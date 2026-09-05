<?php
if (!isset($seg)) exit;

/**
 * Validates a create-order payload with minimal rules:
 * - Must have items (non-empty array)
 * - Must have payment_method
 * - Must have user_id OR customer object
 * - If requires_address = 1, address must be present and non-empty
 *
 * @param array $payload
 * @return array [ok=>bool, errors=>array]
 */
function validate_order_payload(array $payload): array
{
    $errors = [];

    // If there is no direct plan_id/product_id, items becomes mandatory.
    if (
        empty($payload['plan_id']) && empty($payload['product_id'])
        && (empty($payload['items']) || !is_array($payload['items']))
    ) {
        $errors[] = "Você precisa informar pelo menos um item no pedido.";
    }
    elseif (!empty($payload['items']) && is_array($payload['items']))
    {
        foreach ($payload['items'] as $i => $it)
        {
            $itemNumber = $i + 1;
            $type = $it['item_type'] ?? '';

            if (empty($type)) {
                $errors[] = "O tipo do item {$itemNumber} é obrigatório.";
            }

            if ($type !== 'plan' && (empty($it['quantity']) || (int)$it['quantity'] < 1)) {
                $errors[] = "A quantidade do item {$itemNumber} deve ser maior que zero.";
            }

            if ($type === 'product' && empty($it['product_id'])) {
                $errors[] = "O produto do item {$itemNumber} não foi informado.";
            }

            if ($type === 'plan' && empty($it['plan_id'])) {
                $errors[] = "O plano do item {$itemNumber} não foi informado.";
            }

            if ($type === 'one_off') {
                if (empty($it['item_name'])) {
                    $errors[] = "O nome do produto {$itemNumber} é obrigatório.";
                }

                if (!isset($it['unit_price'])) {
                    $errors[] = "O valor unitário do produto {$itemNumber} é obrigatório.";
                }
            }
        }
    }

    $total_preview = 0.0;

    if (!empty($payload['items']) && is_array($payload['items']))
    {
        foreach ($payload['items'] as $it)
        {
            $qty  = isset($it['quantity']) ? (int)$it['quantity'] : 1;
            $unit = isset($it['unit_price']) ? (float)$it['unit_price'] : 0;

            if ($qty < 1) $qty = 1;

            $total_preview += ($unit * $qty);
        }
    }

    /**
     * If order total > 0 → payment is required.
     */
    if ($total_preview > 0)
    {
        if (empty($payload['payment_method']) || !is_string($payload['payment_method'])) {
            $errors[] = "A forma de pagamento é obrigatória para pedidos com valor maior que zero.";
        }
    }

    $has_user = !empty($payload['user_id']);
    $has_customer = !empty($payload['customer']) && is_array($payload['customer']);

    if (!$has_user && !$has_customer) {
        $errors[] = "Você precisa informar um usuário ou os dados do cliente.";
    }

    if (!$has_user && $has_customer)
    {
        $c = $payload['customer'];

        if (!empty($c['name']))
        {
            $c['name'] = explode(' ', $c['name']);

            $c['first_name'] = $c['name'][0];
            unset($c['name'][0]);

            $c['name'] = implode(' ', $c['name']);
            $c['last_name'] = $c['name'];
        }

        if (empty($c['first_name'])) {
            $errors[] = "O primeiro nome do cliente é obrigatório.";
        }

        if (empty($c['last_name'])) {
            $errors[] = "O sobrenome do cliente é obrigatório.";
        }

        if (empty($c['email'])) {
            $errors[] = "O e-mail do cliente é obrigatório.";
        }

        // phone/doc optional by schema
        if (!empty($c['document_type']) && empty($c['document_number'])) {
            $errors[] = "O número do documento do cliente é obrigatório quando o tipo de documento for informado.";
        }
    }

    $requires_address = !empty($payload['requires_address']) ? 1 : 0;

    if ($requires_address === 1) {
        if (empty($payload['address']) || !is_array($payload['address'])) {
            $errors[] = "O endereço é obrigatório para este pedido.";
        }
    }

    return ['ok' => empty($errors), 'errors' => $errors];
}


/**
 * Builds the customer snapshot:
 * - If user_id is provided: loads from tb_users
 * - Else: uses payload['customer']
 *
 * Returns columns compatible with tb_orders customer_* fields.
 *
 * @param int|null $user_id
 * @param array|null $customer
 * @return array
 * @throws Exception
 */
