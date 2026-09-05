<?php
if (!isset($seg)) exit;

/**
 * Create a renewal or retry order for a subscription.
 *
 * This function only creates the order payload/order record.
 * Actual payment processing should be handled by your normal order flow.
 *
 * @param int   $subscription_id Subscription ID.
 * @param array $data            Optional arguments:
 *                               - payment_method (string)
 *                               - user_payment_method_id (int|string)
 *                               - order_purpose (string) subscription_renewal|retry
 *                               - billing_reference_date (string)
 *                               - renewal_attempt (int)
 * @param bool  $debug           Print debug information when true.
 *
 * @return array
 */
function create_subscription_renewal_order(array $subscription, array $data = [], bool $debug = false): array
{
    global $info;

    if (empty($subscription['id'])) {
        return [
            'code' => 'error',
            'msg'  => ['reason' => 'Subscription not found.']
        ];
    }

    if (!in_array($subscription['status'], ['trialing', 'active', 'past_due'], true)) {
        return [
            'code' => 'error',
            'msg'  => ['reason' => 'Subscription is not billable in the current status.']
        ];
    }

    if (empty($subscription['auto_renew'])) {
        return [
            'code' => 'error',
            'msg'  => ['reason' => 'Subscription auto renew is disabled.']
        ];
    }

    $cycles_quantity    = (int)($subscription['cycles_quantity'] ?? 0);
    $subscription_id    = $subscription['id'];

    /**
     *
     * Verify if subscripation has a proposal and apply.
     *
     */
    $proposal = get_subscription_proposal($subscription_id, $debug);

    $plan              = get_plan($subscription['plan_id']);
    $sale_price_cycles = (int)($plan['sale_price_cycles'] ?? 0);
    $cycles_quantity   = (int)($subscription['cycles_quantity'] ?? 0);

    $amount = isset($proposal['amount'])
        ? (float)$proposal['amount']
        : (float)($plan['sale_price'] ?? 0);

    if ($cycles_quantity > $sale_price_cycles) {
        $amount = isset($proposal['amount'])
            ? (float)$proposal['amount']
            : (float)($plan['regular_price'] ?? 0);
    }

    if ($amount <= 0) {
        return [
            'code' => 'error',
            'subscription_id' => $subscription_id,
            'msg'  => ['reason' => 'Invalid renewal amount.']
        ];
    }


    /**
     * Billing
     */
    $billing_reference_date = !empty($data['billing_reference_date'])
        ? trim((string)$data['billing_reference_date'])
        : trim((string)($subscription['next_billing_at'] ?? ''));

    if ($billing_reference_date === '') {
        return [
            'code' => 'error',
            'msg'  => ['reason' => 'Missing billing reference date.']
        ];
    }

    // $existing_open_order = get_open_subscription_billing_order($subscription_id, $billing_reference_date);

    // if (!empty($existing_open_order['id'])) {
    //     return [
    //         'code' => 'error',
    //         'msg'  => [
    //             'reason' => 'There is already an open billing order for this subscription cycle.',
    //             'order_id' => (int)$existing_open_order['id']
    //         ]
    //     ];
    // }

    $order_purpose = trim((string)($data['order_purpose'] ?? 'subscription_renewal'));
    $renewal_attempt = max(1, (int)($data['renewal_attempt'] ?? ((int)($subscription['renewal_attempts_count'] ?? 0) + 1)));

    /**
     *
     * Paymet method
     *
     */
    $user_payment_method_id = array_key_exists('user_payment_method_id', $data)
        ? trim((string)$data['user_payment_method_id'])
        : trim((string)($subscription['user_payment_method_id'] ?? ''));

    $statement_descriptor = !empty($data['statement_descriptor'])
        ? trim((string)$data['statement_descriptor'])
        : trim((string)($subscription['statement_descriptor'] ?? $info['short_name']));

    $payment_method = trim((string)($data['payment_method'] ?? 'credit_card'));

    /**
     *
     * Plan
     *
     */
    $plan                 = get_plan($subscription['plan_id']);
    $sale_price_cycles    = (int)($plan['sale_price_cycles'] ?? 0);
    $amount               = (float)($proposal['sale_price'] ?? $plan['sale_price']);

    if ($cycles_quantity > $sale_price_cycles) {
        $amount = (float)($proposal['regular_price'] ?? $plan['regular_price']);
    }

    $payload = [
        'user_id' => (int)$subscription['user_id'],
        'order_type' => 'plan',
        'order_purpose' => $order_purpose,
        // 'billing_reference_date' => $billing_reference_date,
        // 'renewal_attempt' => $renewal_attempt,
        'payment_method' => $payment_method,
        'subscription_id' => (int)$subscription['id'],
        'payment_data' => [
            'user_payment_method_id' => $user_payment_method_id,
            'statement_descriptor' => $statement_descriptor,
        ],
        'items' => [
            [
                'item_type' => 'plan',
                'plan_id' => (int)$subscription['plan_id'],
                'item_name' => (string)($plan['name'] ?? 'Subscription renewal'),
                'quantity' => 1,
                'unit_price' => $amount,
            ]
        ],
        'process-payment' => true,
        'all_info' => true,
    ];

    // $debug = true;

    if ($debug) {
        dump($payload);
    }

    /**
     * Create the order to renew.
     */
    $order = create_order($payload);

    if ($debug) {
        dump($order);
    }

    if (($order['code'] ?? '') === 'success')
    {
        $order_data = (array)($order['order'] ?? []);
        $order_status_id = (int)($order_data['status_id'] ?? $order_data['order_status_id'] ?? 0);

        // debug retry
        if ($debug && !isset($_SESSION['retry-test'])) {
            $_SESSION['retry-test'] = false;
            $order_status_id = 4;
        }

        /**
         * Paid: advance the billing cycle.
         */
        if ($order_status_id === 3)
        {
            $interval_unit        = trim((string)($proposal['interval_unit'] ?? $plan['interval_unit'] ?? 'month'));
            $interval_count       = max(1, (int)($proposal['interval_count'] ?? $plan['interval_count'] ?? 1));

            $current_period_start = date('Y-m-d');
            $current_period_end   = plan_subscription_add_interval($current_period_start, $interval_unit, $interval_count);
            $cycles_quantity      = $cycles_quantity + 1;
            $next_billing_at      = $current_period_end;

            $current_period_end_sql = $current_period_end
                ? "'" . addslashes($current_period_end) . "'"
                : "NULL";

            $next_billing_at_sql = $next_billing_at
                ? "'" . addslashes($next_billing_at) . "'"
                : "NULL";

            $current_period_start = addslashes($current_period_start);

            query_it("
                UPDATE `tb_plan_user_subscriptions` SET
                    `trial_ends_at` = NULL,
                    `status` = 'active',
                    `grace_ends_at` = NULL,
                    `current_period_start` = '{$current_period_start}',
                    `current_period_end` = {$current_period_end_sql},
                    `next_billing_at` = {$next_billing_at_sql},
                    `renewal_attempts_count` = 0,
                    `cycles_quantity` = '{$cycles_quantity}',
                    `last_renewal_attempt_at` = NULL
                WHERE `id` = '{$subscription_id}'
                LIMIT 1
            ");
        }

        /**
         * Failed or cancelled: keep the cycle overdue and enter/keep grace period.
         * Retry flow will be implemented afterwards.
         */
        elseif (in_array($order_status_id, [4, 5], true))
        {
            $grace_days = max(0, (int)($proposal['grace_days'] ?? $plan['grace_days'] ?? 0));
            $now = date('Y-m-d');

            /**
             * Preserve existing grace end if it already exists and is still open.
             * Otherwise, create a new grace window starting now.
             */
            $grace_ends_at = null;

            if (!empty($subscription['grace_ends_at']) && strtotime((string)$subscription['grace_ends_at']) >= strtotime($now)) {
                $grace_ends_at = (string)$subscription['grace_ends_at'];
            }

            elseif ($grace_days > 0) {
                $grace_ends_at = plan_subscription_add_days($now, $grace_days);
            }

            $grace_ends_at_sql = $grace_ends_at
                ? "'" . addslashes($grace_ends_at) . "'"
                : "NULL";

            query_it("
                UPDATE `tb_plan_user_subscriptions` SET
                    `status` = 'past_due',
                    `grace_ends_at` = {$grace_ends_at_sql},
                    `renewal_attempts_count` = COALESCE(`renewal_attempts_count`, 0) + 1,
                    `last_renewal_attempt_at` = NOW()
                WHERE `id` = '{$subscription_id}'
                LIMIT 1
            ");
        }

        /**
         * Pending / processing: do not advance cycle and do not mark past_due yet.
         */
        elseif (in_array($order_status_id, [1, 2], true))
        {
            query_it("
                UPDATE `tb_plan_user_subscriptions` SET
                    `last_renewal_attempt_at` = NOW()
                WHERE `id` = '{$subscription_id}'
                LIMIT 1
            ", false, $debug);
        }
    }

    $res = [
        'code' => $order['code'] ?? 'error',
        'subscription_id' => $subscription_id,
        'order' => $order['order'] ?? [],
    ];

    return $res;
}

/**
 * Fallback used when the user has no active/saved payment method on file.
 * Instead of giving up (or retrying forever with nothing to retry
 * against), creates a single charge using the system's default payment
 * method (get_system_info('default_payment_method')).
 *
 * Guarded by a dedicated order_purpose: if an order was already created
 * this way for the subscription -- successful or not -- it's skipped
 * instead of creating a new one on every cron pass. This only ever
 * fires once per subscription.
 *
 * @param array $subscription Subscription data.
 * @param array $data         Optional arguments:
 *                             - billing_reference_date (string)
 * @param bool  $debug        Print debug information when true.
 *
 * @return array
 */
function charge_subscription_with_default_payment_method(array $subscription, array $data = [], bool $debug = false): array
{
    $subscription_id = (int)($subscription['id'] ?? 0);

    if (empty($subscription_id))
    {
        return [
            'code' => 'error',
            'msg'  => ['reason' => 'Subscription not found.']
        ];
    }

    /**
     * Never attempt this fallback more than once per subscription,
     * regardless of the outcome of the previous attempt.
     */
    $existing_order = get_result("
        SELECT id, status_id
        FROM tb_orders
        WHERE subscription_id = '{$subscription_id}'
          AND order_purpose = 'renewal_no_payment_method'
          AND status_id != 3
        ORDER BY id DESC
        LIMIT 1
    ", false, $debug);

    if (!empty($existing_order['id']))
    {
        return [
            'code' => 'error',
            'subscription_id' => $subscription_id,
            'msg'  => [
                'reason'   => 'The default payment method was already attempted once for this subscription.',
                'order_id' => (int)$existing_order['id'],
            ]
        ];
    }

    $default_gateway_key = trim((string)get_system_info('default_payment_method'));

    if ($default_gateway_key === '') {
        return [
            'code' => 'error',
            'subscription_id' => $subscription_id,
            'msg'  => ['reason' => 'No default payment method configured for the system.']
        ];
    }

    // Gateway keys are "provider.method" (e.g. "pagbank.pix") -- only the
    // method matters here, create_subscription_renewal_order() resolves
    // the provider on its own via resolve_provider_by_method().
    $dot = strrpos($default_gateway_key, '.');
    $payment_method = $dot !== false
        ? substr($default_gateway_key, $dot + 1)
        : $default_gateway_key;

    return create_subscription_renewal_order($subscription, [
        'billing_reference_date' => $data['billing_reference_date'] ?? ($subscription['next_billing_at'] ?? ''),
        'order_purpose'          => 'renewal_no_payment_method',
        'payment_method'         => $payment_method,
        // Force a fresh charge on the default method instead of silently
        // reusing whatever (possibly inactive) method the subscription
        // had saved.
        'user_payment_method_id' => '',
    ], $debug);
}

/**
 * Try to charge a subscription using all active user payment methods.
 *
 * Strategy:
 * - try the subscription preferred payment method first when available
 * - then try the remaining active methods ordered by default/newest
 * - stop at the first paid renewal
 * - never retry the same payment method twice on the same day
 * - when there are no active payment methods at all, fall back to a
 *   single charge on the system's default payment method (see
 *   charge_subscription_with_default_payment_method())
 *
 * @param array $subscription Subscription data.
 * @param array $data         Optional arguments:
 *                            - billing_reference_date (string)
 *                            - order_purpose (string)
 *                            - reference_date (string) Y-m-d
 * @param bool  $debug        Print debug information when true.
 *
 * @return array
 */
function retry_subscription_with_user_payment_methods(array $subscription, array $data = [], bool $debug = false): array
{
    $subscription_id = (int)($subscription['id'] ?? 0);

    if (empty($subscription_id)) {
        return [
            'code' => 'error',
            'msg'  => ['reason' => 'Subscription not found.']
        ];
    }

    $user_id = (int)($subscription['user_id'] ?? 0);

    if ($user_id <= 0) {
        return [
            'code' => 'error',
            'msg'  => ['reason' => 'Invalid subscription user.']
        ];
    }

    $methods = get_active_user_payment_methods($user_id);
    if (empty($methods))
    {
        return charge_subscription_with_default_payment_method($subscription, $data, $debug);
    }

    $reference_date = !empty($data['reference_date'])
        ? trim((string)$data['reference_date'])
        : date('Y-m-d');

    /**
     * Put the subscription preferred payment method first, if it exists in the list.
     */
    $preferred_id = trim((string)($subscription['user_payment_method_id'] ?? ''));

    if ($preferred_id !== '')
    {
        usort($methods, function ($a, $b) use ($preferred_id) {
            $a_is_preferred = ((string)($a['id'] ?? '') === $preferred_id) ? 1 : 0;
            $b_is_preferred = ((string)($b['id'] ?? '') === $preferred_id) ? 1 : 0;

            if ($a_is_preferred === $b_is_preferred) {
                return 0;
            }

            return $a_is_preferred > $b_is_preferred ? -1 : 1;
        });
    }

    $attempts = [];

    foreach ($methods as $method)
    {
        $user_payment_method_id = trim((string)($method['id'] ?? ''));
        $payment_method         = trim((string)($method['method'] ?? 'credit_card'));

        if ($user_payment_method_id === '') {
            continue;
        }

        /**
         * Do not retry the same payment method twice on the same day.
         */
        $already_attempted_today = subscription_payment_method_attempted_today(
            $subscription_id,
            $user_payment_method_id,
            $reference_date
        );

        if (!empty($already_attempted_today))
        {
            $attempts[] = [
                'user_payment_method_id' => $user_payment_method_id,
                'payment_method' => $payment_method,
                'skipped' => true,
                'reason' => 'This payment method has already been attempted today for this subscription.',
                'payment_attempt_id' => $already_attempted_today,
            ];

            continue;
        }

        $order = create_subscription_renewal_order($subscription, [
            'billing_reference_date' => $data['billing_reference_date'] ?? ($subscription['next_billing_at'] ?? ''),
            'order_purpose'          => $data['order_purpose'] ?? 'retry',
            'payment_method'         => $payment_method,
            'user_payment_method_id' => $user_payment_method_id,
        ], $debug);

        $attempts[] = [
            'user_payment_method_id' => $user_payment_method_id,
            'payment_method'         => $payment_method,
            'response'               => $order,
        ];

        if (($order['code'] ?? '') !== 'success') {
            // continue;
        }

        $order_data      = (array)($order['order'] ?? []);
        $order_status_id = (int)($order_data['status_id'] ?? 5);

        if ($debug && !isset($_SESSION['retry-test'])) {
            $_SESSION['retry-test'] = false;
            // $order_status_id = 5;
        }

        /**
         * Stop on the first paid order.
         */
        if ($order_status_id === 3)
        {
            return [
                'code' => 'success',
                'subscription_id' => $subscription_id,
                'msg' => [
                    'reason' => 'Subscription renewal paid successfully.',
                    'user_payment_method_id' => $user_payment_method_id,
                    'payment_method' => $payment_method,
                    'attempts' => $attempts,
                ]
            ];
        }

        else {
            continue;
        }
    }

    return [
        'code' => 'error',
        'subscription_id' => $subscription_id,
        'msg' => [
            'reason' => 'All active payment methods failed for this retry cycle.',
            'attempts' => $attempts,
        ]
    ];
}
