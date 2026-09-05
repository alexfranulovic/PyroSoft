<?php
if (!isset($seg)) exit;


/**
 * Finalize a plan subscription based on the provided params.
 *
 * Accepted params:
 * - subscription (array, optional if subscription_id is given) Full subscription row.
 * - subscription_id (int, optional if subscription is given) Resolved via
 *   get_plan_subscription() when the full row isn't supplied.
 * - final_status (string, optional) Allowed: canceled, expired. Default: expired
 * - reason (string, optional) Default: subscription_finalized -- written to `cancel_flow`
 *   (how/where the subscription got finalized; not to be confused with the
 *   customer-facing `cancel_reason` category -- see get_subscription_cancel_reason_options(),
 *   plans-subscriptions/src/ui.php -- which this function never touches).
 * - user_cancel_detail (string, optional) Default: '' -- written to `user_cancel_detail`
 *   (the customer's own free-text comment, when there is one to pass through).
 * - expected_current_status (string|array, optional) Default: ['past_due']
 * - order_id, order_status_id, payment_slug (optional) When all three are
 *   given, an order note about the cancellation is added.
 *
 * @param array $params
 * @param bool  $debug
 * @return array
 */
function finalize_plan_subscription(array $params = [], bool $debug = false): array
{
    $subscription = (array)($params['subscription'] ?? []);

    // Accept either the full subscription row or just its id -- whichever
    // is missing gets resolved from the other, so callers don't have to
    // fetch it themselves before calling.
    if (empty($subscription['id']))
    {
        $subscription_id = (int)($params['subscription_id'] ?? 0);

        if ($subscription_id > 0) {
            $subscription = (array)(get_plan_subscription($subscription_id) ?? []);
        }
    }

    $final_status            = trim(strtolower((string)($params['final_status'] ?? 'expired')));
    $reason                  = trim(strtolower((string)($params['reason'] ?? 'subscription_finalized')));
    $user_cancel_detail      = trim((string)($params['user_cancel_detail'] ?? ''));
    $expected_current_status = $params['expected_current_status'] ?? ['past_due'];

    if (!is_array($expected_current_status)) {
        $expected_current_status = [$expected_current_status];
    }

    $expected_current_status = array_map(function ($status) {
        return trim(strtolower((string)$status));
    }, $expected_current_status);

    if (!in_array($final_status, ['canceled', 'expired'], true)) {
        return [
            'code' => 'error',
            'msg'  => [
                'reason'  => 'invalid_final_status',
                'message' => 'Invalid final status. Use canceled or expired.'
            ]
        ];
    }

    if (empty($subscription['id'])) {
        return [
            'code' => 'error',
            'msg'  => [
                'reason'  => 'subscription_not_found',
                'message' => 'Subscription not found.'
            ]
        ];
    }

    $subscription_id     = (int)$subscription['id'];
    $plan_id             = (int)($subscription['plan_id'] ?? 0);
    $user_id             = (int)($subscription['user_id'] ?? 0);
    $current_status      = trim(strtolower((string)($subscription['status'] ?? '')));
    $plan                = get_plan($plan_id);
    $proposal            = get_subscription_proposal($subscription_id, $debug);
    $deactivation_function = (string)($proposal['deactivation_function'] ?? ($plan['deactivation_function'] ?? ''));

    if (!empty($expected_current_status) && !in_array($current_status, $expected_current_status, true)) {
        return [
            'code' => 'error',
            'msg'  => [
                'reason'  => 'invalid_current_status',
                'message' => 'Subscription current status does not allow this finalization.',
                'status'  => $current_status
            ]
        ];
    }

    // Note: this only ever writes `cancel_flow` (the system-set "how") and,
    // when passed in, `user_cancel_detail` -- the customer-set `cancel_reason`
    // (category) and `probability_return` columns are never touched here, so
    // whatever the customer already picked on the cancellation form survives
    // finalization untouched (see close_non_renewing_plan_subscriptions() below,
    // which is the caller that actually has those on the row already).
    $updated = update('tb_plan_user_subscriptions', [
        'data' => array_merge(
            [
                'status'      => $final_status,
                'auto_renew'  => 0,
                'ended_at'    => 'NOW()',
                'cancel_flow' => $reason,
                'canceled_at' => 'NOW()',
            ],
            $user_cancel_detail !== '' ? ['user_cancel_detail' => $user_cancel_detail] : []
        ),
        'where' => where_equal_id($subscription_id),
    ], false, $debug);


    if ($updated === false)
    {
        return [
            'code' => 'error',
            'msg'  => [
                'reason'  => 'failed_to_finalize_subscription',
                'message' => 'Failed to finalize subscription.'
            ]
        ];
    }

    if (!empty($subscription_id))
    {
        user_plan_function_manager('remove', $user_id, $plan_id, $debug);

        if (!empty($deactivation_function))
        {
            function_process(
                $deactivation_function,
                'deactivation_function',
                $subscription
            );
        }
    }


    /**
     *
     * Add order note if the params wre provided.
     *
     */
    if (!empty($params['order_id']) && !empty($params['order_status_id']) && !empty($params['payment_slug']))
    {
        $payment_slug = $params['payment_slug'];

        $subscription_note = "The customer's SUBSCRIPTION (**#{$subscription['id']} {$plan['name']}**) has been **CANCELED** because the order status turned into **{$payment_slug}**.";
        add_order_note([
            'order_id'  => $params['order_id'],
            'content'   => $subscription_note,
            'note_type' => "order_{$payment_slug}",
            'body_type' => 'alert'
        ]);
    }

    return [
        'code' => 'success',
        'subscription_id' => $subscription_id,
        'msg' => [
            'reason'       => 'subscription_finalized',
            'message'      => 'Subscription finalized successfully.',
            'final_status' => $final_status,
        ]
    ];
}


