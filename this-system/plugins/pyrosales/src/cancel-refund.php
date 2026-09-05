<?php
if (!isset($seg)) exit;

/**
 * Cancel or refund a payment.
 *
 * Accepts:
 * - payment_id
 * - payment (full array)
 *
 * @param array $params
 * @param bool  $debug
 * @return array
 */
function cancel_refund(array $params, bool $debug = false)
{
    global $seg;

    // Error treatment.
    $error          = false;
    $msg_code       = 'ER_TO_CANCEL_OR_REFUND_PAYMENT';
    $msg_container  = 'toast';


    // Payment params.
    $payment       = (array) ($params['payment'] ?? []);
    $payment_id    = (string) ($params['payment_id'] ?? '');
    $wanted_refund_amount = isset($params['to_refund_amount']) && $params['to_refund_amount'] !== ''
        ? (float) DECIMAL($params['to_refund_amount'])
        : 0;
    $to_refund_amount = $wanted_refund_amount;


    // Priority: full payment object
    if (!empty($payment) && !empty($payment['id'])) {
        $payment_id = (string) $payment['id'];
    }


    // Fallback: fetch by ID
    elseif (!empty($payment_id))
    {
        $payment = get_result("
            SELECT *
            FROM tb_order_payments
            WHERE
                id = '" . addslashes($payment_id) . "'
                OR provider_payment_id = '" . addslashes($payment_id) . "'
            LIMIT 1
        ");
    }

    else
    {
        $error = true;
        $error_reason = [
            'code' => 'error',
            'detail'  => [
                'code'  => 'missing_payment',
                'msg' => 'Provide payment_id or payment object.'
            ]
        ];
    }

    // Final validation
    if (empty($payment['id']))
    {
        $error = true;
        $error_reason = [
            'code' => 'error',
            'detail'  => [
                'code'  => 'payment_not_found',
                'msg' => 'Payment not found.'
            ]
        ];
    }

    /**
     * Get order.
     */
    $order = get_result("
        SELECT
            user_id,
            order_type,
            subscription_id,
            status_id,
            refunded_amount,
            total_amount
        FROM tb_orders
        WHERE id = '{$payment['order_id']}'
        LIMIT 1
    ");

    // Snapshot of both refunded_amount values BEFORE this action, kept separate:
    // the order's is the sum across all its payments; the payment's is its own history.
    $order_refunded_amount_before   = (float) $order['refunded_amount'];
    $payment_refunded_amount_before = (float) ($payment['refunded_amount'] ?? 0);

    if ($wanted_refund_amount == 0) {
        $to_refund_amount = (float) ($order['total_amount'] - $order['refunded_amount']);
    }

    // Order-level snapshot: previous order refunded_amount + this action's amount.
    $new_order_refunded_amount = number_format($order_refunded_amount_before + $to_refund_amount, 2);

    // Payment-level snapshot: previous PAYMENT refunded_amount + this action's amount.
    $new_payment_refunded_amount = number_format($payment_refunded_amount_before + $to_refund_amount, 2);

    if ($new_order_refunded_amount > $order['total_amount'])
    {
        $error = true;
        $error_reason = [
            'code' => 'error',
            'detail'  => [
                'code'  => 'refund_value_is_bigger_than_amount',
                'msg' => 'The refund amount is greater than the principal amount.'
            ]
        ];
    }

    if ($order['refunded_amount'] >= $order['total_amount'])
    {
        $error = true;
        $error_reason = [
            'code' => 'error',
            'detail'  => [
                'code'  => 'payment_already_full_refunded',
                'msg' => 'The payment is already full refunded.'
            ]
        ];
    }

    if ($order['total_amount'] !== null && $order['total_amount'] <= 0)
    {
        $error = true;
        $error_reason = [
            'code' => 'error',
            'detail'  => [
                'code' => 'invalid_amount',
                'msg' => 'Total amount must be greater than zero.',
            ],
        ];
    }

    $gateway = $payment['provider'] ?? '';
    $gateway_cancel_refund = "{$gateway}_cancel_refund";
    if (!function_exists($gateway_cancel_refund))
    {
        $error = true;
        $error_reason = [
            'code' => 'error',
            'detail'  => [
                'code'  => 'gateway_not_supported',
                'msg' => "Cancel/refund not implemented for {$gateway}."
            ]
        ];
    }

    /**
     *
     * Lights, camera & action.
     *
     */
    if (!$error)
    {
        $payment['total_amount']        = $order['total_amount'];
        $payment['refunded_amount']     = $new_payment_refunded_amount;
        $payment['to_refund_amount']    = $to_refund_amount;
        $gateway_response                       = $gateway_cancel_refund($payment, $debug);
    }

    /**
     * Return error treatment.
     */
    if ($gateway_response['code'] == 'error' OR $error)
    {
        $alert_message         = $GLOBALS['alerts'][$msg_code];
        $alert_message['body'].= !empty($error_reason['detail']['msg'])
            ? $error_reason['detail']['msg']
            : ($gateway_response['detail']['msg'] ?? '');

        if ($debug) {
            print_r($gateway_response);
        }

        return [
            'code' => 'error',
            'detail' => [
                'type' => 'toast',
                'msg' => alert_message($alert_message, $msg_container),
                'code' => $msg_code,
            ],
        ];
    }

    elseif ($gateway_response['code'] == 'success')
    {
        /**
         *
         * Save the status change.
         *
         */
        /**
         * Save the payment status change.
         */
        $old_payment_status_id = $payment['status_id'];
        $new_payment_status_id = ($gateway_response['data']['flow'] == 'cancel_authorized')
            ? 5
            : (($gateway_response['data']['flow'] == 'full_refunded') ? 4 : 6);

        /**
         * Save the order status change.
         */
        $old_order_status_id = $order['status_id'];
        $new_order_status_id = payment_to_order_status([
            'payment_status' => $new_payment_status_id,
        ]);

        /**
         * Veriy if it was already processed.
         */
        // if ($old_order_status_id == $new_order_status_id)
        // {
        //     $error = true;
        //     return [
        //         'code' => 'success',
        //         'msg'  => [
        //             'reason'  => 'already_changed',
        //             'message' => 'Already processed.',
        //         ],
        //     ];
        // }

        $msg_old_payment_status = general_stats($old_payment_status_id, 'payment_status', 'name');
        $msg_new_payment_status = general_stats($new_payment_status_id, 'payment_status', 'name');
        $msg_old_order_status   = general_stats($old_order_status_id, 'order_status', 'title');
        $msg_new_order_status   = general_stats($new_order_status_id, 'order_status', 'title');

        $payment_status_changed = ($msg_old_payment_status != $msg_new_payment_status);
        $order_status_changed   = ($msg_old_order_status != $msg_new_order_status);
        $statuses_changed       = $payment_status_changed || $order_status_changed;


        /**
         * Log the gateway response.
         */
        if ($statuses_changed)
        {
            add_order_note([
                'order_id'  => $payment['order_id'],
                'title'     => 'Status updated',
                'content'   => "Payment status changed from \"{$msg_old_payment_status}\" to \"{$msg_new_payment_status}\". -br Order status changed from \"{$msg_old_order_status}\" to \"{$msg_new_order_status}\".",
                'note_type' => 'status_change',
                'body_type' => 'alert'
            ]);
        }


        /**
         * Log the gateway response.
         */
        $content = $gateway_response['data'];
        add_order_note([
            'order_id'  => $payment['order_id'],
            'title'     => 'Cancel/Refund response',
            'content'   => $content ?? [],
            'note_type' => 'json_response',
            'body_type' => 'accordion'
        ]);


        /**
         *
         * (Only order type plan) Activate a plan
         *
         */
        if ($order['order_type'] == 'plan' && $payment_status_changed)
        {
            /**
             * ORDER status canceled, refunded & chargeback
             */
            if (
                $new_order_status_id == 4 ||        // canceled
                $new_order_status_id == 6 ||        // refunded
                $new_order_status_id == 7           // chargeback
            ){
                $payment_slug  = get_status_slug_by_id('order_status', (int)$new_order_status_id);
                $cancel_reason = "order_is_{$payment_slug}";

                finalize_plan_subscription([
                    'payment_slug'            => $payment_slug,
                    'order_id'                => $payment['order_id'],
                    'order_status_id'         => $new_order_status_id,
                    'subscription_id'         => (int) ($order['subscription_id'] ?? 0),
                    'final_status'            => 'canceled',
                    'reason'                  => $cancel_reason,
                    'expected_current_status' => ['active', 'paused', 'trialing'],
                ], $debug);
            }
        }

        if ($order['order_type'] == 'one_off' && $payment_status_changed)
        {
            /**
             * ORDER status canceled, refunded & chargeback
             */
            if (
                $new_order_status_id == 4 ||        // canceled
                $new_order_status_id == 6 ||        // refunded
                $new_order_status_id == 7           // chargeback
            ){
                $items_lines = get_results("
                    SELECT *
                    FROM tb_order_items
                    WHERE order_id = '{$payment['order_id']}'
                      AND item_type = 'one_off'
                ", false, $debug);

                foreach (($items_lines ?? []) as $order_item)
                {
                    if (empty($order_item['deactivation_function'])) continue;

                    function_process(
                        $order_item['deactivation_function'],
                        'deactivation_function',
                        [
                            'order'       => array_merge($order, ['id' => (int) $payment['order_id']]),
                            'items_lines' => $items_lines,
                        ]
                    );
                }
            }
        }


        /**
         * Update the payment.
         */
        // Prefer whatever the gateway reports as this payment's actual refunded_amount;
        // fall back to our own computed payment-level snapshot.
        $final_payment_refunded_amount = !empty($gateway_response['data']['refunded_amount'])
            ? $gateway_response['data']['refunded_amount']
            : $new_payment_refunded_amount;

        $args = [
            'data' => [
                'status_id' => $new_payment_status_id,
                'refunded_amount' => $final_payment_refunded_amount,
            ],
            'where' => where_equal_id($payment['id'])
        ];
        update('tb_order_payments', $args, false, $debug);

        /**
         * Update the order.
         */
        $args = [
            'data' => [
                'status_id' => $new_order_status_id,
                'refunded_amount' => $new_order_refunded_amount,
            ],
            'where' => where_equal_id($payment['order_id'])
        ];
        update('tb_orders', $args, false, $debug);
    }

    $response = [
        'code' => 'success',
    ];

    // Add redirect
    if (!$error) {
        $response['redirect'] = '{force_reload}';
    }

    return $response ?? [];
    // return $gateway_response ?? [];
}
