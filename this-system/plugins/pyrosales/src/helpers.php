<?php
if (!isset($seg)) exit;

function format_payment_gateways($is_settings_form = true)
{
    global $config, $payment_gateways;

    $active_payment_methods = $config['active_payment_methods'] ?? [];

    $res = [];
    foreach (($payment_gateways ?? []) as $key => $method)
    {
        if ($is_settings_form)
        {
            $res[] = [
                'value'   => $key,
                'display' => icon($method['icon'] ?? '') ." {$key}",
                'checked' => in_array($key, $active_payment_methods, true),
                'description' => $method['description'] ?? null
            ];
        }

        elseif (!$is_settings_form && in_array($key, $active_payment_methods, true))
        {
            $res[] = [
                'value'   => $method['method'],
                'display' => icon($method['icon'] ?? '') ." {$method['label']}",
                'description' => $method['description'] ?? null,
                'required' => true,
            ];
        }
    }

    return $res;
}

function list_providers()
{
    global $config, $payment_gateways;

    $res = [];
    foreach (($payment_gateways ?? []) as $key => $gateway) {
        $key = explode('.', $key);
        $res[] = $key[0];
    }

    return array_unique($res);
}

function format_user_payment_gateways($is_settings_form = true)
{
    global $config, $current_user, $payment_gateways;

    $user_id = $current_user['id'] ?? null;
    $active_payment_methods = $config['active_payment_methods'] ?? [];

    $providers = list_providers();
    $providers = implode("','", $providers);

    $sql = "
    SELECT *
    FROM tb_user_payment_methods
    WHERE
        user_id = '{$user_id}'
        AND provider IN ('{$providers}')
    ";
    $methods = get_results($sql);


    $res = [];
    if (!empty($methods))
    {
        foreach ($methods as $key => $method)
        {
            $res[] = [
                'value'   => "{$method['method']}:{$method['id']}",
                'display' => "{$method['issuer_name']} ** {$method['last4']}",
                'description' => "{$method['exp_month']}/{$method['exp_year']}",
                'image' => card_icon_url($method['issuer_name'], $method['brand']),
                'required' => true,
            ];
        }
    }

    return $res;
}


function card_icon_url(?string $issuerName, ?string $brand, string $color = '000000'): string
{
    $issuer = strtolower(trim((string)$issuerName));
    $brand  = strtolower(trim((string)$brand));

    // Normalize spaces
    $issuer = preg_replace('/\s+/', ' ', $issuer);

    /**
     * Issuer (bank) mapping
     */
    $issuerMap = [
        'nubank'       => 'nubank',
        'nu'           => 'nubank',
        // 'itaú'         => 'itau',
        // 'itau'         => 'itau',
        // 'bradesco'     => 'bradesco',
        // 'santander'    => 'santander',
        // 'banco inter'  => 'bancointer',
        // 'inter'        => 'bancointer',
        // 'c6 bank'      => 'c6bank',
        // 'c6'           => 'c6bank',
        'picpay'       => 'picpay',
        // 'caixa'        => 'caixa',
        // 'bb'           => 'bankofbrazil',
        // 'banco do brasil' => 'bankofbrazil',
    ];

    /**
     * Brand (card network) mapping
     */
    $brandMap = [
        'master'      => 'mastercard',
        'mastercard'  => 'mastercard',
        'visa'        => 'visa',
        'amex'        => 'americanexpress',
        'american_express' => 'americanexpress',
        // 'elo'         => 'elo',
        // 'debelo'      => 'elo',
        'hipercard'   => 'hipercard',
        'diners'      => 'dinersclub',
        'dinersclub'  => 'dinersclub',
        'discover'    => 'discover',
        'jcb'         => 'jcb',
    ];

    // 1️⃣ Try issuer
    if (!empty($issuerMap[$issuer])) {
        $slug = $issuerMap[$issuer];
        return "https://cdn.simpleicons.org/{$slug}/{$color}";
    }

    // 2️⃣ Fallback to brand
    if (!empty($brandMap[$brand])) {
        $slug = $brandMap[$brand];
        return "https://cdn.simpleicons.org/{$slug}/{$color}";
    }

    // 3️⃣ Generic fallback
    return "https://cdn.simpleicons.org/creditcard/{$color}";
}

