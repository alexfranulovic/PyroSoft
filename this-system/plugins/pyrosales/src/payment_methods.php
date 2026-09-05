<?php
if (!isset($seg)) exit;

/**
 * Format payment gateway options for settings forms or checkout fields.
 *
 * This helper transforms the global payment gateway registry into a normalized
 * array structure suitable for UI rendering. It can be used in three modes:
 *
 * - `checkout`:
 *   Returns only active payment methods configured in the system, formatted
 *   for checkout field rendering.
 *
 * - `is_settings_form_free`:
 *   Returns only active payment methods, formatted as selectable options with
 *   checked state based on the provided `$selected` array.
 *
 * - `is_settings_form`:
 *   Returns all available payment methods, formatted as selectable options with
 *   checked state based on the system active payment methods configuration.
 *
 * @param string $mode       Output mode. Supported values:
 *                           - checkout
 *                           - is_settings_form
 *                           - is_settings_form_free
 * @param array  $selected   List of gateway keys marked as selected when using free settings mode.
 * @param array  $only_these Optional whitelist of gateway keys to include.
 * @global array $config           System configuration array.
 * @global array $payment_gateways Registered payment gateways.
 *
 * @return array
 */
function format_payment_gateways($mode = 'is_settings_form', array|string $selected = [], array $only_these = [])
{
    global $config, $payment_gateways;

    $active_payment_methods = $config['active_payment_methods'] ?? [];
    $list_methods = ($payment_gateways ?? []);

    if ($selected == 'default_payment_method') {
        $selected = [get_system_info('default_payment_method')];
    }

    if (!empty($only_these))
    {
        $pot = [];
        foreach ($list_methods as $gateway_method => $details)
        {
            if (in_array($gateway_method, $only_these)) {
                $pot[$gateway_method] = $details;
            }
        }

        $list_methods = $pot;
    }

    $res = [];
    foreach ($list_methods as $key => $method)
    {
        if ($mode == 'checkout' && in_array($key, $active_payment_methods, true))
        {
            $res[] = [
                'value'   => $method['method'],
                'display' => icon($method['icon'] ?? '') ." {$method['label']}",
                'description' => $method['description'] ?? null,
                'required' => true,
            ];
        }

        elseif ($mode == 'is_settings_form_free' && in_array($key, $active_payment_methods, true))
        {
            $res[] = [
                'value'   => $key,
                'display' => icon($method['icon'] ?? '') ." {$key}",
                'checked' => in_array($key, $selected, true),
                'description' => $method['description'] ?? null
            ];
        }

        elseif ($mode == 'is_settings_form')
        {
            $res[] = [
                'value'   => $key,
                'display' => icon($method['icon'] ?? '') ." {$key}",
                'checked' => in_array($key, $active_payment_methods, true),
                'description' => $method['description'] ?? null
            ];
        }
    }

    return $res;
}


/**
 * List registered payment gateways in a normalized provider/method format.
 *
 * Supported modes:
 *
 * - `default`:
 *   Lists all registered gateways, optionally filtered by `$only_these`.
 *
 * - `only_active`:
 *   Lists only gateways that are currently active in configuration.
 *
 * @param string $mode       Listing mode. Supported values:
 *                           - default
 *                           - only_active
 * @param array  $only_these Optional whitelist of gateway keys to include.
 * @global array $config           System configuration array.
 * @global array $payment_gateways Registered payment gateways.
 *
 * @return array
 */
function list_payment_gateways(string $mode = 'default', array $only_these = [])
{
    global $config, $payment_gateways;

    $active_payment_methods = $config['active_payment_methods'] ?? [];

    $list_methods = (!empty($only_these) && $mode == 'default')
        ? $only_these
        : ($payment_gateways ?? []);

    $res = [];
    foreach ($list_methods as $key => $gateway)
    {
        // normalize $key
        if (is_numeric($key)) {
            $key = $gateway;
        }

        if ($mode == 'only_active' && !in_array($key, $active_payment_methods)) continue;
        if (!empty($only_these) && !in_array($key, $only_these)) continue;

        $key = explode('.', $key);
        $res[] = [
            'provider' => $key[0],
            'method' => $key[1]
        ];
    }

    return $res;
}


