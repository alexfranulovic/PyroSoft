<?php
if (!isset($seg)) exit;

define('DEBUG_API_PYROSALES', true);
// define('DEBUG_API_PYROSALES', false);

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
    // if (!DEBUG_API_PYROSALES && !$permission) {
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


register_rest_route('order-note-manager', [
  'methods'  => ['POST', 'GET'],
  'callback' => function ()
  {
    $current_user = $GLOBALS['current_user']?? [];

    $permission = load_permission('order-manager', 'custom');
    if (!DEBUG_API_PYROSALES && !$permission) {
      return invalid_permission_response();
    }

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    // -----------------------------------------------------------
    // GET: list notes for an order
    // -----------------------------------------------------------
    if ($method === 'GET')
    {
      $order_id = (int) ($_GET['order_id'] ?? 0);

      if ($order_id <= 0) {
        return ['code' => 'error', 'msg' => ['reason' => 'Missing order_id']];
      }

      $notes = get_order_notes([
        'order_id'     => $order_id,
        'show_private' => true,
        'note_type'    => [],
      ]);

      return ['code' => 'success', 'notes' => $notes];
    }

    // -----------------------------------------------------------
    // POST: create a new note (private or customer-facing)
    // -----------------------------------------------------------
    $payload = $_POST;

    if (empty($payload))
    {
      $raw = trim((string) file_get_contents('php://input'));
      $payload = $raw !== '' ? json_decode($raw, true) : null;
    }

    if (!is_array($payload) || empty($payload))
    {
      return [
        'code' => 'error',
        'detail' => [
          'type' => 'toast',
          'msg'  => alert_message('ER_TO_ADD_ORDER_NOTE', 'toast'),
          'code' => 'invalid_payload',
        ],
      ];
    }

    $order_id = (int) ($payload['order_id'] ?? 0);

    // The order must exist before a note can be attached to it.
    $order = get_order($order_id, false);
    if (empty($order)) {
      return [
        'code' => 'error',
        'detail' => [
          'type' => 'toast',
          'msg'  => alert_message('ER_TO_ADD_ORDER_NOTE', 'toast'),
          'code' => 'order_not_found',
        ],
      ];
    }

    // If whoever is authenticated IS the order owner, the note came from
    // the customer themselves -- it can only ever be customer-facing.
    $order_owner_id  = (int) ($order['order']['user_id'] ?? 0);
    $current_user_id = (int) ($current_user['id'] ?? 0);
    $is_customer_note = $order_owner_id > 0 && $current_user_id > 0 && $current_user_id === $order_owner_id;

    $visibility = in_array($payload['visibility'] ?? '', ['private', 'customer'], true)
      ? $payload['visibility']
      : 'private';

    if ($is_customer_note) {
      $visibility = 'customer';
    }

    $result = add_order_note([
      'order_id'   => $order_id,
      'content'    => $payload['content'] ?? '',
      'visibility' => $visibility,
      'note_type'  => 'manual',
      'created_by' => $current_user_id ?: null,
    ]);

    if (($result['code'] ?? '') !== 'success')
    {
      return [
        'code' => 'error',
        'detail' => [
          'type' => 'toast',
          'msg'  => alert_message('ER_TO_ADD_ORDER_NOTE', 'toast'),
          'code' => 'could_not_save_note',
        ],
      ];
    }

    $author = trim(($current_user['first_name'] ?? '') . ' ' . ($current_user['last_name'] ?? ''));

    return [
      'code' => 'success',
      'note' => [
        'id'            => $result['id'],
        'order_id'      => $order_id,
        'content'       => $payload['content'],
        'visibility'    => $visibility,
        'from_customer' => $is_customer_note,
        'author'        => $author !== '' ? $author : 'System',
        'created_at'    => date('Y-m-d H:i:s'),
      ],
    ];
  },
  'permission_callback' => '__return_true',
]);


