<?php
if (!isset($seg)) exit;

register_rest_route('create-order', [
  'methods'  => ['POST'],
  'callback' => function ()
  {
    global $config, $current_user;

    $api_status = get_system_info('pyrosales_api_status');
    if ($api_status != 'active') {
      return invalid_permission_response();
    }

    // $permission = load_permission('order-manager', 'custom');
    // if (!$permission) {
    //   return invalid_permission_response();
    // }

    // 1) Prefer form-data / x-www-form-urlencoded
    $payload = $_POST;

    // 2) If empty, try JSON body
    if (empty($payload))
    {
      $raw = file_get_contents('php://input');
      $raw = trim((string) $raw);

      $payload = $raw !== '' ? json_decode($raw, true) : null;

      if (!is_array($payload) || empty($payload))
      {
        return [
          'code' => 'error',
          'detail' => [
            'type' => 'toast',
            'msg'  => [
              'color' => 'danger',
              'close_button' => true,
              'title' => 'Erro!',
              'body' => 'Invalid payload'
            ],
          ],
        ];
      }
    }

    return create_order($payload);
  },
  'permission_callback' => '__return_true',
]);


register_rest_route('update-order-status', [
  'methods'  => ['POST'],
  'callback' => function ()
  {
    global $config;

    // $permission = load_permission('order-manager', 'custom');
    // if (!$permission) {
    //   return invalid_permission_response();
    // }

    // 1) Prefer form-data / x-www-form-urlencoded
    $payload = $_POST;

    // 2) If empty, try JSON body
    if (empty($payload))
    {
      $raw = file_get_contents('php://input');
      $raw = trim((string) $raw);

      $payload = $raw !== '' ? json_decode($raw, true) : null;

      if (!is_array($payload) || empty($payload))
      {
        return [
          'code' => 'error',
          'detail' => [
            'type' => 'toast',
            'msg'  => [
              'color' => 'danger',
              'close_button' => true,
              'title' => 'Erro!',
              'body' => 'Invalid payload'
            ],
          ],
        ];
      }
    }

    return create_order($payload);
  },
  'permission_callback' => '__return_true',
]);


register_rest_route('process-order', [
  'methods'  => ['POST'],
  'callback' => function ()
  {
    global $current_user;

    $payload = $_POST;

    // 2) If empty, try JSON body
    if (empty($payload))
    {
      $raw = file_get_contents('php://input');
      $raw = trim((string) $raw);

      $payload = $raw !== '' ? json_decode($raw, true) : null;
    }

    if (empty($payload))
    {
      return [
        'code' => 'error',
        'detail' => [
          'type' => 'toast',
          'msg'  => [
            'color' => 'danger',
            'close_button' => true,
            'title' => 'Erro!',
            'body' => 'Invalid payload'
          ],
        ],
      ];
    }

    if (is_user_logged_in()) {
      $payload['user_id'] = $current_user['id'];
    } else {
      $payload['create-user'] = true;
    }

    $payload['process-payment'] = true;

    return create_order($payload);
  },
  'permission_callback' => '__return_true',
]);


register_rest_route('view-payment', [
  'methods'  => ['GET'],
  'callback' => function ()
  {
    global $current_user;

    $permission = load_permission('order-manager', 'custom');
    if (!$permission) {
      return invalid_permission_response();
    }

    $payment_id = isset($_GET['payment_id']) ? (int)$_GET['payment_id'] : 0;
    if ($payment_id <= 0) {
      return ['code' => 'error', 'message' => 'payment_id is required'];
    }

    $payment = get_result("SELECT * FROM tb_order_payments WHERE id = '{$payment_id}' LIMIT 1");
    if (!$payment) {
      return ['code' => 'error', 'message' => 'Payment not found'];
    }

    $payment = block('modal', [
      'id' => 'view-payment',
      'title' => 'Payment detail',
      'close_button' => true,
      'body' => payment_details($payment),
    ]);

    return [
      'code' => 'success',
      'payment' => $payment,
    ];
  },
  'permission_callback' => '__return_true',
]);