/**
 * Format saved user payment methods for checkout rendering.
 *
 * This helper loads all active saved payment methods for the current user that
 * match the currently active system gateways and the optional whitelist.
 * The result is formatted for field rendering in checkout UI, including:
 *
 * @param array $only_these Optional whitelist of gateway keys to include.
 * @global array $config       System configuration array.
 * @global array $current_user Current logged user data.
 *
 * @return array
 */
function format_user_payment_gateways($only_these = [])
{
    global $config, $current_user;

    $user_id = $current_user['id'] ?? null;
    $active_payment_methods = $config['active_payment_methods'] ?? [];

    $gateways_methods = list_payment_gateways('only_active', $only_these);

    $providers = array_unique(array_column($gateways_methods, 'provider'));
    $providers = implode("','", $providers);

    $methods = array_unique(array_column($gateways_methods, 'method'));
    $methods = implode("','", $methods);

    $sql = "
    SELECT *
    FROM tb_user_payment_methods
    WHERE
        user_id = '{$user_id}'
        AND status_id = 1
        AND provider IN ('{$providers}')
        AND method IN ('{$methods}')
    ORDER BY is_default DESC, created_at DESC";
    $methods = get_results($sql);


    $res = [];
    if (!empty($methods))
    {
        foreach ($methods as $key => $method)
        {
            $exp_month = str_pad(($method['exp_month']??0), 2, "0", STR_PAD_LEFT);
            $res[] = [
                'value'   => "{$method['method']}:{$method['id']}",
                'display' => ucfirst($method['brand_name']) ." ** {$method['last4']}",
                'description' => "{$exp_month}/{$method['exp_year']}",
                'image' => card_icon_url($method['brand_name']),
                'required' => true,
                'attributes' => "data-card-bin:({$method['first6']}); data-card-brand:({$method['brand_name']});",
            ];
        }
    }

    return $res;
}


/**
 * Resolve the local icon URL for a card brand.
 *
 * This helper checks whether a brand icon exists inside the plugin assets and,
 * if found, returns its public URL. If the icon file does not exist, an empty
 * string is returned.
 *
 * @param string $brand Card brand name.
 *
 * @return string|null
 */