register_rest_route('order-notification', [
  'methods'  => ['POST','GET','PATCH','PUT'],
  'callback' => function ()
  {
    global $config, $current_user, $seg;
    load_pyrosales_notifications();

    $gateway = !empty($_GET['gateway']) ? $_GET['gateway'] : null;
    if (is_null($gateway)) {
      return ['code' => 'error', 'detail' => 'No gateway provided.'];
    }

    $hash = !empty($_GET['hash']) ? $_GET['hash'] : null;
    if (is_null($hash)) {
      return ['code' => 'error', 'detail' => 'The payment hash was not provided.'];
    }

    // Genérico: qualquer gateway pode mandar JSON cru, form-urlencoded, etc.
    // $_POST só é populado nos dois últimos formatos — por isso capturamos o raw sempre.
    $raw_body = file_get_contents('php://input');

    $params              = $_REQUEST ?? [];
    $params['hash']      = $hash;
    $params['gateway']   = $gateway;
    $params['raw_body']  = $raw_body;
    $params['headers']   = $_SERVER; // cada gateway extrai os headers que precisar (assinatura, etc.)

    $notification_exec = order_notification($params);

    // Debuggers
    update_option('GET-order-notification', $_GET ?? []);
    update_option('POST-order-notification', $_POST ?? []);
    update_option('RAW-order-notification', $raw_body);
    update_option('SERVER-order-notification', $_SERVER ?? []);
    update_option('REQUEST-order-notification', $_REQUEST ?? []);
    $request_full = [
        'created_at' => date('d/m/Y - H:i:s'),
        'method'     => $_SERVER['REQUEST_METHOD'],
        'headers'    => $_SERVER,
        'params'     => $_GET ?? [],
        'body'       => $raw_body,
        'response'   => $notification_exec,
        'REQUEST'    => $_REQUEST ?? [],
    ];
    $request_full = json_encode($request_full, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    update_option('FULL-order-notification', $request_full);
    update_option('notification_exec-order-notification', $notification_exec);

    return $notification_exec;
  },
  'permission_callback' => '__return_true',
]);

register_rest_route('check-order-status', [
  'methods'  => ['POST', 'GET'],
  'callback' => function ()
  {
    $order_id = (int) ($_GET['order_id'] ?? 0);
    $user_id  = (int) ($_SESSION['current_user']['id'] ?? 0);

    $order = get_result("
    SELECT
      o.id,
      o.order_purpose,
      o.status_id,
      p.expires_at
    FROM `tb_orders` AS o
    LEFT JOIN tb_order_payments AS p ON p.order_id = o.id
    WHERE
      o.id = '{$order_id}'
      AND o.user_id = '{$user_id}'");

    if (empty($order))
    {
      $msg = block('modal', [
        'id' => 'payment-error-modal',
        'attributes' => 'data-modal:(true);',
        'size' => 'dialog-centered',
        'close_button' => true,
        'body' => "Esse pedido não existe ou você não fez.",
      ]);
      return [
        'code' => 'error',
        'detail' => [
          'type' => 'modal',
          'msg' => $msg,
        ]
      ];
    }

    $is_paid    = ($order['status_id'] == 3);
    $is_expired = (time() > strtotime($order['expires_at'] ?? ''));

    $res = [
      'code' => 'success',
      'is_paid' => $is_paid,
      'is_expired' => $is_expired,
      'time' => date('Y-m-d H:i:s'),
      'expires_at' => $order['expires_at'],
    ];

    $receipt_page_id = get_system_info('receipt_page_id');
    $receipt_url     = get_url_page($receipt_page_id, 'full');

    // Redirect to the receipt page.
    if ($is_paid && !$is_expired) {
      $res['redirect'] = "{$receipt_url}/{$order_id}";
    }

    return $res;
  },
  'need_login' => true,
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
    $payload['all_info'] = false;

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
    if (!DEBUG_API_PYROSALES && !$permission) {
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
      'size' => 'lg',
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
      'size' => 'lg',
      'title' => 'Payment detail',
      'close_button' => true,
      'body' => payment_details($payment),
    ]);

    return [
      'code' => 'success',
      'payment' => $payment,
    ];
  },
  'need_login' => true,
  'permission_callback' => '__return_true',
]);


