<?php
if (!isset($seg)) exit;

/**
 * Applies or removes all role assignments linked to a specific plan for a user.
 *
 * When mode is "apply", this function first removes existing assignments for the
 * same user and plan, then recreates them based on the roles linked to the plan.
 *
 * When mode is "remove", this function removes only the role assignments that
 * belong to the given user, plan, and roles linked to the plan.
 *
 * @param string $mode The operation mode: "apply" or "remove".
 * @param int $user_id The ID of the user whose plan roles will be managed.
 * @param int $plan_id The ID of the plan used to retrieve related role IDs.
 * @param bool $debug Whether to output debug information.
 * @return int|null The last inserted assignment ID when applying, or the number of affected rows when removing.
 */
function user_plan_function_manager($mode = 'apply', $user_id = 0, $plan_id = 0, bool $debug = false)
{
    $plan_roles = get_plan_role_ids($plan_id);
    if ($debug) dump($plan_roles);

    // Add new permissions
    if ($mode == 'apply')
    {
        query_it("
        DELETE FROM
            tb_user_role_assignments
        WHERE user_id = '{$user_id}'
            AND plan_id = '{$plan_id}'
        ", false, $debug);

        foreach ($plan_roles as $role_id)
        {
            insert('tb_user_role_assignments', [
                'user_id' => $user_id,
                'role_id' => $role_id,
                'plan_id' => $plan_id,
            ], false, $debug);
        }

        return inserted_id();
    }

    // Remove permissions
    elseif ($mode == 'remove')
    {
        $plan_roles = implode("','", $plan_roles);

        query_it("
        DELETE FROM
            tb_user_role_assignments
        WHERE user_id = '{$user_id}'
            AND plan_id = '{$plan_id}'
            AND role_id IN ('{$plan_roles}')
        ", false, $debug);

        return affected_rows();
    }
}


/**
 * Add an interval to a datetime string based on plan interval data.
 *
 * @param string $datetime       Base datetime in Y-m-d format.
 * @param string $interval_unit  Interval unit: day, week, month, year, lifetime.
 * @param int    $interval_count Interval count.
 *
 * @return string|null
 */
function plan_subscription_add_interval(string $datetime, string $interval_unit, int $interval_count): ?string
{
    if ($interval_unit === 'lifetime') {
        return null;
    }

    // $interval_count = max(1, $interval_count);
    $interval_count = max(1, ($interval_count-1));

    try {
        $date = new DateTime($datetime);

        switch ($interval_unit) {
            case 'day':
                $date->modify("+{$interval_count} day");
                break;

            case 'week':
                $date->modify("+{$interval_count} week");
                break;

            case 'month':
                $date->modify("+{$interval_count} month");
                break;

            case 'year':
                $date->modify("+{$interval_count} year");
                break;

            default:
                return null;
        }

        return $date->format('Y-m-d');
    }

    catch (Throwable $e) {
        return null;
    }
}


/**
 * Add days to a datetime string.
 *
 * @param string $datetime Base datetime in Y-m-d format.
 * @param int    $days     Number of days to add.
 *
 * @return string|null
 */
function plan_subscription_add_days(string $datetime, int $days): ?string
{
    $days = max(0, $days);

    try {
        $date = new DateTime($datetime);
        $date->modify("+{$days} day");

        return $date->format('Y-m-d');
    }

    catch (Throwable $e) {
        return null;
    }
}

/**
 * Check whether the user already has an active-like subscription for the given plan.
 *
 * @param int $user_id User ID.
 * @param int $plan_id Plan ID.
 *
 * @return array
 */
function get_user_active_plan_subscription(int $user_id, int $plan_id): ?array
{
    $subscription = get_result("
        SELECT *
        FROM tb_plan_user_subscriptions
        WHERE user_id = '{$user_id}'
          AND plan_id = '{$plan_id}'
          AND status IN ('pending', 'active', 'trialing', 'past_due', 'paused')
        LIMIT 1
    ");

    return !empty($subscription) ? $subscription : [];
}

/**
 * Get a subscription by ID.
 *
 * @param int      $subscription_id Subscription ID.
 * @param int|null $user_id         Optional user ID ownership validation.
 *
 * @return array|null
 */
function get_plan_subscription(int $subscription_id, ?int $user_id = null): ?array
{
    $where_user = !empty($user_id) ? " AND user_id = '{$user_id}'" : '';

    $subscription = get_result("
        SELECT *
        FROM tb_plan_user_subscriptions
        WHERE id = '{$subscription_id}'
        {$where_user}
        LIMIT 1
    ");

    return !empty($subscription['id']) ? $subscription : null;
}

/**
 * Get all active payment methods of a user ordered for retry attempts.
 *
 * Order:
 * - active/default first
 * - newest first afterwards
 *
 * @param int $user_id User ID.
 *
 * @return array
 */
function get_active_user_payment_methods(int $user_id): array
{
    global $config, $payment_gateways;

    $active_payment_methods = $config['active_payment_methods'] ?? [];

    $gateways_methods = [];
    foreach (($payment_gateways ?? []) as $key => $gateway)
    {
        if (!in_array($key, $active_payment_methods)) continue;

        $key = explode('.', $key);
        $gateways_methods[] = [
            'provider' => $key[0],
            'method' => $key[1]
        ];
    }

    $providers = array_unique(array_column($gateways_methods, 'provider'));
    $providers = implode("','", $providers);

    $methods = array_unique(array_column($gateways_methods, 'method'));
    $methods = implode("','", $methods);

    $methods = get_results("
        SELECT *
        FROM tb_user_payment_methods
        WHERE user_id = '{$user_id}'
          AND status_id = '1'
          AND provider  IN ('{$providers}')
          AND method IN ('{$methods}')
        ORDER BY is_default DESC, id DESC
    ");

    return !empty($methods) ? $methods : [];
}


/**
 * Check whether a payment method was already attempted today for a subscription.
 *
 * This prevents retrying the same saved payment method more than once in the same day
 * for the same subscription cycle.
 *
 * @param int         $subscription_id         Subscription ID.
 * @param int|string  $user_payment_method_id  Saved payment method ID.
 * @param string|null $reference_date          Reference date in Y-m-d format. Defaults to today.
 *
 * @return bool
 */
function subscription_payment_method_attempted_today(int $subscription_id, $user_payment_method_id, ?string $reference_date = null)
{
    $reference_date = !empty($reference_date) ? trim($reference_date) : date('Y-m-d');
    $reference_date = addslashes($reference_date);
    $user_payment_method_id = addslashes((string)$user_payment_method_id);

    $attempt = get_col("
        SELECT p.id
        FROM tb_orders o
        INNER JOIN tb_order_payments p
            ON p.order_id = o.id
        WHERE o.subscription_id = '{$subscription_id}'
          AND p.user_payment_method_id = '{$user_payment_method_id}'
          AND DATE(o.created_at) = '{$reference_date}'
          AND o.order_purpose IN ('subscription_renewal', 'retry')
        ORDER BY o.id DESC
        LIMIT 1
    ");

    return $attempt ?? null;
}

/**
 * Get the commercial billing snapshot for a subscription.
 *
 * Priority:
 * - tb_plan_proposal_lock
 * - tb_plans current values
 *
 * @param int  $subscription_id Subscription ID.
 * @param bool $debug           Print debug information when true.
 *
 * @return array
 */
function get_subscription_proposal(int $subscription_id, bool $debug = false): array
{
    $lock = get_result("
        SELECT *
        FROM tb_plan_proposal_lock
        WHERE subscription_id = '{$subscription_id}'
        ORDER BY id DESC
        LIMIT 1
    ");

    return !empty($lock['id']) ? $lock : [];
}

/**
 * List subscriptions that are due for first billing, renewal or retry.
 *
 * Included cases:
 * - trialing or active subscriptions whose next_billing_at is due
 * - past_due subscriptions still inside grace period
 *
 * @param string|null $reference_date Reference datetime in Y-m-d format. Defaults to NOW().
 * @param bool        $debug          Print SQL query when true.
 *
 * @return array
 */
function get_due_plan_subscriptions(?string $reference_date = null, bool $debug = false): array
{
    $reference_date = !empty($reference_date) ? trim($reference_date) : date('Y-m-d');
    $reference_date_sql = addslashes($reference_date);

    $subscriptions = get_results("
        SELECT
            *,
            CASE
                WHEN status = 'past_due' THEN 'retry'
                WHEN status = 'trialing' THEN 'trial_end_charge'
                ELSE 'renewal'
            END AS billing_action
        FROM tb_plan_user_subscriptions
        WHERE auto_renew = '1'
          AND
          (
              (
                  status IN ('trialing', 'active')
                  AND next_billing_at IS NOT NULL
                  AND next_billing_at <= '{$reference_date_sql}'
              )
              OR
              (
                  status = 'past_due'
                  AND next_billing_at IS NOT NULL
                  AND next_billing_at <= '{$reference_date_sql}'
                  AND grace_ends_at IS NOT NULL
                  AND grace_ends_at >= '{$reference_date_sql}'
              )
          )
        ORDER BY next_billing_at ASC, id ASC
    ", false, $debug);

    return !empty($subscriptions) ? $subscriptions : [];
}

/**
 * Activates a plan subscription for an order and syncs the order back
 * (subscription_id + order_purpose), plus logs a note about it.
 *
 * Shared by create_order() (index.php) and order_notification()
 * (src/notifications.php) -- both reach this point once an order tied to a
 * plan has just gone into a paid status; the only thing that differs
 * between them is how they got hold of $order_id / $user_id / $plan.
 *
 * @param array $params {
 *     @type int    $order_id                Order to attach the subscription to.
 *     @type int    $user_id                 Owner of the subscription.
 *     @type array  $plan                    Full plan row (id, name, trial_days, ...).
 *     @type string $order_purpose           Order purpose computed by the caller
 *                                            (kept as-is when already "trial_validation").
 *     @type mixed  $user_payment_method_id  Optional.
 *     @type string $statement_descriptor    Optional.
 * }
 * @param bool $debug
 * @return array The result of subscription_business_logic() -- empty
 *               'subscription' when no subscription was actually created.
 */
function activate_order_plan_subscription(array $params, bool $debug = false): array
{
    $order_id               = $params['order_id'] ?? null;
    $user_id                = $params['user_id'] ?? null;
    $plan                   = $params['plan'] ?? [];
    $order_purpose          = $params['order_purpose'] ?? '';
    $user_payment_method_id = $params['user_payment_method_id'] ?? null;
    $statement_descriptor   = $params['statement_descriptor'] ?? null;

    $subscription = [
        'user_id' => $user_id,
        'plan_id' => $plan['id'] ?? null,
        'plan'    => $plan,
    ];

    if (!empty($user_payment_method_id)) {
        $subscription['statement_descriptor']   = $statement_descriptor;
        $subscription['user_payment_method_id'] = $user_payment_method_id;
    }

    $subscription_result = subscription_business_logic($subscription, $debug);
    $subscription        = $subscription_result['subscription'] ?? [];

    if (empty($subscription['subscription_id'])) {
        return $subscription_result;
    }

    $purpose = ($order_purpose == 'trial_validation')
        ? $order_purpose
        : $subscription_result['purpose'];

    query_it("
        UPDATE tb_orders
        SET
            subscription_id = '{$subscription['subscription_id']}',
            order_purpose = '{$purpose}'
        WHERE id = '{$order_id}'
        LIMIT 1
    ");

    $subscription_note = "The customer subscribed the plan **{$plan['name']}** ({$subscription_result['purpose']})";
    add_order_note([
        'order_id'  => $order_id,
        'content'   => $subscription_note,
        'note_type' => 'order_created',
        'body_type' => 'alert'
    ]);

    return $subscription_result;
}

function user_is_already_sub(string|int $user_id = 0, string|int $plan = 0)
{
    $sql = "
    SELECT
        sub.id
    FROM tb_plan_user_subscriptions AS sub
    INNER JOIN tb_plans AS plan ON plan.id = sub.plan_id
    WHERE
        sub.user_id = '{$user_id}' AND
        (
            sub.status != 'canceled' AND
            sub.status != 'paused' AND
            sub.status != 'paused'
        ) AND
        (
            plan.slug = '{$plan}' OR
            plan.id = '{$plan}'
        )

    ORDER BY sub.id DESC
    LIMIT 1
    ";

    return get_col($sql);
}
