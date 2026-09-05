<?php
if (!isset($seg)) exit;

require_once __DIR__ .'/status.php';
require_once __DIR__ .'/helpers.php';
require_once __DIR__ .'/renewal.php';
require_once __DIR__ .'/cancel.php';


/**
 * Routes subscription creation based on the configured business model, classifies the purpose
 * (upgrade|downgrade|subscription_renewal|trial_validation|came_back) and, if needed, finalizes the
 * previous subscription before creating the new one.
 *
 * "Current subscription" rules (for comparison purposes): status active, trialing or past_due.
 * "History" = any row in tb_plan_user_subscriptions, regardless of status.
 *
 * d2c_single_plan    -> global scope (1 subscription per user). Different segment
 *                       (target_audience) = line switch (finalize old, treat as came_back).
 *                       Same segment compares order_reg: equal = subscription_renewal, lower = upgrade,
 *                       higher = downgrade.
 * d2c_multiple_plans -> plans coexist, no upgrade/downgrade. Grouping = exact plan_id.
 * marketplace        -> 1 current subscription per creator. Switching plans of the same
 *                       creator = subscription_renewal.
 *
 * @param array $data Same payload accepted by create_plan_subscription() (user_id, plan_id, plan, etc).
 * @param bool  $debug Print queries if true.
 *
 * @return array{purpose:string,business_model:string,subscription:array}|array{code:string,msg:array}
 */