/**
 * Returns a card icon URL based on issuer (bank) first,
 * falling back to card brand, and finally to generic credit card.
 *
 * @param string|null $issuerName
 * @param string|null $brand
 * @param string $color Hex color without # (default black)
 * @return string
 */
function card_icon_path_local(string $slug): ?string
{
    $slug = strtolower($slug);

    $baseDir = "pyrosales/assets/icons/issuers/{$slug}.svg";
    $full = plugin_path($baseDir);

    if (is_file($full)) {
        return plugin_path($baseDir, 'url');
    }

    return null;
}





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
        $errors[] = "Missing or invalid 'items'.";
    }
    elseif (!empty($payload['items']) && is_array($payload['items']))
    {
        foreach ($payload['items'] as $i => $it)
        {
            $type = $it['item_type'] ?? '';

            if (empty($type)) {
                $errors[] = "items[$i].item_type is required.";
            }

            if ($type !== 'plan' && (empty($it['quantity']) || (int)$it['quantity'] < 1)) {
                $errors[] = "items[$i].quantity must be >= 1.";
            }

            if ($type === 'product' && empty($it['product_id'])) {
                $errors[] = "items[$i].product_id is required for product items.";
            }

            if ($type === 'plan' && empty($it['plan_id'])) {
                $errors[] = "items[$i].plan_id is required for plan items.";
            }

            if ($type === 'one_off') {
                if (empty($it['item_name'])) $errors[] = "items[$i].item_name is required for one_off items.";
                if (!isset($it['unit_price'])) $errors[] = "items[$i].unit_price is required for one_off items.";
            }
        }
    }

    // if (empty($payload['payment_method']) || !is_string($payload['payment_method'])) {
    //     $errors[] = "Missing or invalid 'payment_method'.";
    // }

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
            $errors[] = "payment_method is required when order total is greater than zero.";
        }
    }

    $has_user = !empty($payload['user_id']);
    $has_customer = !empty($payload['customer']) && is_array($payload['customer']);

    if (!$has_user && !$has_customer) {
        $errors[] = "You must provide 'user_id' or 'customer'.";
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

        if (empty($c['first_name']))  $errors[] = "customer.first_name is required.";
        if (empty($c['last_name']))  $errors[] = "customer.last_name is required.";
        if (empty($c['email'])) $errors[] = "customer.email is required.";
        // phone/doc optional by schema
        if (!empty($c['document_type']) && empty($c['document_number'])) {
            $errors[] = "customer.document_number is required when customer.document_type is provided.";
        }
    }

    $requires_address = !empty($payload['requires_address']) ? 1 : 0;
    if ($requires_address === 1) {
        if (empty($payload['address']) || !is_array($payload['address'])) {
            $errors[] = "address is required when requires_address=1.";
        }
    }

    // print_r($payload);
    // die;

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
        if (!$u) throw new Exception("User not found: {$user_id}");

        $first_name = (string)($u['first_name'] ?? '');
        $last_name = (string)($u['last_name'] ?? '');

        // $name = [$first_name, $last_name];

        return [
            // 'customer_name'            => implode(' ', $name),
            'customer_first_name'      => $first_name,
            'customer_last_name'       => $last_name,
            'customer_email'           => (string)($u['email'] ?? ''),
            'customer_phone'           => $u['phone'] ?? null,
            'customer_document_type'   => $u['document_type'] ?? null,
            'customer_document_number' => $u['document_number'] ?? null,
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
        'customer_phone'           => $c['phone'] ?? null,
        'customer_document_type'   => $c['document_type'] ?? null,
        'customer_document_number' => $c['document_number'] ?? null,
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
        ];
    }

    if ($type === 'plan')
    {
        $id = (int)($item['plan_id'] ?? 0);
        if ($id <= 0) throw new Exception("plan_id is required for plan item.");

        // Plans are always quantity = 1 (force BEFORE any math)
        $qty = 1;

        $p = get_result("
            SELECT id, name, regular_price, sale_price, activation_function
            FROM tb_user_roles
            WHERE id = '{$id}' AND type = 'plan'
            LIMIT 1
        ");
        if (!$p) throw new Exception("plan not found: {$id}");

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
            'product_id'          => null,
            'plan_id'             => $id,
            'item_type'           => 'plan',
            'item_name'           => $name,
            'quantity'            => $qty,
            'activation_function' => $p['activation_function'] ?? null,

            'unit_price'          => number_format($unit, 2, '.', ''),
            'regular_unit_price'  => number_format($regular_unit, 2, '.', ''),

            'line_subtotal'       => number_format($line_subtotal, 2, '.', ''),
            'line_total'          => number_format($line_total, 2, '.', ''),

            // Optional but usefu l for auditing; remove if your schema doesn't support it.
            'line_discount'       => number_format($line_discount, 2, '.', ''),

            'meta_json'           => $item['meta_json'] ?? null,
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
            'product_id'         => null,
            'plan_id'            => null,
            'item_type'          => 'one_off',
            'item_name'          => $name,
            'quantity'           => $qty,

            'unit_price'         => number_format($unit, 2, '.', ''),
            'regular_unit_price' => number_format($regular_unit, 2, '.', ''),

            'line_subtotal'      => number_format($line_subtotal, 2, '.', ''),
            'line_total'         => number_format($line_total, 2, '.', ''),

            // Optional but useful for auditing; remove if your schema doesn't support it.
            'line_discount'      => number_format($line_discount, 2, '.', ''),

            'meta_json'          => $item['meta_json'] ?? null,
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
 * Returns:
 * - order_totals array for tb_orders
 * - normalized fee rows for tb_order_fees (only if provided)
 * - normalized coupon rows for tb_order_coupons (only if provided)
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
    $required = ['order_id', 'method', 'provider', 'amount'];

    foreach ($required as $key) {
        if (!isset($params[$key])) {
            throw new Exception("Missing required payment parameter: {$key}");
        }
    }

    $now = date('Y-m-d H:i:s');

    return [
        'order_id'            => $params['order_id'],
        'status_id'           => $params['status_id'] ?? 1,
        'method'              => (string)$params['method'],
        'provider'            => (string)$params['provider'],
        'currency'            => strtoupper($params['currency'] ?? DEFAULT_CURRENCY),
        'amount'              => number_format((float)$params['amount'], 2, '.', ''),
        'gateway_fee'         => isset($params['gateway_fee']) ? number_format((float)$params['gateway_fee'], 2, '.', '') : null,
        'net_amount'          => isset($params['net_amount']) ? number_format((float)$params['net_amount'], 2, '.', '') : null,
        'installments'        => isset($params['installments']) ? (int)$params['installments'] : null,
        'installment_amount'  => isset($params['installment_amount']) ? number_format((float)$params['installment_amount'], 2, '.', '') : null,
        'payment_link'        => $params['payment_link'] ?? null,
        'provider_payment_id' => $params['provider_payment_id'] ?? null,
        'provider_type_code'  => $params['provider_type_code'] ?? null,
        'raw_response_json'   => isset($params['raw_response_json'])
            ? json_encode($params['raw_response_json'], JSON_UNESCAPED_UNICODE)
            : null,
        'created_at'          => $now,
        'updated_at'          => $now,
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


/**
 * Main orchestrator that:
 * - Validates payload
 * - Builds customer snapshot (tb_users fallback if user_id present)
 * - Resolves items snapshots
 * - Calculates totals
 * - Returns ready-to-insert rows for all tables
 *
 * IMPORTANT:
 * - This function DOES NOT INSERT into DB. It returns arrays for you to persist.
 * - You should insert within a transaction:
 *   1) insert tb_orders -> $orderId
 *   2) insert tb_order_items (with $orderId)
 *   3) optionally insert coupons/fees (only if present in payload and non-empty)
 *   4) insert tb_order_payments (and then call provider adapter to create payment_link/provider_payment_id)
 *
 * @param array $payload
 * @param array $payment_gateway
 * @return array
 * @throws Exception
 */
function prepare_order_create(array $payload): array
{
    global $payment_gateways;

    $v = validate_order_payload($payload);
    if (!$v['ok']) {
        throw new Exception("Invalid payload: " . implode(' | ', $v['errors']));
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

    $user_id = !empty($payload['user_id']) ? (int)$payload['user_id'] : null;
    $customer_snapshot = build_customer_snapshot($user_id, $payload['customer'] ?? null);
    $requires_address = !empty($payload['requires_address']) ? 1 : 0;
    $address = normalize_address($payload['address'] ?? null, $requires_address);

    $order_type = $payload['items'][0]['item_type'] ?? 'one_off';

    $currency = $payload['currency'] ?? DEFAULT_CURRENCY;
    $currency = strtoupper(trim((string)$currency));
    if (strlen($currency) !== 3) $currency = DEFAULT_CURRENCY;

    // Resolve items
    $resolvedItems = [];
    foreach ($payload['items'] as $it) {
        $resolvedItems[] = resolve_item_snapshot($it);
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

    $orderRow = array_merge([
        'ip_address' => $ipAddress,
        'user_agent' => $userAgent,
        'origin' => $origin,
        'device_type' => $deviceType,
        'user_id' => $user_id,
        'status_id' => (int)($payload['status_id'] ?? 1), // default pending
        'order_type' => $order_type,
        'requires_address' => $requires_address,
        'address' => $address ? json_encode($address, JSON_UNESCAPED_UNICODE) : null,
        'currency' => $currency,
        'commission_amount' => isset($payload['commission_amount']) ? number_format((float)$payload['commission_amount'], 2, '.', '') : null,
        'vendor_id' => !empty($payload['vendor_id']) ? (int)$payload['vendor_id'] : null,
        'notes' => $payload['notes'] ?? null,
        'created_at' => $now,
        'updated_at' => $now,
    ], $customer_snapshot, $calc['order_totals']);


    $method = strtolower(
        trim((string) ($payload['payment_method'] ?? ''))
    );

    $method = explode(":", $method);
    $user_payment_method = $method[1] ?? null;

    // Provider resolution (payload only sends method)
    $method = $method[0];
    $provider = resolve_provider_by_method($method);


    // Payment row is prepared after order insert (needs order_id). We'll return a template.
    $paymentTemplate = [
        'method' => $method,
        'provider' => $provider,
        'currency' => $currency,
        'amount' => (float)$calc['order_totals']['total_amount'],
    ];

    if (!empty($user_payment_method)) {
        $payload['payment_data']['user_payment_method'] = $user_payment_method;
    }

    return [
        'order' => $orderRow,
        'items_lines' => $resolvedItems,
        'coupon_lines' => (is_array($payload['coupon_lines'] ?? null) && !empty($payload['coupon_lines'])) ? $calc['coupon_lines'] : [],
        'fee_lines'    => (is_array($payload['fee_lines'] ?? null) && !empty($payload['fee_lines'])) ? $calc['fee_lines'] : [],
        'payment_template' => $paymentTemplate,
        'payment_data' => $payload['payment_data'] ?? [],
    ];
}

function checkout_load_gateways_head()
{
    global $config, $payment_gateways;

    $active_payment_methods = $config['active_payment_methods'] ?? [];

    $res = [];
    foreach (($payment_gateways ?? []) as $key => $gateway)
    {
        $key = explode('.', $key);
        $gateway_method = implode('_', $key);

        $gateway_method = "{$gateway_method}_head";
        if (function_exists($gateway_method)) {
            $gateway_method();
        }
    }
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


/**
 * Persist saved card in your DB.
 * Requires tb_user_payment_methods with UNIQUE(provider, provider_card_id).
 */
function save_user_payment_method(string $user_id, array $pm, bool $makeDefault = true, bool $debug = false): ?int
{
    if ($user_id <= 0) return null; // only save if you can link to a user

    $now = date('Y-m-d H:i:s');

    // Optional: if default, unset others
    if ($makeDefault) {
        query_it("UPDATE tb_user_payment_methods SET is_default = 0 WHERE user_id = '{$user_id}'");
    }

    insert('tb_user_payment_methods', [
        'user_id'              => $user_id,
        'provider'             => $pm['provider'],
        'method'               => $pm['method'],
        'provider_customer_id' => $pm['provider_customer_id'] ?? null,
        'provider_card_id'     => $pm['provider_card_id'],
        'brand'                => $pm['brand'] ?? null,
        'brand_name'           => $pm['brand_name'] ?? null,
        'issuer_name'          => $pm['issuer_name'] ?? null,
        'last4'                => $pm['last4'] ?? null,
        'exp_month'            => !empty($pm['exp_month']) ? (int)$pm['exp_month'] : null,
        'exp_year'             => !empty($pm['exp_year']) ? (int)$pm['exp_year'] : null,
        'holder_name'          => $pm['holder_name'] ?? null,
        'is_default'           => $makeDefault ? 1 : 0,
        'status_id'            => 1,
        'meta_json'            => null,
        'created_at'           => $now,
        'updated_at'           => $now,
    ], true, $debug);

    $id = inserted_id();
    return $id ? (int)$id : null;
}