function card_icon_url(string $brand = ''): ?string
{
    $brand = strtolower($brand);

    $baseDir = "pyrosales/assets/icons/brands/{$brand}.webp";
    $full = plugin_path($baseDir);

    if (is_file($full)) {
        return plugin_path($baseDir, 'url');
    }

    return '';
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
        'first6'               => $pm['first6'] ?? null,
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

/**
 * Find and reactivate a saved PagBank card for the current user.
 *
 * @param int   $user_id
 * @param array $payment_data
 * @param bool  $debug
 *
 * @return array
 */
function find_user_payment_method(int $user_id = 0, array $payment_data = []): array
{
    global $conn;

    $user_id     = (int)$user_id;
    $card_number = preg_replace('/\D+/', '', (string)($payment_data['card_number'] ?? ''));
    $expiration  = trim((string)($payment_data['expiration'] ?? ''));
    $provider    = (string)($payment_data['provider'] ?? '');
    $method      = (string)($payment_data['method'] ?? '');

    if (
        $user_id <= 0 ||
        strlen($card_number) < 10 ||
        !preg_match('/^(\d{2})\s*\/\s*(\d{2}|\d{4})$/', $expiration, $matches)
    ) {
        return [];
    }

    $first6    = substr($card_number, 0, 6);
    $last4     = substr($card_number, -4);
    $exp_month = $matches[1];
    $exp_year  = strlen($matches[2]) === 2 ? '20' . $matches[2] : $matches[2];

    $first6    = mysqli_real_escape_string($conn, $first6);
    $last4     = mysqli_real_escape_string($conn, $last4);
    $exp_month = mysqli_real_escape_string($conn, $exp_month);
    $exp_year  = mysqli_real_escape_string($conn, $exp_year);

    $row = get_result("
        SELECT *
        FROM tb_user_payment_methods
        WHERE provider = '{$provider}'
          AND method = '{$method}'
          AND first6 = '{$first6}'
          AND last4 = '{$last4}'
          AND exp_month = '{$exp_month}'
          AND exp_year = '{$exp_year}'
          -- AND user_id = '{$user_id}'
        ORDER BY id DESC
        LIMIT 1
    ");

    if (empty($row['provider_card_id'])) {
        return [];
    }

    if ((int)$row['status_id'] !== 1) {
        query_it("
            UPDATE tb_user_payment_methods
            SET status_id = 1, updated_at = NOW()
            WHERE id = '" . (int)$row['id'] . "'
            LIMIT 1
        ");
    }

    return [
        'id'               => (int)$row['id'],
        'provider_card_id' => (string)$row['provider_card_id'],
    ];
}


/**
 * -----------------------------------------------------------------------
 * "Meus cartões" (payment-methods.php) support -- CRUD orchestration.
 * -----------------------------------------------------------------------
 *
 * format_user_payment_gateways() above only lists ACTIVE saved cards
 * (status_id = 1) and only for currently-active checkout gateways -- it's
 * built for the checkout radio list, not account management. The
 * functions below power the "Meus cartões" page instead: every card the
 * user has (active or not), and the actual cadastrar/editar/ativar-desativar
 * /marcar-como-padrão actions.
 */


/**
 * Card gateway keys ("provider.method") whose method is a card
 * (credit_card/debit_card) and are currently active in the system --
 * used to build the "Tipo de cartão" selector and to decide which
 * gateway `_fields()` renderers to embed in the add/edit-card offcanvas.
 * Provider-agnostic on purpose: works with pagbank today, and with any
 * future gateway plugin that registers a credit_card/debit_card method.
 *
 * @return array List of 'provider.method' keys, e.g. ['pagbank.credit_card', 'pagbank.debit_card'].
 */
function active_card_payment_gateway_keys(): array
{
    global $config, $payment_gateways;

    $active = $config['active_payment_methods'] ?? [];
    $res = [];

    foreach (($payment_gateways ?? []) as $key => $gateway)
    {
        if (!in_array($key, $active, true)) continue;
        if (!in_array($gateway['method'] ?? '', ['credit_card', 'debit_card'], true)) continue;

        $res[] = $key;
    }

    return $res;
}


/**
 * List EVERY saved payment method for a user (active or not) with
 * display-ready fields for the "Meus cartões" table/carousel/carrossel.
 *
 * Only returns cards whose gateway is still active in
 * $config['active_payment_methods'] -- reuses active_card_payment_gateway_keys()
 * (same provider/method IN-list idiom as format_user_payment_gateways() /
 * get_active_user_payment_methods()) so a card saved under a gateway that
 * was later disabled or uninstalled stops showing up here, even though its
 * row is still sitting in tb_user_payment_methods.
 *
 * Order: default card first, then active-before-inactive, then newest
 * first -- (status_id = 1) evaluates to 1/0 in MySQL, so DESC puts active
 * rows ahead of inactive ones within each default/non-default group.
 *
 * @param int|null $user_id Defaults to $current_user['id'].
 * @return array
 */
function list_user_saved_payment_methods(?int $user_id = null, bool $only_active = false): array
{
    global $current_user;

    $user_id = $user_id ?? (int)($current_user['id'] ?? 0);
    if ($user_id <= 0) return [];

    $card_gateway_keys = active_card_payment_gateway_keys();
    if (empty($card_gateway_keys)) return [];

    $providers = [];
    $methods   = [];
    foreach ($card_gateway_keys as $gateway_key)
    {
        [$provider, $method] = array_pad(explode('.', $gateway_key, 2), 2, '');
        $providers[] = $provider;
        $methods[]   = $method;
    }

    $providers = implode("','", array_unique($providers));
    $methods   = implode("','", array_unique($methods));

    $only_active_sql = $only_active
        ? " AND status_id = 1"
        : "";

    $rows = get_results("
        SELECT *
        FROM tb_user_payment_methods
        WHERE user_id = '{$user_id}'
          AND provider IN ('{$providers}')
          AND method IN ('{$methods}')
          {$only_active_sql}
        ORDER BY is_default DESC, (status_id = 1) DESC, created_at DESC
    ");

    $res = [];
    foreach (($rows ?: []) as $row)
    {
        $exp_month = str_pad((string)($row['exp_month'] ?? ''), 2, '0', STR_PAD_LEFT);

        $res[] = $row + [
            'brand_icon' => card_icon_url($row['brand_name'] ?? ''),
            'display'    => ucfirst((string)($row['brand_name'] ?? '')) ." ** {$row['last4']}",
            'expiration' => "{$exp_month}/{$row['exp_year']}",
            'type_label' => ($row['method'] === 'debit_card') ? 'Débito' : 'Crédito',
            'is_active'  => (int)($row['status_id'] ?? 0) === 1,
        ];
    }

    return $res;
}


/**
 * Transações (tb_order_payments) feitas com um cartão salvo específico --
 * backs the "Transações" section of the "Meus cartões" carousel A/B page
 * (custom-pages/payment-methods-carousel.php), fetched on demand each time
 * the carousel lands on a different card.
 *
 * Ownership is re-checked here (not just trusted from the caller) the same
 * way every other function in this file re-checks $current_user['id'] --
 * a card id alone must never be enough to read another user's data.
 * order_purpose = 'trial_validation' orders are excluded, same rule as
 * list_user_payment_history().
 *
 * Defaults to the last 5 -- "Meus cartões" only ever shows a quick preview
 * per card, with a link to the full /historico-pagamentos/ listing for
 * everything else (see render_payment_method_transactions_html(), src/ui.php).
 *
 * @param int $user_payment_method_id tb_user_payment_methods.id
 * @param int $limit
 * @return array
 */
function list_payment_method_transactions(int $user_payment_method_id, int $limit = 5): array
{
    global $current_user;

    $user_id = (int)($current_user['id'] ?? 0);
    $upm_id  = (int)$user_payment_method_id;

    if ($user_id <= 0 || $upm_id <= 0) return [];

    if ($limit < 1) $limit = 5;
    if ($limit > 50) $limit = 50;

    // Ownership check -- the card must belong to the logged-in user.
    $owns = get_result("
        SELECT id
        FROM tb_user_payment_methods
        WHERE id = '{$upm_id}' AND user_id = '{$user_id}'
        LIMIT 1
    ");

    if (empty($owns)) return [];

    $rows = get_results("
        SELECT
            p.id AS payment_id,
            p.order_id,
            p.status_id AS payment_status_id,
            p.currency,
            p.amount,
            p.statement_descriptor,
            p.installments,
            p.created_at,
            o.status_id AS order_status_id,
            first_item.item_name AS first_item_name,
            COALESCE(items_count.total_items, 0) AS total_items
        FROM tb_order_payments p
        INNER JOIN tb_orders o ON o.id = p.order_id
        LEFT JOIN (
            SELECT oi1.order_id, oi1.item_name
            FROM tb_order_items oi1
            WHERE oi1.id = (SELECT MIN(oi2.id) FROM tb_order_items oi2 WHERE oi2.order_id = oi1.order_id)
        ) first_item ON first_item.order_id = o.id
        LEFT JOIN (
            SELECT order_id, COUNT(*) AS total_items
            FROM tb_order_items
            GROUP BY order_id
        ) items_count ON items_count.order_id = o.id
        WHERE p.user_payment_method_id = '{$upm_id}'
          AND o.user_id = '{$user_id}'
          AND (o.order_purpose IS NULL OR o.order_purpose != 'trial_validation')
        ORDER BY p.created_at DESC
        LIMIT {$limit}
    ");

    return $rows ?: [];
}


/**
 * Assinaturas (tb_plan_user_subscriptions) atreladas a um cartão salvo
 * específico -- backs the "Assinaturas" section of the same carousel A/B
 * page, right below list_payment_method_transactions() above.
 *
 * Also defaults to the last 5, for the same "quick preview per card" reason
 * -- see list_payment_method_transactions()'s docblock.
 *
 * @param int $user_payment_method_id tb_user_payment_methods.id
 * @param int $limit
 * @return array
 */
function list_payment_method_subscriptions(int $user_payment_method_id, int $limit = 5): array
{
    global $current_user;

    $user_id = (int)($current_user['id'] ?? 0);
    $upm_id  = (int)$user_payment_method_id;

    if ($user_id <= 0 || $upm_id <= 0) return [];

    if ($limit < 1) $limit = 5;
    if ($limit > 50) $limit = 50;

    $owns = get_result("
        SELECT id
        FROM tb_user_payment_methods
        WHERE id = '{$upm_id}' AND user_id = '{$user_id}'
        LIMIT 1
    ");

    if (empty($owns)) return [];

    $rows = get_results("
        SELECT
            sub.id,
            sub.plan_id,
            sub.status,
            sub.started_at,
            sub.current_period_end,
            sub.next_billing_at,
            sub.auto_renew,
            plan.name AS plan_name,
            plan.interval_unit,
            plan.interval_count
        FROM tb_plan_user_subscriptions sub
        INNER JOIN tb_plans plan ON plan.id = sub.plan_id
        WHERE sub.user_payment_method_id = '{$upm_id}'
          AND sub.user_id = '{$user_id}'
        ORDER BY sub.id DESC
        LIMIT {$limit}
    ");

    return $rows ?: [];
}


/**
 * Query backing custom-listings/payment-history.php -- one row per
 * tb_order_payments, scoped to $current_user['id'] via an INNER JOIN to
 * tb_orders (same idiom as api.php's view-my-payment route and
 * get_order()'s $only_current_user flag), with the first item's name +
 * total item count (LEFT JOINed per-order subqueries) and the saved card
 * used, if any (LEFT JOIN tb_user_payment_methods).
 *
 * Kept as hand-written SQL rather than query_builder() -- the per-order
 * "first item name" / "item count" lookups need correlated subqueries in
 * the JOIN clause, which query_builder()'s join spec (a flat
 * table+condition pair, see list_orders()) has no way to express.
 *
 * @param array $args ['q' => search term, 'sort' => field, 'dir' => 'ASC'|'DESC', 'limit' => int, 'offset' => int]
 * @return array ['total' => int, 'data' => array]
 */
function list_user_payment_history(array $args = []): array
{
    global $current_user;

    $user_id = (int)($current_user['id'] ?? 0);
    if ($user_id <= 0) {
        return ['total' => 0, 'data' => []];
    }

    $limit = (int)($args['limit'] ?? 10);
    if ($limit < 1) $limit = 10;
    if ($limit > 200) $limit = 200;

    $offset = (int)($args['offset'] ?? 0);
    if ($offset < 0) $offset = 0;

    $sort_columns = [
        'created_at' => 'p.created_at',
        'amount'     => 'p.amount',
        'id'         => 'p.id',
    ];
    $sort_key = in_array($args['sort'] ?? 'created_at', array_keys($sort_columns), true)
        ? ($args['sort'] ?? 'created_at')
        : 'created_at';

    $dir = strtoupper($args['dir'] ?? 'DESC');
    if (!in_array($dir, ['ASC', 'DESC'], true)) $dir = 'DESC';

    $search_sql = '';
    $search = trim((string)($args['q'] ?? ''));
    if ($search !== '')
    {
        $term = addslashes($search);
        $search_sql = "AND (
            o.id LIKE '%{$term}%'
            OR p.statement_descriptor LIKE '%{$term}%'
            OR first_item.item_name LIKE '%{$term}%'
        )";
    }

    $base_from = "
        FROM tb_order_payments p
        INNER JOIN tb_orders o ON o.id = p.order_id
        LEFT JOIN tb_user_payment_methods upm ON upm.id = p.user_payment_method_id
        LEFT JOIN (
            SELECT oi1.order_id, oi1.item_name
            FROM tb_order_items oi1
            WHERE oi1.id = (SELECT MIN(oi2.id) FROM tb_order_items oi2 WHERE oi2.order_id = oi1.order_id)
        ) first_item ON first_item.order_id = o.id
        LEFT JOIN (
            SELECT order_id, COUNT(*) AS total_items
            FROM tb_order_items
            GROUP BY order_id
        ) items_count ON items_count.order_id = o.id
        WHERE o.user_id = '{$user_id}'
        AND (o.order_purpose IS NULL OR o.order_purpose != 'trial_validation')
        {$search_sql}
    ";

    $total = (int)(get_result("SELECT COUNT(*) AS c {$base_from}")['c'] ?? 0);

    $sql = "
        SELECT
            p.id AS payment_id,
            p.order_id,
            p.status_id AS payment_status_id,
            p.method,
            p.provider,
            p.currency,
            p.amount,
            p.statement_descriptor,
            p.created_at,
            o.status_id AS order_status_id,
            upm.brand_name,
            upm.last4,
            upm.exp_month,
            upm.exp_year,
            first_item.item_name AS first_item_name,
            COALESCE(items_count.total_items, 0) AS total_items
        {$base_from}
        ORDER BY {$sort_columns[$sort_key]} {$dir}
        LIMIT {$limit} OFFSET {$offset}
    ";

    $rows = get_results($sql);

    return [
        'total' => $total,
        'data'  => $rows ?: [],
    ];
}


/**
 * Cadastrar um novo cartão salvo (tb_user_payment_methods).
 *
 * Delegates the actual tokenization/verification to the active gateway's
 * own "{provider}_save_payment_method" function (mirrors exactly how
 * cancel_refund() looks up "{provider}_cancel_refund" -- see src/cancel-refund.php),
 * so every gateway plugin builds its own request payload while the CMS
 * side stays gateway-agnostic.
 *
 * Cards are never edited in place -- a saved card's number/token can never
 * be mutated on any real gateway, so this function only ever creates a new
 * row. To replace a card, the user adds the new one and (optionally) turns
 * the old one off / removes its "padrão" flag from the "Meus cartões" page.
 *
 * @param array $params ['payment_method' => 'credit_card'|'debit_card', 'payment_data' => [...]]
 * @param bool  $debug
 * @return array
 */
function save_payment_method(array $params, bool $debug = false): array
{
    global $current_user, $seg;

    // resolve_provider_by_method() lives in helpers.php, which (unlike this
    // file) is NOT auto-loaded at plugin boot -- every other caller of it
    // (create_order(), the checkout-amount route) requires it on demand
    // the same way.
    require_once __DIR__ .'/helpers.php';

    $msg_code = 'ER_TO_SAVE_PAYMENT_METHOD';
    $user_id  = (int)($current_user['id'] ?? 0);

    if ($user_id <= 0)
    {
        return [
            'code' => 'error',
            'detail' => [
                'type' => 'toast',
                'msg'  => alert_message($GLOBALS['alerts'][$msg_code], 'toast'),
                'code' => $msg_code,
            ],
        ];
    }

    $method     = strtolower(trim((string)($params['payment_method'] ?? '')));
    $provider   = resolve_provider_by_method($method);

    $gateway_save_function = "{$provider}_save_payment_method";
    if (empty($provider) || !function_exists($gateway_save_function)) {
        return payment_method_error_response($msg_code, 'Este método de pagamento não está disponível.');
    }

    $name_parts = array_filter([
        trim((string)($current_user['first_name'] ?? '')),
        trim((string)($current_user['last_name'] ?? '')),
    ]);

    $gateway_params = [
        'method'  => $method,
        'user_id' => $user_id,
        'customer' => [
            'name'            => implode(' ', $name_parts),
            'email'           => (string)($current_user['email'] ?? ''),
            'phone'           => (string)($current_user['phone'] ?? ''),
            'document_number' => (string)($current_user['document_number'] ?? ''),
            'address'         => $current_user['address'] ?? null,
        ],
        'payment_data' => (array)($params['payment_data'] ?? []),
    ];

    $gateway_response = $gateway_save_function($gateway_params, $debug);

    if ($debug) {
        print_r($gateway_response);
    }

    if (($gateway_response['code'] ?? 'error') !== 'success')
    {
        return payment_method_error_response($msg_code, $gateway_response['detail']['msg'] ?? '');
    }

    $new_id = (int)($gateway_response['data']['user_payment_method_id'] ?? 0);
    if ($new_id <= 0)
    {
        return payment_method_error_response($msg_code, 'O gateway não retornou os dados do cartão salvo.');
    }

    return [
        'code' => 'success',
        'redirect' => '{force_reload}',
    ];
}


/**
 * Ativar/desativar OU marcar como padrão um cartão salvo -- unifica o que
 * antes eram duas funções quase idênticas (set_default_payment_method() e
 * toggle_payment_method_status()): mesma validação de posse, mesmo
 * lookup, só o UPDATE final e as regras em torno dele mudavam.
 *
 * status_id: 1 = ativo, 2 = inativo -- 2 é o próprio default da coluna em
 * tb_user_payment_methods (install.php), então um cartão nasce "inativo"
 * até a primeira vez que for ativado por aqui (ou automaticamente ativado
 * por save_user_payment_method()/find_user_payment_method(), que sempre
 * gravam/promovem para status_id = 1).
 *
 * Desativar o cartão padrão também limpa a flag de padrão -- não faz
 * sentido um cartão desativado continuar marcado como o padrão do usuário.
 * Marcar como padrão exige o cartão já estar ativo.
 *
 * @param int    $id   tb_user_payment_methods.id
 * @param string $mode 'toggle' (ativar/desativar) ou 'default' (marcar como padrão)
 * @return array
 */
function update_payment_method_status(int $id, string $mode): array
{
    global $current_user;

    $msg_code = 'ER_TO_UPDATE_PAYMENT_METHOD';
    $user_id  = (int)($current_user['id'] ?? 0);
    $id       = (int)$id;

    if ($user_id <= 0 || $id <= 0) {
        return payment_method_error_response($msg_code, 'Cartão inválido.');
    }

    $row = get_result("
        SELECT id, status_id
        FROM tb_user_payment_methods
        WHERE id = '{$id}' AND user_id = '{$user_id}'
        LIMIT 1
    ");

    if (empty($row)) {
        return payment_method_error_response($msg_code, 'Cartão não encontrado.');
    }

    if ($mode === 'default')
    {
        if ((int)$row['status_id'] !== 1) {
            return payment_method_error_response($msg_code, 'Ative o cartão antes de marcá-lo como padrão.');
        }

        query_it("UPDATE tb_user_payment_methods SET is_default = 0 WHERE user_id = '{$user_id}'");
        query_it("UPDATE tb_user_payment_methods SET is_default = 1, updated_at = NOW() WHERE id = '{$id}' AND user_id = '{$user_id}' LIMIT 1");

        return ['code' => 'success', 'data' => ['id' => $id]];
    }

    // mode === 'toggle'
    $new_status = ((int)$row['status_id'] === 1) ? 2 : 1;

    query_it("
        UPDATE tb_user_payment_methods
        SET status_id = '{$new_status}', updated_at = NOW()
        WHERE id = '{$id}' AND user_id = '{$user_id}'
        LIMIT 1
    ");

    if ($new_status !== 1)
    {
        query_it("UPDATE tb_user_payment_methods SET is_default = 0 WHERE id = '{$id}' AND user_id = '{$user_id}'");
    }

    return ['code' => 'success', 'data' => ['id' => $id, 'status_id' => $new_status]];
}


/**
 * Small shared helper for the toast-shaped error responses above --
 * mirrors the `$alert_message['body'] .= ...; alert_message(...)` pattern
 * used throughout cancel_refund()/order_notification().
 *
 * @param string $msg_code Key into $GLOBALS['alerts'].
 * @param string $detail   Extra detail appended to the alert body.
 * @return array
 */
function payment_method_error_response(string $msg_code, string $detail = ''): array
{
    $alert_message = $GLOBALS['alerts'][$msg_code] ?? [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'Não foi possível concluir a ação: ',
    ];

    if ($detail !== '') {
        $alert_message['body'] .= $detail;
    }

    return [
        'code' => 'error',
        'detail' => [
            'type' => 'toast',
            'msg'  => alert_message($alert_message, 'toast'),
            'code' => $msg_code,
        ],
    ];
}