register_rest_route('view-my-payment', [
  'methods'  => ['GET'],
  'callback' => function ()
  {
    global $current_user;

    $user_id = (int)($current_user['id'] ?? 0);
    if ($user_id <= 0) {
      return ['code' => 'error', 'message' => 'Not authenticated'];
    }

    $payment_id = isset($_GET['payment_id']) ? (int)$_GET['payment_id'] : 0;
    if ($payment_id <= 0) {
      return ['code' => 'error', 'message' => 'payment_id is required'];
    }

    // Must belong to an order owned by current user
    $sql = "
      SELECT p.*
      FROM tb_order_payments p
      INNER JOIN tb_orders o ON o.id = p.order_id
      WHERE p.id = '{$payment_id}'
        AND o.user_id = '{$user_id}'
      LIMIT 1
    ";

    $payment = get_result($sql);

    if (!$payment) {
      return ['code' => 'error', 'message' => 'Payment not found or not allowed'];
    }

    $payment = block('modal', [
      'id' => 'view-payment',
      'title' => 'Payment detail',
      'close_button' => true,
      'body' => payment_details($payment),
    ]);

    return [
      'code' => 'success',
      'payment' => $payment,
    ];
  },
  'permission_callback' => '__return_true',
]);



register_rest_route('checkout-amount', [
  'methods'  => ['GET'],
  'callback' => function ()
  {
    $regular = 0.0;
    $sale    = 0.0;

    // $_GET['plan_id'] = 36;

    // Defaults (system-level)
    $fee_mode = (string)get_system_info('fee_mode');
    if (!$fee_mode) $fee_mode = 'merchant';

    $max_no_interest = (int)get_system_info('max_interest_free_installments');
    if ($max_no_interest < 1) $max_no_interest = 1;

    $surcharge_percent = (float)str_replace(',', '.', (string)get_system_info('surcharge_percent'));
    $surcharge_fixed   = (float)str_replace(',', '.', (string)get_system_info('surcharge_fixed'));

    /**
     *  Load price (plan/product) + allow override per item
     */
    if (!empty($_GET['plan_id']))
    {
      $plan_id = (int)$_GET['plan_id'];

      // Note: if you don't have these override columns yet, remove them from SELECT
      $row = get_result("
        SELECT
          regular_price, sale_price, fee_mode
        FROM tb_user_roles
        WHERE id = '{$plan_id}' AND type = 'plan'
        LIMIT 1
      ");

      if (!$row) return ["code" => "error", "msg" => "Plan not found."];

      $regular = (float)($row['regular_price'] ?? 0);
      $sale    = (float)($row['sale_price'] ?? 0);

      // Overrides (plan wins over system when filled)
      // $max_no_interest = 1;
      if (!empty($row['fee_mode'])) $fee_mode = (string)$row['fee_mode'];
    }

    elseif (!empty($_GET['product_id']))
    {
      $product_id = (int)$_GET['product_id'];

      // Note: if you don't have these override columns yet, remove them from SELECT
      $row = get_result("
        SELECT
          regular_price, sale_price,
          fee_mode, max_interest_free_installments
        FROM tb_products
        WHERE id = '{$product_id}'
        LIMIT 1
      ");

      if (!$row) return ["code" => "error", "msg" => "Product not found."];

      $regular = (float)($row['regular_price'] ?? 0);
      $sale    = (float)($row['sale_price'] ?? 0);

      // Overrides (product wins over system when filled)
      if (!empty($row['fee_mode'])) $fee_mode = (string)$row['fee_mode'];
      if (!empty($row['max_interest_free_installments'])) $max_no_interest = (int)$row['max_interest_free_installments'];
    }

    else
    {
      // TODO: cart pricing
      return ["code" => "error", "msg" => "Missing plan_id or product_id."];
    }

    /**
     *  Base amount
     */
    if ($sale > 0) {
      $amount_base = ($regular > 0) ? min($regular, $sale) : $sale;
    } else {
      $amount_base = $regular;
    }
    if ($amount_base < 0) $amount_base = 0.0;

    /**
     *  Calculate (system defaults + item overrides)
     */
    $calc = checkout_amount_config([
      'amount_base' => $amount_base,
      'fee_mode' => $fee_mode,
      'max_no_interest' => $max_no_interest,
      'surcharge_percent' => $surcharge_percent,
      'surcharge_fixed'   => $surcharge_fixed,
    ]);

    return array_merge(["code" => "success"], $calc);
  },
  'permission_callback' => '__return_true',
]);
