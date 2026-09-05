<?php
if (!isset($seg)) exit;


/**
 * Fetches a single order (header) by id.
 * Optionally loads lines (items/coupons/fees/payments).
 *
 * @param int $orderId
 * @param bool $withLines
 * @return array
 */
function get_order($orderId = 0, bool $withLines = true, bool $only_current_user = false): array
{
    global $current_user;

    $where = '';
    if ($only_current_user AND !empty($current_user['id'])) {
        $where = "AND user_id = '{$current_user['id']}'";
    }

    $order = get_result("
        SELECT
            ord.*,
            user.document_type AS vendor_document_type,
            user.first_name AS vendor_first_name,
            user.last_name AS vendor_last_name,
            user.email AS vendor_email,
            user.phone AS vendor_phone,
            user.document_number AS vendor_document_number
        FROM tb_orders AS ord
        LEFT JOIN tb_users AS user ON ord.vendor_id = user.id
        WHERE ord.id = '{$orderId}' {$where}
        LIMIT 1");

    if (!$order) return [];
    if (!$withLines) {
        return ['code' => 'success', 'order' => $order];
    }

    $items    = get_results("SELECT * FROM tb_order_items WHERE order_id = '{$orderId}' ORDER BY id ASC");
    $coupons  = get_results("SELECT * FROM tb_order_coupons WHERE order_id = '{$orderId}' ORDER BY id ASC");
    $fees     = get_results("SELECT * FROM tb_order_fees WHERE order_id = '{$orderId}' ORDER BY id ASC");
    $payments = get_results("SELECT * FROM tb_order_payments WHERE order_id = '{$orderId}' ORDER BY id ASC");
    $utm_data = get_result("SELECT * FROM tb_order_utm_data WHERE order_id = '{$orderId}' LIMIT 1");

    return [
        'code' => 'success',
        'order' => $order,
        'items' => $items ?: [],
        'coupon_lines' => $coupons ?: [],
        'fee_lines' => $fees ?: [],
        'payments' => $payments ?: [],
        'utm_data' => $utm_data ?: null,
    ];
}


/**
 * list_orders() — rewritten to use query_builder() instead of hand-built
 * SQL strings, and to search across BOTH tb_orders and tb_order_payments —
 * each with its own PRE-SEARCH PROCESSING, so e.g. the document number
 * field is matched after stripping formatting from the typed term (via
 * clean_number()), while name/email fields are matched on the raw term.
 *
 * Three things worth knowing:
 *
 * 1) Per-field processing needs a per-field WHERE clause (search "cleaned
 *    digits" against the document column, but the raw term against name/
 *    email columns) — a single CONCAT_WS(...) LIKE '%term%' can't do that,
 *    since it's one term against one blended string. This builds one
 *    "$column LIKE '%term%'" fragment per searchable field (each using
 *    that field's own processed term) and OR's them together into ONE raw
 *    fragment, via query_builder()'s new `raw_where` — needed because
 *    `where_logic` is a single flat AND/OR and can't mix "AND the fixed
 *    filters, but OR the search fragments" otherwise. I build and escape
 *    this fragment myself (addslashes on each term), so it doesn't depend
 *    on assumptions about safe_where()'s `skip_sanitize` behaviour.
 *
 * 2) The join to tb_order_payments is LEFT (so orders without a payment
 *    row still show up) + GROUP BY tb_orders.id (so an order with more
 *    than one payment row doesn't get duplicated) + MIN(op.method) in the
 *    SELECT list (MySQL's ONLY_FULL_GROUP_BY mode requires any selected
 *    column that isn't part of the GROUP BY, or functionally dependent on
 *    it, to be wrapped in an aggregate — `tb_orders.*` is fine as-is
 *    because grouping by its own primary key covers it).
 *
 * The WHERE fields for the plain filters (user_id, vendor_id, etc.) are
 * left unqualified, same as your original code — I don't have safe_where()'s
 * source, so I can't confirm how it treats a dotted field name, and these
 * particular column names are unlikely to collide with tb_order_payments'
 * columns. The one place that DOES need table-qualifying is ORDER BY on
 * `id`, since tb_order_payments almost certainly has its own `id` column
 * too (ambiguous otherwise) — that's covered by the query_builder() fix.
 * If you hit a "column is ambiguous" MySQL error on any of the other
 * filters, that means tb_order_payments has a same-named column too —
 * tell me and I'll qualify that specific one.
 */
function list_orders(array $filters = []): array
{
    $where = [];

    if (!empty($filters['user_id']))    $where[] = ['field' => 'user_id', 'operator' => '=', 'value' => (int) $filters['user_id']];
    if (!empty($filters['vendor_id']))  $where[] = ['field' => 'vendor_id', 'operator' => '=', 'value' => (int) $filters['vendor_id']];
    if (!empty($filters['status_id']))  $where[] = ['field' => 'status_id', 'operator' => '=', 'value' => (int) $filters['status_id']];
    if (!empty($filters['order_type'])) $where[] = ['field' => 'order_type', 'operator' => '=', 'value' => (string) $filters['order_type']];
    if (!empty($filters['email']))      $where[] = ['field' => 'customer_email', 'operator' => '=', 'value' => (string) $filters['email']];
    if (!empty($filters['created_from'])) $where[] = ['field' => 'created_at', 'operator' => '>=', 'value' => (string) $filters['created_from']];
    if (!empty($filters['created_to']))   $where[] = ['field' => 'created_at', 'operator' => '<=', 'value' => (string) $filters['created_to']];
    if (isset($filters['min_total']))   $where[] = ['field' => 'total_amount', 'operator' => '>=', 'value' => number_format((float) $filters['min_total'], 2, '.', '')];
    if (isset($filters['max_total']))   $where[] = ['field' => 'total_amount', 'operator' => '<=', 'value' => number_format((float) $filters['max_total'], 2, '.', '')];

    $raw_where = [];

    // Search across tb_orders' own columns AND the joined payment method.
    // Each field can have its own `function_process` applied to the typed
    // term before it's matched — e.g. clean_number() strips formatting so
    // "000.000.000-00" and "00000000000" both match the unformatted value
    // stored in the DB. Also fixes the original bug: it searched a
    // `customer_name` column that doesn't exist; this uses the real
    // first/last name columns.
    if (!empty($filters['q'])) {
        $search_fields = [
            ['column' => 'tb_orders.customer_first_name'],
            ['column' => 'tb_orders.customer_last_name'],
            ['column' => 'tb_orders.customer_email'],
            ['column' => 'tb_orders.customer_document_number', 'function_process' => 'clean_number'],
            ['column' => 'tb_orders.total_amount', 'function_process' => fn($term) => DECIMAL($term, false)],
            ['column' => 'tb_orders.order_type'],
            ['column' => 'tb_orders.order_purpose'],
            ['column' => 'tb_orders.created_at', 'function_process' => fn($term) => br_datetime_to_mysql($term)],
            ['column' => 'op.method'],
        ];

        $clauses = [];
        foreach ($search_fields as $search_field)
        {
            $term = !empty($search_field['function_process']) && is_callable($search_field['function_process'])
                ? (string) call_user_func($search_field['function_process'], $filters['q'])
                : (string) $filters['q'];

            if ($term === '') continue; // e.g. clean_number() on a term with no digits

            $clauses[] = "{$search_field['column']} LIKE '%" . addslashes($term) . "%'";
        }

        if (!empty($clauses)) {
            $raw_where[] = implode(' OR ', $clauses);
        }
    }

    $joins = [[
        'type'      => 'LEFT',
        'table'     => 'tb_order_payments op',
        'condition' => 'op.order_id = tb_orders.id',
    ]];

    // Table-qualified so ORDER BY `id` can't collide with tb_order_payments.id.
    $sort_columns = ['created_at' => 'tb_orders.created_at', 'total_amount' => 'tb_orders.total_amount', 'id' => 'tb_orders.id'];
    $sort_key     = in_array($filters['sort'] ?? 'created_at', array_keys($sort_columns), true) ? ($filters['sort'] ?? 'created_at') : 'created_at';

    $dir = strtoupper($filters['dir'] ?? 'DESC');
    if (!in_array($dir, ['ASC', 'DESC'], true)) $dir = 'DESC';

    $limit = (int) ($filters['limit'] ?? 10);
    if ($limit < 1) $limit = 10;
    if ($limit > 200) $limit = 200;

    $offset = (int) ($filters['offset'] ?? 0);
    if ($offset < 0) $offset = 0;
    $current_page = floor($offset / $limit) + 1;

    // COUNT ignores GROUP BY/ORDER BY/pagination entirely — DISTINCT on the
    // order id is what keeps a multi-payment order from being counted twice.
    $total = (int) (get_result(query_builder([
        'table'       => 'tb_orders',
        'fields'      => ['COUNT(DISTINCT tb_orders.id) AS c'],
        'joins'       => $joins,
        'where'       => $where,
        'where_logic' => 'AND',
        'raw_where'   => $raw_where,
    ]))['c'] ?? 0);

    $query = query_builder([
        'table'              => 'tb_orders',
        'fields'             => ['tb_orders.*', 'MIN(op.method) AS method, op.status_id AS payment_status_id'],
        'joins'              => $joins,
        'where'              => $where,
        'where_logic'        => 'AND',
        'raw_where'          => $raw_where,
        'group_by'           => ['tb_orders.id'],
        'order_by'           => [['field' => $sort_columns[$sort_key], 'way' => $dir]],
        'registers_per_page' => $limit,
        'current_page'       => $current_page,
    ]);
    $orders = get_results($query);

    return [
        'code'   => 'success',
        'total'  => $total,
        'limit'  => $limit,
        'offset' => $offset,
        'orders' => $orders ?: [],
    ];
}