function build_customer_snapshot(?int $user_id, ?array $customer): array
{
    if ($user_id && $user_id > 0)
    {
        // Adjust field names to your tb_users schema:
        $u = get_result("SELECT id, first_name, last_name, email, phone, document_type, document_number FROM tb_users WHERE id = '{$user_id}' LIMIT 1");
        if (!$u) {
            // throw new Exception("User not found: {$user_id}");
            return ['errors' => "User not found: {$user_id}"];
        }

        $first_name = (string)($u['first_name'] ?? '');
        $last_name = (string)($u['last_name'] ?? '');

        // $name = [$first_name, $last_name];

        return [
            // 'customer_name'            => implode(' ', $name),
            'customer_first_name'      => $first_name,
            'customer_last_name'       => $last_name,
            'customer_email'           => (string)($u['email'] ?? ''),
            'customer_phone'           => clean_number($u['phone'] ?? ''),
            'customer_document_type'   => $u['document_type'] ?? null,
            'customer_document_number' => clean_number(($u['document_number'] ?? '')),
        ];
    }

    $c = $customer ?? [];
    if (!empty($c['name']))
    {
        $c['name'] = explode(' ', $c['name']);

        $c['first_name'] = $c['name'][0];
        unset($c['name'][0]);

        $c['name'] = implode(' ', $c['name']);
        $c['last_name'] = $c['name'];
    }

    return [
        // 'customer_name'            => (string)($c['name'] ?? ''),
        'customer_first_name'      => (string)($c['first_name'] ?? ''),
        'customer_last_name'       => (string)($c['last_name'] ?? ''),
        'customer_email'           => (string)($c['email'] ?? ''),
        'customer_phone'           => clean_number($c['phone'] ?? ''),
        'customer_document_type'   => $c['document_type'] ?? null,
        'customer_document_number' => clean_number($c['document_number'] ?? ''),
    ];
}

/**
 * Resolves the active provider by payment method, using a simple map.
 *
 * @param string $method
 * @param array $payment_gateways Example: ['pix'=>'mercadopago', 'credit_card'=>'pagarme']
 * @return string
 * @throws Exception
 */
function resolve_provider_by_method(string $method)
{
    global $config;

    $active = $config['active_payment_methods'] ?? [];

    // Support both formats:
    // 1) List: ["pagbank.pix", "mercadopago.credit_card"]
    // 2) Map : { "pagbank.pix": {...}, "mercadopago.credit_card": {...} }
    $keys = array_keys($active) === range(0, count($active) - 1)
        ? $active
        : array_keys($active);

    foreach ($keys as $key)
    {
        // key = "provider.method"
        $pos = strrpos($key, '.');
        if ($pos === false) continue;

        $key_method = substr($key, $pos + 1);

        if ($key_method === $method) {
            return explode('.', $key)[0];
        }
    }

    return null;
}


/**
 * Normalizes and returns a safe address payload for saving as JSON.
 *
 * @param mixed $address
 * @param int $requires_address
 * @return array|null
 * @throws Exception
 */
function normalize_address($address, int $requires_address): ?array
{
    if ($requires_address === 0) {
        // You can choose: return null always, or accept the address anyway.
        return is_array($address) ? $address : null;
    }

    if (!is_array($address) || empty($address)) {
        throw new Exception("Address is required.");
    }

    return $address;
}

/**
 * Resolves an item into a frozen line snapshot:
 * - For product/plan: loads current price + name from DB
 * - For one_off: uses payload name + unit_price
 *
 * Returns fields compatible with tb_order_items row (excluding order_id + timestamps).
 *
 * @param array $item
 * @return array
 * @throws Exception
 */