register_rest_route('checkout-amount', [
  'methods'  => ['GET'],
  'callback' => function ()
  {
    global $seg;

    $regular = 0.0;
    $sale    = 0.0;

    require_once __DIR__ .'/src/helpers.php';

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
      $row = get_plan($plan_id);

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

    elseif (!empty($one_off_item = get_one_off_cart_item()))
    {
      $qty = (int)$one_off_item['quantity'];

      $regular = (float)$one_off_item['regular_unit_price'] * $qty;
      $sale    = (float)$one_off_item['unit_price'] * $qty;

      // Installments for one-off orders are gated by a dedicated setting --
      // when it's off, one-off checkouts are always charged in a single installment.
      $allow_one_off_installments = (string)get_system_info('pyrosales_one_off_allow_installments');
      if ($allow_one_off_installments !== '1') {
        $max_no_interest = 1;
      }
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


register_rest_route('one-off-cart', [
  'methods'  => ['GET', 'POST', 'DELETE'],
  'callback' => function ()
  {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    /**
     * GET
     * - no params -> just returns whatever is currently in the cart.
     */
    if ($method === 'GET') {
      return manage_one_off_cart('get');
    }

    /**
     * DELETE
     * - Drops the cart cookie (e.g. shopper backs out of the one-off checkout).
     */
    if ($method === 'DELETE') {
      return manage_one_off_cart('clear');
    }

    /**
     * POST
     * - action=set             -> { item_slug, quantity } -- item_slug must
     *                              match a key in $GLOBALS['one_off_catalog']
     *                              (populated by your own code, see
     *                              src/one-off-cart.php). Price/behavior
     *                              always come from the catalog entry, never
     *                              from the request, so this stays safe for
     *                              anonymous callers.
     * - action=update_quantity -> { quantity } (default action)
     * - action=clear
     */
    $payload = $_POST;

    if (empty($payload))
    {
      $raw = trim((string) file_get_contents('php://input'));
      $payload = $raw !== '' ? json_decode($raw, true) : [];
    }

    if (!is_array($payload)) $payload = [];

    $action = $payload['action'] ?? 'update_quantity';

    if ($action === 'set') {
      return manage_one_off_cart('set', $payload);
    }

    if ($action === 'clear') {
      return manage_one_off_cart('clear');
    }

    return manage_one_off_cart('update_quantity', [
      'quantity' => (int)($payload['quantity'] ?? 1),
    ]);
  },
  'permission_callback' => '__return_true',
]);


register_rest_route('cancel-order', [
  'methods'  => ['POST','GET','PATCH','PUT'],
  'callback' => function ()
  {
    global $config, $seg;

    $permission = load_permission('order-manager', 'custom');
    if (!DEBUG_API_PYROSALES && !$permission) {
      return invalid_permission_response();
    }

    $payment_id = !empty($_POST['payment_id']) ? $_POST['payment_id'] : null;
    $params['payment_id'] = $payment_id;

    if (!empty($_POST['to_refund_amount'])) {
      $params['to_refund_amount'] = $_POST['to_refund_amount'];
    }

    return cancel_refund(
      $params
      // , true
    );
  },
  // 'need_login' => true,
  'permission_callback' => '__return_true',
]);


/**
 * Reconstruct PHP's native $_POST bracket-array nesting
 * ("payment_data[token]", "payment_data[customer_address][street]", ...)
 * for the save-payment-method route below, when the payload instead
 * arrives as a FLAT object -- this happens when a `data-send-without-reload`
 * form (the CMS's own AJAX submit handler, added to
 * <form data-payment-method-form> in custom-pages/payment-methods.php) is
 * serialized as {name: value} pairs keyed by each field's literal `name`
 * attribute, instead of a real nested structure the way a native multipart
 * form submission (which PHP itself unflattens into $_POST) would produce.
 * Without this, `payment_data[token]` lands as a top-level key literally
 * named "payment_data[token]" -- pagbank_save_payment_method() never sees
 * `payment_data.token`, and PagBank's own API rejects the request with
 * "Either encrypted card token or provider_card_id must be provided."
 *
 * A no-op when the payload already arrived properly nested (native
 * multipart POST, or a JSON body built as a real nested object) -- none of
 * its keys contain literal brackets to match.
 *
 * @param array $payload
 * @return array
 */
function normalize_payment_method_payload(array $payload): array
{
    if (isset($payload['payment_data']) && is_array($payload['payment_data'])) {
        return $payload;
    }

    $payment_data = [];

    foreach ($payload as $key => $value)
    {
        if (!preg_match('/^payment_data\[([^\]]+)\](?:\[([^\]]+)\])?$/', (string)$key, $m)) {
            continue;
        }

        if (isset($m[2]) && $m[2] !== '') {
            $payment_data[$m[1]][$m[2]] = $value;
        } else {
            $payment_data[$m[1]] = $value;
        }
    }

    if (!empty($payment_data)) {
        $payload['payment_data'] = $payment_data;
    }

    return $payload;
}


/**
 * "Meus cartões" (custom-pages/payment-methods.php) -- CRUD routes.
 * All three are strictly scoped to the logged-in customer: save_payment_method()
 * / update_payment_method_status() (src/payment_methods.php) each re-check
 * user_id themselves, on top of the 'need_login' gate here, exactly like
 * view-my-payment above.
 */
register_rest_route('save-payment-method', [
  'methods'  => ['POST'],
  'callback' => function ()
  {
    global $current_user;

    if (empty($current_user['id'])) {
      return invalid_permission_response();
    }

    $payload = $_POST;

    if (empty($payload))
    {
      $raw = trim((string) file_get_contents('php://input'));
      $payload = $raw !== '' ? json_decode($raw, true) : null;
    }

    if (!is_array($payload) || empty($payload))
    {
      return [
        'code' => 'error',
        'detail' => [
          'type' => 'toast',
          'msg'  => alert_message('ER_TO_SAVE_PAYMENT_METHOD', 'toast'),
          'code' => 'invalid_payload',
        ],
      ];
    }

    return save_payment_method(normalize_payment_method_payload($payload));
  },
  'need_login' => true,
  'permission_callback' => '__return_true',
]);


register_rest_route('set-default-payment-method', [
  'methods'  => ['POST'],
  'callback' => function ()
  {
    global $current_user;

    if (empty($current_user['id'])) {
      return invalid_permission_response();
    }

    $payload = $_POST;

    if (empty($payload))
    {
      $raw = trim((string) file_get_contents('php://input'));
      $payload = $raw !== '' ? json_decode($raw, true) : null;
    }

    $id = (int) ($payload['id'] ?? 0);
    if ($id <= 0) {
      return ['code' => 'error', 'detail' => ['type' => 'toast', 'msg' => 'Cartão inválido.']];
    }

    return update_payment_method_status($id, 'default');
  },
  'need_login' => true,
  'permission_callback' => '__return_true',
]);


register_rest_route('toggle-payment-method-status', [
  'methods'  => ['POST'],
  'callback' => function ()
  {
    global $current_user;

    if (empty($current_user['id'])) {
      return invalid_permission_response();
    }

    $payload = $_POST;

    if (empty($payload))
    {
      $raw = trim((string) file_get_contents('php://input'));
      $payload = $raw !== '' ? json_decode($raw, true) : null;
    }

    $id = (int) ($payload['id'] ?? 0);
    if ($id <= 0) {
      return ['code' => 'error', 'detail' => ['type' => 'toast', 'msg' => 'Cartão inválido.']];
    }

    return update_payment_method_status($id, 'toggle');
  },
  'need_login' => true,
  'permission_callback' => '__return_true',
]);


/**
 * Carousel A/B page (custom-pages/payment-methods-carousel.php) -- fetched
 * every time the carousel slides to a different card. A SINGLE route
 * (rather than one per section) so switching cards costs one round trip,
 * not two -- both pre-rendered HTML blobs (render_payment_method_transactions_html() /
 * render_payment_method_subscriptions_html(), src/ui.php) come back
 * together, matching this plugin's own convention for REST responses that
 * feed straight into the DOM (see view-my-payment's `payment` key above)
 * instead of raw JSON the front-end would have to template itself.
 */
register_rest_route('payment-method-details', [
  'methods'  => ['GET'],
  'callback' => function ()
  {
    global $current_user;

    if (empty($current_user['id'])) {
      return invalid_permission_response();
    }

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
      return ['code' => 'error', 'message' => 'id is required'];
    }

    return [
      'code' => 'success',
      'transactions_html'  => render_payment_method_transactions_html(list_payment_method_transactions($id)),
      'subscriptions_html' => render_payment_method_subscriptions_html(list_payment_method_subscriptions($id)),
    ];
  },
  'need_login' => true,
  'permission_callback' => '__return_true',
]);