/**
 * List subscriptions whose grace period has already ended.
 *
 * @param string|null $reference_date Reference date in Y-m-d format. Defaults to today.
 * @param bool        $debug          Print SQL query when true.
 *
 * @return array
 */
function get_plan_subscriptions_with_expired_grace(?string $reference_date = null, bool $debug = false): array
{
    $reference_date = !empty($reference_date) ? trim($reference_date) : date('Y-m-d');
    $reference_date_sql = addslashes($reference_date);

    $subscriptions = get_results("
        SELECT *
        FROM tb_plan_user_subscriptions
        WHERE auto_renew = '1'
          AND status = 'past_due'
          AND grace_ends_at IS NOT NULL
          AND grace_ends_at < '{$reference_date_sql}'
        ORDER BY grace_ends_at ASC, id ASC
    ", false, $debug);

    return !empty($subscriptions) ? $subscriptions : [];
}

/**
 * Close all subscriptions whose grace period has ended.
 *
 * Default policy:
 * - mark as expired when grace period ends automatically
 *
 * @param string|null $reference_date Reference date in Y-m-d format. Defaults to today.
 * @param string      $final_status   Final status: expired or canceled.
 * @param bool        $debug          Print debug information when true.
 *
 * @return array
 */
function close_expired_grace_plan_subscriptions(bool $debug = false): array
{
    $subscriptions = get_plan_subscriptions_with_expired_grace();

    $results = [
        'processed' => 0,
        'success' => [],
        'errors' => [],
    ];

    foreach ($subscriptions as $subscription)
    {
        $subscription_id = (int)($subscription['id'] ?? 0);

        if ($subscription_id <= 0) {
            continue;
        }

        // dump($subscription);
        // die;

        $result = finalize_plan_subscription([
            'subscription'            => $subscription,
            'final_status'            => 'expired',
            'reason'                  => 'grace_period_ended',
            'expected_current_status' => ['past_due'],
        ], $debug);

        $results['processed']++;

        if (($result['code'] ?? '') === 'success') {
            $results['success'][] = $result;
        } else {
            $results['errors'][] = [
                'subscription_id' => $subscription_id,
                'response' => $result,
            ];
        }
    }

    return $results;
}

/**
 * List subscriptions the customer has already asked NOT to renew (auto_renew
 * was turned off, e.g. via plans-subscriptions' cancel-subscription REST
 * route) whose current billing period has actually run out.
 *
 * That route deliberately only flips auto_renew off and leaves `status`
 * alone, so the customer keeps their benefits through the period they
 * already paid for -- this is what later finalizes the row once that
 * period is over. Falls back to next_billing_at when current_period_end
 * isn't set (e.g. a still-trialing subscription).
 *
 * @param string|null $reference_date Reference datetime (Y-m-d H:i:s). Defaults to now.
 * @param bool        $debug          Print SQL query when true.
 *
 * @return array
 */
function get_non_renewing_plan_subscriptions_past_period(?string $reference_date = null, bool $debug = false): array
{
    $reference_date = !empty($reference_date) ? trim($reference_date) : date('Y-m-d H:i:s');
    $reference_date_sql = addslashes($reference_date);

    $subscriptions = get_results("
        SELECT *
        FROM tb_plan_user_subscriptions
        WHERE auto_renew = '0'
          AND status IN ('active', 'trialing', 'past_due')
          AND (
                (current_period_end IS NOT NULL AND current_period_end < '{$reference_date_sql}')
             OR (current_period_end IS NULL AND next_billing_at IS NOT NULL AND next_billing_at < '{$reference_date_sql}')
          )
        ORDER BY id ASC
    ", false, $debug);

    return !empty($subscriptions) ? $subscriptions : [];
}

/**
 * Finalizes (as 'canceled') every subscription found by
 * get_non_renewing_plan_subscriptions_past_period() above.
 *
 * Kept as a separate pass from close_expired_grace_plan_subscriptions()
 * on purpose -- that one only ever looks at auto_renew = 1 rows that
 * failed to renew (grace ran out) and always lands on 'expired'; this one
 * only ever looks at auto_renew = 0 rows that simply ran out a period the
 * customer chose not to renew, and always lands on 'canceled'. Passes the
 * row's own cancel_flow/user_cancel_detail straight through to
 * finalize_plan_subscription() so whatever was already stored at
 * cancellation time isn't overwritten by a generic reason here -- the
 * customer's own `cancel_reason` (category) and `probability_return`
 * columns are untouched by finalize_plan_subscription() entirely, so they
 * survive automatically without needing to be passed through at all.
 *
 * @param bool $debug Print debug information when true.
 *
 * @return array
 */
function close_non_renewing_plan_subscriptions(bool $debug = false): array
{
    $subscriptions = get_non_renewing_plan_subscriptions_past_period(null, $debug);

    $results = [
        'processed' => 0,
        'success' => [],
        'errors' => [],
    ];

    foreach ($subscriptions as $subscription)
    {
        $subscription_id = (int)($subscription['id'] ?? 0);

        if ($subscription_id <= 0) {
            continue;
        }

        $result = finalize_plan_subscription([
            'subscription'            => $subscription,
            'final_status'            => 'canceled',
            'reason'                  => $subscription['cancel_flow'] ?: 'auto_renew_disabled',
            'user_cancel_detail'      => $subscription['user_cancel_detail'] ?? '',
            'expected_current_status' => ['active', 'trialing', 'past_due'],
        ], $debug);

        $results['processed']++;

        if (($result['code'] ?? '') === 'success') {
            $results['success'][] = $result;
        } else {
            $results['errors'][] = [
                'subscription_id' => $subscription_id,
                'response' => $result,
            ];
        }
    }

    return $results;
}