function resolve_item_snapshot(array $item): array
{
    $type = (string)($item['item_type'] ?? '');
    $qty  = (int)($item['quantity'] ?? 1);
    if ($qty < 1) $qty = 1;

    // Treat unit_price as override only if it really exists (not empty/null)
    $has_unit_override = array_key_exists('unit_price', $item) && $item['unit_price'] !== '' && $item['unit_price'] !== null;

    if ($type === 'product')
    {
        $id = (int)($item['product_id'] ?? 0);
        if ($id <= 0) throw new Exception("product_id is required for product item.");

        $p = get_result("SELECT id, name, regular_price, sale_price FROM tb_products WHERE id = '{$id}' LIMIT 1");
        if (!$p) throw new Exception("Product not found: {$id}");

        $regular_price = (float)$p['regular_price'];
        $sale_price    = (float)$p['sale_price'];

        // Default charged price = sale when valid, otherwise regular
        $default_unit = ($sale_price > 0)
            ? min(($regular_price > 0 ? $regular_price : $sale_price), $sale_price)
            : $regular_price;

        // Reference price (before discounts/overrides)
        $regular_unit = $regular_price > 0 ? $regular_price : $default_unit;

        // Charged price (can be overridden)
        $unit = $has_unit_override
            ? max(0, (float)$item['unit_price'])
            : $default_unit;

        $name = (string)$p['name'];

        // Important: Subtotal is regular, Total is charged
        $line_subtotal = round($regular_unit * $qty, 2);
        $line_total    = round($unit * $qty, 2);
        $line_discount = round(max(0, $line_subtotal - $line_total), 2);

        return [
            'formatted' => [
                'product_id'         => $id,
                'plan_id'            => null,
                'item_type'          => 'product',
                'item_name'          => $name,
                'quantity'           => $qty,
                'unit_price'         => number_format($unit, 2, '.', ''),
                'regular_unit_price' => number_format($regular_unit, 2, '.', ''),
                'line_subtotal'      => number_format($line_subtotal, 2, '.', ''),
                'line_total'         => number_format($line_total, 2, '.', ''),
                // Optional but useful for auditing; remove if your schema doesn't support it.
                'line_discount'      => number_format($line_discount, 2, '.', ''),
                'meta_json'          => $item['meta_json'] ?? null,
            ]
        ];
    }

    if ($type === 'plan')
    {
        $id = (string)($item['plan_id'] ?? '');
        if (empty($id)) throw new Exception("plan_id is required for plan item.");

        // Plans are always quantity = 1 (force BEFORE any math)
        $qty = 1;

        $p = get_result("SELECT * FROM tb_plans WHERE id = '{$id}' OR slug = '{$id}' LIMIT 1");
        if (!$p) throw new Exception("plan not found: {$id}");

        $id = $p['id'];
        $regular_price = (float)$p['regular_price'];
        $sale_price    = (float)$p['sale_price'];

        $default_unit = ($sale_price > 0)
            ? min(($regular_price > 0 ? $regular_price : $sale_price), $sale_price)
            : $regular_price;

        $regular_unit = $regular_price > 0 ? $regular_price : $default_unit;

        $unit = $has_unit_override
            ? max(0, (float)$item['unit_price'])
            : $default_unit;

        $name = (string)$p['name'];

        $line_subtotal = round($regular_unit * $qty, 2);
        $line_total    = round($unit * $qty, 2);
        $line_discount = round(max(0, $line_subtotal - $line_total), 2);

        return [
            'formatted' => [
                'product_id'          => null,
                'plan_id'             => $id,
                'item_type'           => 'plan',
                'item_name'           => $name,
                'quantity'            => $qty,
                // 'activation_function' => $p['activation_function'] ?? null,
                // 'deactivation_function' => $p['deactivation_function'] ?? null,
                'unit_price'          => number_format($unit, 2, '.', ''),
                'regular_unit_price'  => number_format($regular_unit, 2, '.', ''),
                'line_subtotal'       => number_format($line_subtotal, 2, '.', ''),
                'line_total'          => number_format($line_total, 2, '.', ''),
                // Optional but usefu l for auditing; remove if your schema doesn't support it.
                'line_discount'       => number_format($line_discount, 2, '.', ''),
                'meta_json'           => $item['meta_json'] ?? null,
            ],
            'full' => $p
        ];
    }

    if ($type === 'one_off')
    {
        $name = (string)($item['item_name'] ?? '');
        if ($name === '') throw new Exception("item_name is required for one_off item.");

        $unit = (float)($item['unit_price'] ?? 0);
        if ($unit < 0) $unit = 0;

        // For one_off, regular == charged unless a separate reference is passed
        $regular_unit = isset($item['regular_unit_price'])
            ? max(0, (float)$item['regular_unit_price'])
            : $unit;

        $line_subtotal = round($regular_unit * $qty, 2);
        $line_total    = round($unit * $qty, 2);
        $line_discount = round(max(0, $line_subtotal - $line_total), 2);

        return [
            'formatted' => [
                'product_id'            => null,
                'plan_id'               => null,
                'item_type'             => 'one_off',
                'item_name'             => $name,
                'slug'                  => (string)($item['slug'] ?? ''),
                'quantity'              => $qty,
                'unit_price'            => number_format($unit, 2, '.', ''),
                'regular_unit_price'    => number_format($regular_unit, 2, '.', ''),
                'line_subtotal'         => number_format($line_subtotal, 2, '.', ''),
                'line_total'            => number_format($line_total, 2, '.', ''),
                // Optional but useful for auditing; remove if your schema doesn't support it.
                'line_discount'         => number_format($line_discount, 2, '.', ''),
                'meta_json'             => $item['meta_json'] ?? null,
                // Frozen on the order item itself (tb_order_items already has these
                // columns) so create_order/cancel-refund/notification can run them
                // later without depending on the cart cookie still being around.
                'activation_function'   => trim((string)($item['activation_function'] ?? '')),
                'deactivation_function' => trim((string)($item['deactivation_function'] ?? '')),
            ]
        ];
    }

    throw new Exception("Unsupported item_type: {$type}");
}