function subscription_business_logic(array $data, bool $debug = false): array
{
    $business_model = get_system_info('subscriptions_business_model');

    $user_id = (int)($data['user_id'] ?? 0);
    $plan_id = (int)($data['plan_id'] ?? ($data['plan']['id'] ?? 0));

    if (empty($user_id) || empty($plan_id)) {
        return [
            'code' => 'error',
            'msg'  => ['reason' => 'Invalid user_id or plan_id.'],
        ];
    }

    $plan = (array)($data['plan'] ?? []);

    if (empty($plan['id'])) {
        $plan = get_result("
            SELECT *
            FROM tb_plans
            WHERE id = '{$plan_id}'
            LIMIT 1
        ", false, $debug);
    }

    if (empty($plan['id'])) {
        return [
            'code' => 'error',
            'msg'  => ['reason' => 'Plan not found.'],
        ];
    }

    $data['plan'] = $plan;

    /**
     * Statuses that represent a subscription that is "current" today, used only to decide
     * upgrade/downgrade/subscription_renewal — not to be confused with the billing cron.
     */
    $current_statuses     = ['active', 'trialing', 'past_due'];
    $current_statuses_sql = implode(',', array_map(fn($s) => "'" . addslashes($s) . "'", $current_statuses));

    $purpose              = null;
    $subscription_to_end  = null; // full subscription row, needed by finalize_plan_subscription()


    /**
     *
     * d2c_single_plan
     *
     */
    if ($business_model == 'd2c_single_plan')
    {
        $current = get_result("
            SELECT
                sub.id,
                sub.plan_id,
                sub.user_id,
                sub.status,
                plan.target_audience,
                plan.order_reg,
                plan.interval_unit,
                plan.interval_count
            FROM tb_plan_user_subscriptions AS sub
            INNER JOIN tb_plans AS plan ON plan.id = sub.plan_id
            WHERE sub.user_id = '{$user_id}'
              AND sub.status IN ({$current_statuses_sql})
            ORDER BY sub.id DESC
            LIMIT 1
        ", false, $debug);

        if (empty($current['id']))
        {
            $has_history = get_result("
                SELECT id
                FROM tb_plan_user_subscriptions
                WHERE user_id = '{$user_id}'
                LIMIT 1
            ", false, $debug);

            $purpose = !empty($has_history['id']) ? 'came_back' : 'first_subscription';
        }

        // Different segment (e.g. had an "escort" plan and requested a "customer" plan) = line switch.
        // Finalize the old one and treat as came_back, since history exists by definition at this point.
        elseif ((string)$current['target_audience'] !== (string)$plan['target_audience'])
        {
            $purpose                = 'came_back';
            $subscription_to_end = $current;
        }

        else
        {
            // Same tier -> subscription_renewal
            if ((int)$plan['order_reg'] === (int)$current['order_reg'])
            {
                $purpose = 'subscription_renewal';

                // End the current sub to apply de new one.
                // same tier, only the duration changed (monthly -> annual, e.g.)
                if ((int)$plan['id'] !== (int)$current['plan_id'])
                {
                    $days_map = ['day' => 1, 'week' => 7, 'month' => 30, 'year' => 365, 'lifetime' => 36500];

                    $new_duration_days     = ((int)$plan['interval_count']) * ($days_map[$plan['interval_unit']] ?? 30);
                    $current_duration_days = ((int)$current['interval_count']) * ($days_map[$current['interval_unit']] ?? 30);

                    if ($new_duration_days > $current_duration_days) {
                        $purpose = 'subscription_renewal_longer';
                    } elseif ($new_duration_days < $current_duration_days) {
                        $purpose = 'subscription_renewal_shorter';
                    } else {
                        $purpose = 'subscription_renewal';
                    }

                    $subscription_to_end = $current;
                }
            }

            // Upgrade
            elseif ((int)$plan['order_reg'] < (int)$current['order_reg']) {
                $purpose = 'upgrade'; // order_reg = 1 is the top of the segment
                $subscription_to_end = $current;
            }

            // Downgrade
            else {
                $purpose = 'downgrade';
                $subscription_to_end = $current;
            }

            // $subscription_to_end = $current;
        }
    }

    /**
     *
     * d2c_multiple_plans
     *
     */
    elseif ($business_model == 'd2c_multiple_plans')
    {
        $current = get_result("
            SELECT id, plan_id, user_id, status
            FROM tb_plan_user_subscriptions
            WHERE user_id = '{$user_id}'
              AND plan_id = '{$plan_id}'
              AND status IN ({$current_statuses_sql})
            LIMIT 1
        ", false, $debug);

        // Already has this plan current; don't finalize it — create_plan_subscription() will reject it as a duplicate.
        if (!empty($current['id'])) {
            $purpose = 'subscription_renewal';
        }

        else
        {
            $has_history = get_result("
                SELECT id
                FROM tb_plan_user_subscriptions
                WHERE user_id = '{$user_id}'
                  -- AND plan_id = '{$plan_id}'
                LIMIT 1
            ", false, $debug);

            $purpose = !empty($has_history['id']) ? 'came_back' : 'first_subscription';
        }
    }

    /**
     *
     * marketplace
     *
     */
    elseif ($business_model == 'marketplace')
    {
        $creator_id = (int)($plan['user_id'] ?? 0);

        $current = get_result("
            SELECT
                sub.id,
                sub.plan_id,
                sub.user_id,
                sub.status,
                plan.interval_unit,
                plan.interval_count
            FROM tb_plan_user_subscriptions AS sub
            INNER JOIN tb_plans AS plan ON plan.id = sub.plan_id
            WHERE sub.user_id = '{$user_id}'
              AND plan.user_id = '{$creator_id}'
              AND sub.status IN ({$current_statuses_sql})
            LIMIT 1
        ", false, $debug);

        if (!empty($current['id']))
        {
            // Same plan_id: goes through create_plan_subscription()'s stacking logic instead.
            if ((int)$plan['id'] === (int)$current['plan_id']) {
                $purpose = 'subscription_renewal';
            }
            else
            {
                $days_map = ['day' => 1, 'week' => 7, 'month' => 30, 'year' => 365, 'lifetime' => 36500];

                $new_duration_days     = ((int)$plan['interval_count']) * ($days_map[$plan['interval_unit']] ?? 30);
                $current_duration_days = ((int)$current['interval_count']) * ($days_map[$current['interval_unit']] ?? 30);

                if ($new_duration_days > $current_duration_days) {
                    $purpose = 'subscription_renewal_longer';
                } elseif ($new_duration_days < $current_duration_days) {
                    $purpose = 'subscription_renewal_shorter';
                } else {
                    $purpose = 'subscription_renewal';
                }

                $subscription_to_end = $current;
            }
        }
        else
        {
            $has_history = get_result("
                SELECT id
                FROM tb_plan_user_subscriptions
                WHERE user_id = '{$user_id}'
                  AND plan_id IN (SELECT id FROM tb_plans WHERE user_id = '{$creator_id}')
                LIMIT 1
            ", false, $debug);

            $purpose = !empty($has_history['id']) ? 'came_back' : 'first_subscription';
        }
    }

    else
    {
        return [
            'code' => 'error',
            'msg'  => ['reason' => "Unknown business model: {$business_model}."],
        ];
    }

    // $subscription_result = create_plan_subscription($data, $debug);

    if (!empty($subscription_to_end))
    {
        finalize_plan_subscription([
            'subscription'             => $subscription_to_end,
            'final_status'             => 'canceled',
            'reason'                   => $purpose, // upgrade | downgrade | renewal | came_back
            'expected_current_status'  => $current_statuses,
        ], $debug);
    }

    $subscription_result = create_plan_subscription($data, $debug);

    return [
        'purpose'        => $purpose,
        'business_model' => $business_model,
        'subscription'   => $subscription_result,
    ];
}


/**
 * Create a plan subscription for a user.
 *
 * Rules:
 * - Creates a new row in tb_plan_user_subscriptions.
 * - Copies a commercial snapshot to tb_plan_proposal_lock when allows_price_lock = 1.
 * - Prevents duplicated active-like subscriptions for the same user and plan.
 * - Initializes renewal control fields without starting grace/retry prematurely.
 *
 * @param array $data Subscription payload.
 * @param bool  $debug Print SQL queries if true.
 *
 * @return array
 */
function create_plan_subscription(array $data, bool $debug = false): array
{
    global $info;

    $user_id                = (int)($data['user_id'] ?? 0);
    $order_id               = (int)($data['order_id'] ?? 0);
    $plan                   = (array)($data['plan'] ?? []);
    $plan_id                = (int)($data['plan_id'] ?? ($plan['id'] ?? 0));
    $statement_descriptor   = trim((string)($data['statement_descriptor'] ?? $info['short_name']));
    $user_payment_method_id = trim((string)($data['user_payment_method_id'] ?? ''));
    $reason                 = trim((string)($data['reason'] ?? 'initial_contract'));
    $start_now              = !isset($data['start_now']) || !empty($data['start_now']);
    $started_at             = !empty($data['started_at']) ? trim((string)$data['started_at']) : date('Y-m-d');

    if (!$start_now && !empty($data['started_at'])) {
        $started_at = trim((string)$data['started_at']);
    }

    $fields = ['user_id', 'plan_id'];

    foreach ($fields as $field)
    {
        if (empty($$field))
        {
            return [
                'code' => 'error',
                'msg' => [
                    'reason' => "Invalid {$field}.",
                ],
            ];
        }
    }

    if (empty($plan))
    {
        $plan = get_result("
            SELECT *
            FROM tb_plans
            WHERE id = '{$plan_id}'
            LIMIT 1
        ");
    }

    if (empty($plan['id'])) {
        return [
            'code' => 'error',
            'msg'  => ['reason' => 'Plan not found.']
        ];
    }

    /**
     * In case user have the same subscription:
     * Instead of blocking, sum the purchased period into the existing subscription,
     * so the user can buy extra time on a plan that's still active/trialing.
     */
    $existing_subscription = get_user_active_plan_subscription($user_id, $plan_id);
    if (!empty($existing_subscription['id']))
    {
        /**
         * Trial subscriptions can't be extended — buying more time while trialing would let the
         * known trial bug (never gets billed) run indefinitely.
         */
        // if ($existing_subscription['status'] === 'trialing') {
        //     return [
        //         'code' => 'error',
        //         'msg'  => [
        //             'reason' => 'The user already has a trialing subscription for this plan.',
        //             'subscription_id' => (int)$existing_subscription['id']
        //         ]
        //     ];
        // }

        $interval_unit  = trim((string)($plan['interval_unit'] ?? 'month'));
        $interval_count = max(1, (int)($plan['interval_count'] ?? 1));
        $extension_base = $started_at;

        if (!empty($existing_subscription['current_period_end']) && strtotime($existing_subscription['current_period_end']) > strtotime($started_at))
        {
            $extension_base = $existing_subscription['current_period_end']; // don't lose unused paid time
        }

        $new_period_end = plan_subscription_add_interval($extension_base, $interval_unit, $interval_count);

        $subscription_update_data = [
            'trial_ends_at'           => null,
            'status'                  => 'active',
            'grace_ends_at'           => null,
            'current_period_end'      => $new_period_end,
            'next_billing_at'         => $new_period_end,
            'renewal_attempts_count'  => 0,
            'cycles_quantity'         => (int)($existing_subscription['cycles_quantity'] ?? 0) + 1,
            'last_renewal_attempt_at' => null,
        ];

        if (!empty($statement_descriptor)) {
            $subscription_update_data['statement_descriptor'] = $statement_descriptor;
        }

        if (!empty($user_payment_method_id)) {
            $subscription_update_data['user_payment_method_id'] = $user_payment_method_id;
        }

        $stack_update = update('tb_plan_user_subscriptions', [
            'data' => $subscription_update_data,
            'where' => where_equal_id((int)$existing_subscription['id']),
        ], false, $debug);

        if ($stack_update === false) {
            return [
                'code' => 'error',
                'msg'  => ['reason' => 'Failed to extend existing subscription.']
            ];
        }

        // Re-run the activation function, so per-purchase perks (e.g. turbo credits) stack on top of each other.
        if (!empty($plan['activation_function']))
        {
            function_process(
                $plan['activation_function'],
                'activation_function',
                $data
            );
        }

        return [
            'code' => 'success',
            'reason' => 'Existing subscription extended successfully.',
            'subscription_id' => (int)$existing_subscription['id'],
            'status' => 'active',
            'current_period_end' => $new_period_end,
            'next_billing_at' => $new_period_end,
            'stacked' => true,
        ];
    }

    $interval_unit  = trim((string)($plan['interval_unit'] ?? 'month'));
    $interval_count = max(1, (int)($plan['interval_count'] ?? 1));
    $trial_days     = max(0, (int)($plan['trial_days'] ?? 0));
    $grace_days     = max(0, (int)($plan['grace_days'] ?? 0));
    $auto_renew     = isset($data['auto_renew']) ? (int)(bool)$data['auto_renew'] : (int)(bool)($plan['auto_renew'] ?? 1);

    $trial_ends_at        = null;
    $current_period_start = $started_at;
    $current_period_end   = null;
    $next_billing_at      = null;
    $status               = 'active';

    if ($trial_days > 0) {
        $trial_ends_at      = plan_subscription_add_days($started_at, $trial_days);
        $current_period_end = $trial_ends_at;
        $next_billing_at    = $trial_ends_at;
        $status             = 'trialing';
    } else {
        $current_period_end = plan_subscription_add_interval($started_at, $interval_unit, $interval_count);
        $next_billing_at    = $current_period_end;
        $status             = 'active';
    }

    /**
     * Renewal control fields.
     *
     * These fields must start neutral on subscription creation.
     * Grace only starts after a failed billing attempt.
     */
    $grace_ends_at            = !empty($data['grace_ends_at']) ? trim((string)$data['grace_ends_at']) : null;
    $last_renewal_attempt_at  = !empty($data['last_renewal_attempt_at']) ? trim((string)$data['last_renewal_attempt_at']) : null;
    $renewal_attempts_count   = isset($data['renewal_attempts_count']) ? max(0, (int)$data['renewal_attempts_count']) : 0;
    // NOTE: `cancel_flow` is the system "how" column (was `cancel_reason`
    // before the rename) -- not to be confused with the customer-facing
    // `cancel_reason` category column added alongside it (see
    // plans-subscriptions' get_subscription_cancel_reason_options()).
    $cancel_flow              = isset($data['cancel_flow']) && $data['cancel_flow'] !== '' ? trim((string)$data['cancel_flow']) : null;
    $canceled_at              = !empty($data['canceled_at']) ? trim((string)$data['canceled_at']) : null;
    $activation_function      = trim((string)($plan['activation_function'] ?? ''));
    $deactivation_function    = trim((string)($plan['deactivation_function'] ?? ''));

    query_it("START TRANSACTION", false, $debug);

    $subscription_insert_result = insert('tb_plan_user_subscriptions', [
        'user_id'                => $user_id,
        'plan_id'                => $plan_id,
        'auto_renew'              => $auto_renew,
        'trial_ends_at'           => $trial_ends_at,
        'status'                  => $status,
        'started_at'              => $started_at,
        'current_period_start'    => $current_period_start,
        'current_period_end'      => $current_period_end,
        'next_billing_at'         => $next_billing_at,
        'grace_ends_at'           => $grace_ends_at,
        'last_renewal_attempt_at' => $last_renewal_attempt_at,
        'renewal_attempts_count'  => $renewal_attempts_count,
        'cancel_flow'             => $cancel_flow,
        'canceled_at'             => $canceled_at,
        'user_payment_method_id'  => $user_payment_method_id,
        'statement_descriptor'    => $statement_descriptor,
    ], false, $debug);

    if ($subscription_insert_result === false)
    {
        query_it("ROLLBACK", false, $debug);

        $duplicated_subscription = get_result("
            SELECT *
            FROM tb_plan_user_subscriptions
            WHERE user_id = '{$user_id}'
              AND plan_id = '{$plan_id}'
            LIMIT 1
        ");

        if (!empty($duplicated_subscription['id'])) {
            return [
                'code' => 'error',
                'msg'  => [
                    'reason' => 'The user already has a subscription for this plan.',
                    'subscription_id' => (int)$duplicated_subscription['id']
                ]
            ];
        }

        return [
            'code' => 'error',
            'msg'  => ['reason' => 'Failed to create subscription.']
        ];
    }

    /**
     * Search if the last user subscription with this plan.
     */
    $subscription = get_result("
        SELECT *
        FROM tb_plan_user_subscriptions
        WHERE user_id = '{$user_id}'
          AND plan_id = '{$plan_id}'
        ORDER BY id DESC
        LIMIT 1
    ");

    if (empty($subscription['id'])) {
        query_it("ROLLBACK", false, $debug);

        return [
            'code' => 'error',
            'msg'  => ['reason' => 'Subscription was created, but could not be reloaded safely.']
        ];
    }
    $subscription_id = (int)$subscription['id'];

    /**
     * Lock proposal.
     */
    if (!empty($plan['allows_price_lock']))
    {
        $currency            = addslashes((string)($plan['currency'] ?? 'BRL'));
        $amount              = isset($plan['sale_price']) && $plan['sale_price'] !== null && $plan['sale_price'] !== ''
            ? (float)$plan['sale_price']
            : (float)($plan['regular_price'] ?? 0);
        $fee_mode            = addslashes((string)($plan['fee_mode'] ?? 'merchant'));
        $activation_sql      = $activation_function !== '' ? "'" . addslashes($activation_function) . "'" : "NULL";
        $deactivation_sql    = $deactivation_function !== '' ? "'" . addslashes($deactivation_function) . "'" : "NULL";

        $lock_insert_result = insert('tb_plan_proposal_lock', [
            'subscription_id'       => $subscription_id,
            'currency'               => $currency,
            'amount'                 => $amount,
            'trial_days'             => $trial_days,
            'grace_days'             => $grace_days,
            'interval_unit'          => $interval_unit,
            'interval_count'         => $interval_count,
            'fee_mode'                => $fee_mode,
            'activation_function'    => $activation_function,   // valor cru, era $activation_sql
            'deactivation_function'  => $deactivation_function, // valor cru, era $deactivation_sql
            'reason'                  => $reason,
        ], false, $debug);

        if ($lock_insert_result === false) {
            query_it("ROLLBACK", false, $debug);

            return [
                'code' => 'error',
                'msg'  => ['reason' => 'Subscription created, but proposal lock snapshot failed.']
            ];
        }
    }

    query_it("COMMIT", false, $debug);

    /**
     *
     * After creat subscription.
     *
     */
    if (!empty($subscription_id))
    {
        /**
         * Apply roles to user.
         */
        user_plan_function_manager('apply', $user_id, $plan_id, $debug);

        /**
         * Execute activation funcion.
         */
        if (!empty($plan['activation_function']))
        {
            function_process(
                $plan['activation_function'],
                'activation_function',
                $data
            );
        }
    }

    return [
        'code' => 'success',
        'reason' => 'Subscription created successfully.',
        'subscription_id' => $subscription_id,
        'status' => $status,
        'trial_ends_at' => $trial_ends_at,
        'current_period_end' => $current_period_end,
        'next_billing_at' => $next_billing_at,
        'grace_ends_at' => $grace_ends_at,
        'last_renewal_attempt_at' => $last_renewal_attempt_at,
        'renewal_attempts_count' => $renewal_attempts_count,
        'cancel_flow' => $cancel_flow,
        'canceled_at' => $canceled_at,
        'proposal_locked' => !empty($plan['allows_price_lock'])
    ];
}


function charge_due_plan_subscriptions(): array
{
    $due_plan_subscriptions = get_due_plan_subscriptions();
    $res = [];

    foreach ($due_plan_subscriptions as $subscription)
    {
        $subscription_id = (int)($subscription['id'] ?? 0);

        if ($subscription_id <= 0) {
            continue;
        }

        $charge = retry_subscription_with_user_payment_methods($subscription, [
            'billing_reference_date' => (string)($subscription['next_billing_at'] ?? ''),
            'order_purpose' => ((string)($subscription['status'] ?? '') === 'past_due') ? 'retry' : 'subscription_renewal',
        ]);

        // dump($charge);
        if (!empty($charge)) {
            $res[] = $charge;
        }
    }

    return $res;
}

function process_plan_subscriptions_cron(): array
{
    // $debug = true;
    $debug = false;

    $res = [
        'charges' => charge_due_plan_subscriptions(),
        'closures' => close_expired_grace_plan_subscriptions(),
        'non_renewing_closures' => close_non_renewing_plan_subscriptions(),
    ];

    if ($debug) {
        dump($res);
    }

    return $res;
}

// $_SESSION['retry-test'] = true;
// unset($_SESSION['retry-test']);
