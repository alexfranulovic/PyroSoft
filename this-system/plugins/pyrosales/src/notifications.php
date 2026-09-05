<?php
if(!isset($seg)) exit;

require_once __DIR__ .'/status.php';

/**
 * Handle PagBank legacy order notification and translate it to the CMS.
 *
 * @param bool  $debug
 * @return array
 */
function order_notification(array $params = [], bool $debug = false): array
{
  $gateway = !empty($params['gateway']) ? $params['gateway'] : null;
  if (is_null($gateway)) {
    return ['code' => 'error', 'detail' => 'No gateway provided.'];
  }

  $hash = !empty($params['hash']) ? $params['hash'] : null;
  if (is_null($hash)) {
    return ['code' => 'error', 'detail' => 'The payment hash was not provided.'];
  }

  $order = get_result("
    SELECT
      ord.user_id,
      ord.order_type,
      ord.order_purpose,
      ord.subscription_id,
      ord.vendor_id,
      ord.commission_activation_function,
      ord.commission_status_id,
      ord.id AS order_id,
      ord.status_id AS order_status_id,
      pay.provider_reference,
      pay.payment_hash,
      pay.provider_order_id,
      pay.provider_payment_id,


      pay.user_payment_method_id,
      pay.statement_descriptor,


      pay.status_id AS payment_status_id
      FROM tb_orders AS ord
      INNER JOIN tb_order_payments AS pay ON pay.order_id = ord.id
      WHERE pay.payment_hash = '{$hash}'
      ORDER BY pay.id ASC
      LIMIT 1
  ", false, $debug);

  $params['order_data'] = $order;

  $load_gateway_handler = "{$gateway}_load_notifications";
  if (function_exists($load_gateway_handler)) {
    $load_gateway_handler();
  }

  $gateway_handler = "{$gateway}_handle_order_notification";
  $notification_res = ['code' => 'error'];
  if (function_exists($gateway_handler)) {
    $notification_res = $gateway_handler($params);
  }

  if ($debug) {
    print_r($notification_res);
  }

  $notification_data = ($notification_res['code'] == 'success')
    ? ($notification_res['data'] ?? [])
    : [];

  $reference = $notification_data['reference'] ?? '';

  $order_id = (!empty($order['provider_reference']) && $order['provider_reference'] == $reference)
    ? ($order['order_id'] ?? '')
    : '';

  if (empty($order_id))
  {
    http_response_code(403);
    return [
      'code' => 'error',
      'msg'  => [
        'reason'  => 'order_not_found',
        'message' => 'Internal payment was not found from notification reference.',
      ],
    ];
  }

  /**
   * Save the status change.
   */
  $old_payment_status_id = $order['payment_status_id'];
  $new_payment_status_id = (int)($notification_data['status_id']??0);


  $old_order_status_id = $order['order_status_id'];
  $new_order_status_id = payment_to_order_status([
    'payment_status' => $new_payment_status_id,
    'purpose' => $order['order_purpose'],
  ]);


  /**
   * Veriy if it was already processed.
   */
  // if ($old_order_status_id == $new_order_status_id)
  // {
  //   return [
  //     'code' => 'success',
  //     'msg'  => [
  //       'reason'  => 'already_changed',
  //       'message' => 'Already processed.',
  //     ],
  //   ];
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
      'order_id'  => $order_id,
      'title'     => 'Status updated',
      'content'   => "Order status changed from **\"{$msg_old_order_status}\"** to **\"{$msg_new_order_status}\"**. -br Payment status changed from **\"{$msg_old_payment_status}\"** to \"{$msg_new_payment_status}\".",
      'note_type' => 'status_change',
      'body_type' => 'alert'
    ]);
  }

  /**
   * Log the gateway response.
   */
  add_order_note([
    'order_id'  => $order_id,
    'title'     => 'Post-transactional notification',
    'content'   => $notification_res['data'],
    'note_type' => 'json_response',
    'body_type' => 'accordion'
  ]);


  /**
   *
   * 7) (Only order type plan) Activate a plan
   *
   */
  if ($order['order_type'] == 'plan' && $statuses_changed)
  {
    $plan = get_result("
      SELECT
        plan.*
      FROM tb_plans AS plan
      LEFT JOIN tb_order_items AS item ON item.plan_id = plan.id
      LEFT JOIN tb_orders AS ord ON ord.id = item.order_id
      WHERE
        ord.id = '{$order_id}'
      LIMIT 1
    ", false, $debug);


    $order_purpose = ($plan['trial_days'] > 0 )
      ? 'trial_validation'
      : $order['order_purpose'];

    if ($payment_status_changed)
    {
      /**
       * ORDER status paid AND payment status paid.
       */
      if ($new_order_status_id == 3 && $new_payment_status_id == 2)
      {
        activate_order_plan_subscription([
          'order_id'               => $order_id,
          'user_id'                => $order['user_id'],
          'plan'                   => $plan,
          'order_purpose'          => $order_purpose,
          'user_payment_method_id' => $order['user_payment_method_id'] ?? null,
          'statement_descriptor'   => $order['statement_descriptor'] ?? null,
        ], $debug);
      }

      /**
       * ORDER status canceled, refunded & chargeback
       */
      elseif (
        $new_order_status_id == 4 ||        // canceled
        $new_order_status_id == 6 ||        // refunded
        $new_order_status_id == 7           // chargeback
      ){
        $payment_slug  = get_status_slug_by_id('order_status', (int)$new_order_status_id);
        $cancel_reason = "order_is_{$payment_slug}";

        finalize_plan_subscription([
          'payment_slug'            => $payment_slug,
          'order_id'                => $order_id,
          'order_status_id'         => $new_order_status_id,
          'subscription_id'         => (int) ($order['subscription_id'] ?? 0),
          'final_status'            => 'canceled',
          'reason'                  => $cancel_reason,
          'expected_current_status' => ['active', 'paused', 'trialing'],
        ], $debug);
      }
    }
  }


  /**
   *
   * 8) (Only order type one_off) Activation/deactivation via activation_function
   * /deactivation_function frozen on each tb_order_items row -- same principle
   * used above for plans, adapted to the one-off cart item.
   *
   */
  if ($order['order_type'] == 'one_off' && $statuses_changed)
  {
    $one_off_items = get_results("
      SELECT *
      FROM tb_order_items
      WHERE order_id = '{$order_id}'
        AND item_type = 'one_off'
    ", false, $debug);

    if ($payment_status_changed)
    {
      /**
       * ORDER status paid AND payment status paid.
       */
      if ($new_order_status_id == 3 && $new_payment_status_id == 2)
      {
        foreach (($one_off_items ?? []) as $order_item)
        {
          if (empty($order_item['activation_function'])) continue;

          function_process(
            $order_item['activation_function'],
            'activation_function',
            [
              'order'       => array_merge($order, ['id' => $order_id]),
              'items_lines' => $one_off_items,
            ]
          );
        }

        // Payment confirmed later (e.g. Pix paid after the checkout page was
        // closed) -- the cart cookie already did its job.
        clear_one_off_cart_item();
      }

      /**
       * ORDER status canceled, refunded & chargeback
       */
      elseif (
        $new_order_status_id == 4 ||        // canceled
        $new_order_status_id == 6 ||        // refunded
        $new_order_status_id == 7           // chargeback
      ){
        foreach (($one_off_items ?? []) as $order_item)
        {
          if (empty($order_item['deactivation_function'])) continue;

          function_process(
            $order_item['deactivation_function'],
            'deactivation_function',
            [
              'order'       => array_merge($order, ['id' => $order_id]),
              'items_lines' => $one_off_items,
            ]
          );
        }
      }
    }
  }


  /**
   *
   * 8.5) (Vendor commission) Settle the seller's commission once the
   * gateway confirms the order is paid -- mirrors step 8.3 of
   * create_order(). Runs tb_orders.commission_activation_function and
   * moves commission_status_id 'pending' -> 'complete' on success; it
   * stays 'pending' otherwise. No-op without a vendor or a function.
   * See resolve_order_commission_status() (src/status.php).
   *
   */
  if (
    !empty($order['vendor_id'])
    && $new_order_status_id == 3
    && $new_payment_status_id == 2
  ){
    resolve_order_commission_status(
      array_merge($order, ['id' => $order_id]),
      ['notification' => $notification_data]
    );
  }


  /**
   * Example: update the latest PagBank payment row for this order.
   * Adapt to your exact table/fields if needed.
   */
  // $payment_update_data = [
  // ];

  $fee_fields = [
    'status_id'           => $new_payment_status_id,
    'provider_payment_id' => $notification_data['provider_payment_id'] ?? null,
    // 'provider_order_id'   => $notification_data['provider_order_id'] ?? null,
    'provider_type_code'  => $notification_data['provider_status_code'] ?? null,
    'updated_at'          => 'NOW()',


    'net_amount'               => $notification_data['net_amount'] ?? null,
    'gateway_fee'              => $notification_data['gateway_fee'] ?? null,
    'installment_fee_amount'   => $notification_data['installment_fee_amount'] ?? null,
    'installment_rate_amount'  => $notification_data['installment_rate_amount'] ?? null,
  ];

  foreach ($fee_fields as $field => $value)
  {
    if (!is_null($value) && !empty($value)) {
      $payment_update_data[$field] = $value;
    }
  }

  update('tb_order_payments', [
    'data'  => $payment_update_data ?? [],
    'where' => [
      ['field' => 'provider_reference', 'operator' => '=', 'value' => $reference],
      ['field' => 'provider',           'operator' => '=', 'value' => 'pagbank'],
    ],
  ], false, $debug);


  /**
   * Optional: reflect payment status on the order itself.
   * Adapt your mapping if your tb_orders.status_id uses different ids.
   */
  $order_update_data = [
    'status_id'  => $new_order_status_id,
    'updated_at' => 'NOW()',
  ];

  if (($notification_data['net_amount'] ?? null) !== null) {
    $order_update_data['net_amount'] = $notification_data['net_amount'];
  }

  if (($notification_data['gateway_fee'] ?? null) !== null) {
    $order_update_data['gateway_fee'] = $notification_data['gateway_fee'];
  }

  update('tb_orders', [
    'data'  => $order_update_data,
    'where' => where_equal_id($order_id),
  ], false, $debug);

  return [
    'code' => 'success',
    // 'data' => $notification_res
  ];
}