/**
 * Calculates totals from resolved items + coupon_lines + fee_lines.
 * - coupon_lines: array of ['code'=>...]
 * - fees: array of ['name'=>..., 'amount'=>..., 'tax_status'=>...]
 * Uses global $coupons as the coupon registry (as per your requirement).
 *
 * @param array $resolvedItems
 * @param array|null $couponLines
 * @param array|null $feeLines
 * @return array
 */
function calculate_order_totals(array $resolvedItems, ?array $couponLines, ?array $feeLines): array
{
    global $coupons;
    $coupons = is_array($coupons) ? $coupons : [];

    $subtotal = 0.00;
    $base_item_discount = 0.00;

    foreach ($resolvedItems as $it)
    {
        $ls = (float)$it['line_subtotal']; // before discount
        $lt = (float)$it['line_total'];    // after item-level discount

        $subtotal += $ls;

        $d = round($ls - $lt, 2);
        if ($d > 0) $base_item_discount += $d;
    }

    $subtotal = round($subtotal, 2);
    $base_item_discount = round($base_item_discount, 2);

    // Fees
    $fee_amount = 0.00;
    $feeRows = [];
    if (is_array($feeLines) && !empty($feeLines))
    {
        foreach ($feeLines as $f)
        {
            $name = (string)($f['name'] ?? '');
            if ($name === '') continue;

            $amount = round((float)($f['amount'] ?? 0), 2);

            $tax_status = (string)($f['tax_status'] ?? 'none');
            if (!in_array($tax_status, ['none','taxable'], true)) $tax_status = 'none';

            $fee_amount = round($fee_amount + $amount, 2);

            $feeRows[] = [
                'name'       => $name,
                'amount'     => number_format($amount, 2, '.', ''),
                'tax_status' => $tax_status,
                'meta_json'  => $f['meta_json'] ?? null,
            ];
        }
    }
    $fee_amount = round($fee_amount, 2);

    // Coupons (these are EXTRA discounts on top of item-level discount)
    $coupon_discount_amount = 0.00;
    $couponRows = [];
    if (is_array($couponLines) && !empty($couponLines))
    {
        // Optional: apply coupons over remaining base (after item discount)
        // This prevents "double discount" on already discounted amount.
        $coupon_base = round(max(0, $subtotal - $base_item_discount), 2);

        foreach ($couponLines as $c)
        {
            $code = strtoupper(trim((string)($c['code'] ?? '')));
            if ($code === '') continue;
            if ($coupon_base <= 0) break;

            $cfg = $coupons[$code] ?? null;
            if (!$cfg) continue;

            $type  = (string)($cfg['discount_type'] ?? 'fixed_cart'); // percent|fixed_cart|fixed_item
            $value = round((float)($cfg['discount_value'] ?? 0), 2);

            $applied = 0.00;

            if ($type === 'percent') {
                $applied = round(($coupon_base * $value) / 100, 2);
            } else {
                // fixed_cart as default (fixed_item not implemented here)
                $applied = round($value, 2);
            }

            if ($applied > $coupon_base) $applied = $coupon_base;

            $coupon_discount_amount = round($coupon_discount_amount + $applied, 2);
            $coupon_base = round($coupon_base - $applied, 2);

            $couponRows[] = [
                'code'            => $code,
                'discount_type'   => in_array($type, ['percent','fixed_cart','fixed_item'], true) ? $type : 'fixed_cart',
                'discount_value'  => number_format($value, 2, '.', ''),
                'discount_amount' => number_format($applied, 2, '.', ''),
                'meta_json'       => [
                    'source'   => 'global_$coupons',
                    'snapshot' => $cfg,
                ],
            ];
        }
    }

    // Total discount = item-level (sale) + coupons
    $discount_amount = round($base_item_discount + $coupon_discount_amount, 2);

    // Discount cannot exceed subtotal
    $discount_amount = round(min($discount_amount, $subtotal), 2);

    // Shipping/tax placeholders
    $shipping_amount = 0.00;
    $tax_amount      = 0.00;

    $total = round($subtotal - $discount_amount + $fee_amount + $shipping_amount + $tax_amount, 2);
    if ($total < 0) $total = 0.00;

    return [
        'coupon_lines' => $couponRows,
        'fee_lines'    => $feeRows,
        'order_totals' => [
            'subtotal_amount' => number_format($subtotal, 2, '.', ''),
            'discount_amount' => number_format($discount_amount, 2, '.', ''),
            'fee_amount'      => number_format($fee_amount, 2, '.', ''),
            'shipping_amount' => number_format($shipping_amount, 2, '.', ''),
            'tax_amount'      => number_format($tax_amount, 2, '.', ''),
            'total_amount'    => number_format($total, 2, '.', ''),
        ],
    ];
}

/**
 * Creates a tb_order_payments row payload to insert, based on order total and chosen provider.
 * Does NOT call the gateway (you'll do that in provider-specific adapters).
 *
 * @param int $orderId
 * @param string $method
 * @param string $provider
 * @param string $currency
 * @param float $amount
 * @return array
 */
function build_payment_line(array $params): array
{
    global $info;

    $required = ['order_id', 'method', 'provider', 'amount'];

    foreach ($required as $key) {
        if (!isset($params[$key])) {
            throw new Exception("Missing required payment parameter: {$key}");
        }
    }

    $now = date('Y-m-d H:i:s');

    $statement_descriptor = (string)($params['statement_descriptor'] ?? ($info['short_name'] ?? 'PAYMENT'));
    $statement_descriptor = preg_replace('/[^A-Z0-9 ]/', '', strtoupper($statement_descriptor));
    $statement_descriptor = substr(trim($statement_descriptor), 0, 17);

    return [
        'order_id'               => $params['order_id'],
        'status_id'              => $params['status_id'] ?? 1,
        'method'                 => (string)$params['method'],
        'provider'               => (string)$params['provider'],
        'currency'               => strtoupper($params['currency'] ?? DEFAULT_CURRENCY),
        'amount'                 => number_format((float)$params['amount'], 2, '.', ''),
        'gateway_fee'            => isset($params['gateway_fee']) ? number_format((float)$params['gateway_fee'], 2, '.', '') : null,
        'net_amount'             => isset($params['net_amount']) ? number_format((float)$params['net_amount'], 2, '.', '') : null,
        'installments'           => isset($params['installments']) ? (int)$params['installments'] : null,
        'installment_amount'     => isset($params['installment_amount']) ? number_format((float)$params['installment_amount'], 2, '.', '') : null,
        'code'                   => $params['code'] ?? null,
        'payment_link'           => $params['payment_link'] ?? null,
        'provider_order_id'      => $params['provider_order_id'] ?? null,
        'provider_payment_id'    => $params['provider_payment_id'] ?? null,
        'provider_type_code'     => $params['provider_type_code'] ?? null,
        'raw_response_json'      => isset($params['raw_response_json'])
            ? json_encode(array_reverse($params['raw_response_json']), JSON_UNESCAPED_UNICODE)
            : null,
        'payment_hash'           => $params['payment_hash'] ?? null,
        'expires_at'             => $params['expires_at'] ?? null,
        'provider_reference'     => $params['provider_reference'] ?? null,
        'statement_descriptor'   => $statement_descriptor,
        'user_payment_method_id' => $params['user_payment_method_id'] ?? null,
        'created_at'             => $now,
        'updated_at'             => $now,
    ];
}

/**
 * Persists whatever UTM parameters capture_utm_params() (core -- runs on
 * every page view, well before any plugin/route code, and stashes captured
 * tags in $_SESSION with a $_COOKIE fallback for a returning visit) has for
 * the CURRENT visitor, snapshotted against the order that was just created.
 *
 * One row per order (not per visit): this is called once, right after the
 * order insert in create_order() (index.php), so the UTM values are frozen
 * at the exact moment of purchase, the same way tb_orders itself freezes
 * ip_address/user_agent/origin -- a later visit with different tags (or no
 * tags at all) never rewrites what an already-placed order was attributed
 * to.
 *
 * @param int $order_id
 * @return array
 */
function save_order_utm_data(int $order_id): array
{
    if ($order_id <= 0) {
        return ['code' => 'error', 'msg' => ['reason' => 'Invalid order_id']];
    }

    // Same default key list as capture_utm_params() -- these are also the
    // exact tb_order_utm_data column names. invite_code rides along the
    // same session/cookie mechanism (see capture_utm_params(), core) and
    // is what create_order() (index.php) resolves into vendor_id.
    $allowed_params = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'utm_resource', 'invite_code'];

    $utm = [];
    foreach ($allowed_params as $key)
    {
        $value = trim((string)($_SESSION[$key] ?? ''));
        if ($value !== '') {
            $utm[$key] = $value;
        }
    }

    // Nothing captured for this visitor (direct/organic traffic, or the
    // cookie/session already expired) -- skip the row entirely rather than
    // insert one with every column empty; get_order() already treats "no
    // tb_order_utm_data row" the same as "no UTM data" either way.
    if (empty($utm)) {
        return ['code' => 'success', 'skipped' => true];
    }

    $insert = array_merge(
        ['order_id' => $order_id],
        array_fill_keys($allowed_params, null),
        $utm,
        ['created_at' => date('Y-m-d H:i:s')]
    );

    insert('tb_order_utm_data', $insert);
    $id = inserted_id();

    return [
        'code' => $id ? 'success' : 'error',
        'id'   => $id,
    ];
}

function detect_device_type(string $userAgent): string
{
    $ua = strtolower($userAgent);

    if (preg_match('/mobile|android|iphone|ipad|ipod|blackberry|windows phone/', $ua)) {
        return 'mobile';
    }

    if (preg_match('/tablet/', $ua)) {
        return 'tablet';
    }

    return 'desktop';
}


function prepare_payment_data(array $payload): array
{
    $user_id        = !empty($payload['user_id']) ? (int)$payload['user_id'] : null;
    $payment_data   = (array)($payload['payment_data'] ?? []);
    $calc           = (array)($payload['calc'] ?? []);

    $method = strtolower(
        trim((string) ($payload['payment_method'] ?? ''))
    );

    $currency               = $payload['currency'] ?? get_system_info('default_currency');
    $method                 = explode(":", $method);
    $user_payment_method_id = $payment_data['user_payment_method_id'] ?? ($method[1]??null);

    // Provider resolution (payload only sends method)
    $method = $method[0];
    $payment_data['method'] = $method;

    $provider = resolve_provider_by_method($method);
    $payment_data['provider'] = $provider;

    $payment_hash = hash('sha256', token_generate([
        'mode'   => 'hex',
        'length' => 32,
    ]));

    // Payment row is prepared after order insert (needs order_id). We'll return a template.
    $paymentTemplate = [
        'method' => $method,
        'provider' => $provider,
        'currency' => $currency,
        'amount' => (float)$calc['order_totals']['total_amount'],
        'payment_hash' => $payment_hash,
    ];

    /**
     * Get user peyment method.
     */
    if (!empty($user_payment_method_id))
    {
        $provider_card_id = get_col("
            SELECT
                provider_card_id
            FROM tb_user_payment_methods
            WHERE
                id = '{$user_payment_method_id}'
                AND user_id = {$user_id}"
        );

        $payment_data['user_payment_method_id'] = $user_payment_method_id;
        $payment_data['provider_card_id'] = $provider_card_id;
    }

    else
    {
        $provider_card = find_user_payment_method($user_id, $payment_data);

        if (!empty($provider_card['provider_card_id'])) {
            $payment_data['user_payment_method_id'] = $provider_card['id'];
            $payment_data['provider_card_id'] = $provider_card['provider_card_id'];
        }
    }

    return [
        'payment_template' => $paymentTemplate,
        'payment_data' => $payment_data,
    ];
}


/**
 * Main orchestrator that:
 * - Validates payload
 * - Builds customer snapshot (tb_users fallback if user_id present)
 * - Resolves items snapshots
 * - Calculates totals
 * - Returns ready-to-insert rows for all tables
 *
 * @param array $payload
 * @param array $payment_gateway
 * @return array
 * @throws Exception
 */
function prepare_order_create(array $payload): array
{
    global $payment_gateways;

    /**
     * One-off order: the item isn't sent by the client -- it's whatever is
     * currently frozen in the one-off cart cookie. The `one_off` flag is
     * forced into the checkout form as a hidden field (see plan-checkout-template.php),
     * the actual name/price/functions always come from the cookie (server-side),
     * never trusted from payload directly.
     *
     * This must run BEFORE validate_order_payload(): unlike plan_id/product_id
     * (checked directly by the validator), items[] is what's actually
     * validated here, so it needs to already be populated -- an empty/expired
     * cookie then naturally falls through to the normal "no item" validation error.
     */
    if (!empty($payload['one_off']) && empty($payload['items']))
    {
        $one_off_item = get_one_off_cart_item();

        if (!empty($one_off_item)) {
            $payload['items'][] = $one_off_item;
        }
    }

    $v = validate_order_payload($payload);
    if (!$v['ok']) {
        // throw new Exception("Invalid payload: " . implode(' | ', $v['errors']));
        return $v;
    }

    if (!empty($payload['plan_id']) && empty($payload['items']))
    {
        $payload['items'][] = [
            'item_type' => 'plan',
            'plan_id' => $payload['plan_id'],
            'quantity' => 1,
        ];
    }

    if (!empty($payload['product_id']) && empty($payload['items']))
    {
        $payload['items'][] = [
            'item_type' => 'product',
            'product_id' => $payload['product_id'],
            'quantity' => 1,
        ];
    }

    $user_id = !empty($payload['user_id'])
        ? (int)$payload['user_id']
        : (!empty($_SESSION['current_user']['id']) ? $_SESSION['current_user']['id'] : null);
    $customer_snapshot = build_customer_snapshot($user_id, $payload['customer'] ?? null);
    $requires_address = !empty($payload['requires_address']) ? 1 : 0;
    $address = normalize_address($payload['address'] ?? null, $requires_address);

    $order_type = $payload['items'][0]['item_type'] ?? 'one_off';

    $currency = $payload['currency'] ?? DEFAULT_CURRENCY;
    $currency = strtoupper(trim((string)$currency));
    if (strlen($currency) !== 3) $currency = DEFAULT_CURRENCY;

    // Resolve items
    $items_full = [];
    $resolvedItems = [];
    foreach ($payload['items'] as $it)
    {
        $it = resolve_item_snapshot($it);
        $resolvedItems[] = $it['formatted'];

        if (!empty($it['full'])) {
            $items_full[] = $it['full'];
        }
    }

    // Calculate totals + normalized coupon/fee rows
    $calc = calculate_order_totals(
        $resolvedItems,
        $payload['coupon_lines'] ?? null,
        $payload['fee_lines'] ?? null
    );

    $now = date('Y-m-d H:i:s');

    $ipAddress = $_SERVER['HTTP_CF_CONNECTING_IP']
        ?? $_SERVER['HTTP_X_FORWARDED_FOR']
        ?? $_SERVER['REMOTE_ADDR']
        ?? null;

    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $origin = $_SERVER['HTTP_ORIGIN']
        ?? $_SERVER['HTTP_REFERER']
        ?? null;

    $deviceType = $userAgent ? detect_device_type($userAgent) : null;

    /**
     * (Only order type plan) Search if user is already subscriber.
     */
    if (
        $order_type == 'plan' &&
        empty($payload['subscription_id']) &&
        !empty($user_id)
    ){
        $payload['subscription_id'] = user_is_already_sub($user_id, $payload['plan_id']);
    }

    $vendor_id = !empty($payload['vendor_id'])
        ? (int)$payload['vendor_id']
        : null;
    $commission_activation_function = !empty($vendor_id)
        ? (get_system_info('pyrosales_commission_activation_function') ?? null)
        : null;

    $orderRow = array_merge([
        'ip_address' => $ipAddress,
        'user_agent' => $userAgent,
        'origin' => $origin,
        'subscription_id' => ((int)($payload['subscription_id'] ?? null)),
        'device_type' => $deviceType,
        'user_id' => $user_id,
        'status_id' => (int)($payload['status_id'] ?? 1), // default pending
        'order_type' => $order_type,
        'requires_address' => $requires_address,
        'address' => $address ? json_encode($address, JSON_UNESCAPED_UNICODE) : null,
        'currency' => $currency,
        'commission_amount' => isset($payload['commission_amount']) ? number_format((float)$payload['commission_amount'], 2, '.', '') : null,
        'vendor_id' => $vendor_id,
        'commission_activation_function' => $commission_activation_function,
        'notes' => $payload['notes'] ?? null,
        'order_purpose' => $payload['order_purpose'] ?? 'charge',
        'created_at' => $now,
        'updated_at' => $now,
    ], $customer_snapshot, $calc['order_totals']);

    return [
        'order' => $orderRow,
        'items_lines' => $resolvedItems,
        'items_full' => $items_full,
        'coupon_lines' => (is_array($payload['coupon_lines'] ?? null) && !empty($payload['coupon_lines'])) ? $calc['coupon_lines'] : [],
        'fee_lines' => (is_array($payload['fee_lines'] ?? null) && !empty($payload['fee_lines'])) ? $calc['fee_lines'] : [],
        'calc' => $calc,
    ];
}


/**
 * Simple checkout config calculator.
 *
 * @param array $params {
 *   @type float|int|string $amount_base  Base amount (e.g. 74.90 or "74,90")
 *   @type string           $fee_mode     'merchant' (você), 'customer' (cliente), 'split' (parcial)
 *   @type int              $max_no_interest Max installments without interest (1..18)
 *
 *   // Optional (only used when fee_mode is 'customer' or 'split')
 *   @type float|int|string $surcharge_percent Percent surcharge (e.g. 0.049 = 4.9%)
 *   @type float|int|string $surcharge_fixed   Fixed surcharge in BRL (e.g. 1.00)
 * }
 *
 * @return array
 */
function checkout_amount_config(array $params = [])
{
    $currency = 'BRL';

    // Base amount (accepts "74,90" too)
    $baseRaw = $params['amount_base'] ?? 0;
    $base = (float)str_replace(',', '.', trim((string)$baseRaw));
    if ($base < 0) $base = 0.0;

    // Who pays "fees" (your business rule layer)
    $fee_mode = (string)($params['fee_mode'] ?? 'merchant');
    $allowed = ['merchant','customer','split'];
    if (!in_array($fee_mode, $allowed, true)) $fee_mode = 'merchant';

    // Max installments w/o interest
    $max_no_interest = (int)($params['max_no_interest'] ?? get_system_info('max_interest_free_installments'));
    if ($max_no_interest < 1) $max_no_interest = 1;
    if ($max_no_interest > 18) $max_no_interest = 18;

    // Optional surcharge (only applied when customer/split)
    $percentRaw = $params['surcharge_percent'] ?? get_system_info('surcharge_percent');
    $fixedRaw   = $params['surcharge_fixed'] ?? get_system_info('surcharge_fixed');

    $percent = (float)str_replace(',', '.', trim((string)$percentRaw));
    $fixed   = (float)str_replace(',', '.', trim((string)$fixedRaw));

    if ($percent < 0) $percent = 0.0;
    if ($percent > 1) $percent = 1.0;
    if ($fixed < 0) $fixed = 0.0;

    // Final amount
    $final = $base;

    // If you chose "customer/split", you can repass a surcharge you define
    if ($fee_mode === 'customer' || $fee_mode === 'split') {
        $final = ($base * (1.0 + $percent)) + $fixed;
        $final = round($final, 2);
        if ($final < 0) $final = 0.0;
    }

    return [
      'currency' => $currency,
      'amount'       => number_format($final, 2, '.', ''),
      'amount_base'  => number_format($base,  2, '.', ''),
      'amount_final' => number_format($final, 2, '.', ''),
      'fee_mode' => $fee_mode,
      'max_interest_free_installments' => $max_no_interest,
    ];
}
