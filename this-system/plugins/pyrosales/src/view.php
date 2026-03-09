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
function get_order(int $orderId = 0, bool $withLines = true): array
{
    $order = get_result("SELECT * FROM tb_orders WHERE id = '{$orderId}' LIMIT 1");

    if (!$order) return [];
    if (!$withLines) {
        return ['code' => 'success', 'order' => $order];
    }

    $items    = get_results("SELECT * FROM tb_order_items WHERE order_id = '{$orderId}' ORDER BY id ASC");
    $coupons  = get_results("SELECT * FROM tb_order_coupons WHERE order_id = '{$orderId}' ORDER BY id ASC");
    $fees     = get_results("SELECT * FROM tb_order_fees WHERE order_id = '{$orderId}' ORDER BY id ASC");
    $payments = get_results("SELECT * FROM tb_order_payments WHERE order_id = '{$orderId}' ORDER BY id ASC");

    return [
        'code' => 'success',
        'order' => $order,
        'items' => $items ?: [],
        'coupon_lines' => $coupons ?: [],
        'fee_lines' => $fees ?: [],
        'payments' => $payments ?: [],
    ];
}


/**
 * Fetches multiple orders based on filters.
 *
 * Supported filters:
 * - user_id (int)
 * - vendor_id (int)
 * - status_id (int)
 * - order_type (string)
 * - email (string) (customer_email)
 * - created_from (Y-m-d or Y-m-d H:i:s)
 * - created_to (Y-m-d or Y-m-d H:i:s)
 * - min_total (float)
 * - max_total (float)
 * - q (string) search in name/email/doc
 * - limit (int, default 10, max 200)
 * - offset (int, default 0)
 * - sort (created_at|total_amount|id, default created_at)
 * - dir (ASC|DESC, default DESC)
 *
 * @param array $filters
 * @return array
 */
function list_orders(array $filters = []): array
{
    $where = ["1=1"];

    if (!empty($filters['user_id']))   $where[] = "user_id = '" . (int)$filters['user_id'] . "'";
    if (!empty($filters['vendor_id'])) $where[] = "vendor_id = '" . (int)$filters['vendor_id'] . "'";
    if (!empty($filters['status_id'])) $where[] = "status_id = '" . (int)$filters['status_id'] . "'";

    if (!empty($filters['order_type'])) {
        $type = addslashes((string)$filters['order_type']);
        $where[] = "order_type = '{$type}'";
    }

    if (!empty($filters['email'])) {
        $email = addslashes((string)$filters['email']);
        $where[] = "customer_email = '{$email}'";
    }

    if (!empty($filters['created_from'])) {
        $from = addslashes((string)$filters['created_from']);
        $where[] = "created_at >= '{$from}'";
    }

    if (!empty($filters['created_to'])) {
        $to = addslashes((string)$filters['created_to']);
        $where[] = "created_at <= '{$to}'";
    }

    if (isset($filters['min_total'])) {
        $min = number_format((float)$filters['min_total'], 2, '.', '');
        $where[] = "total_amount >= '{$min}'";
    }

    if (isset($filters['max_total'])) {
        $max = number_format((float)$filters['max_total'], 2, '.', '');
        $where[] = "total_amount <= '{$max}'";
    }

    if (!empty($filters['q'])) {
        $q = addslashes((string)$filters['q']);
        $where[] = "(
            customer_name LIKE '%{$q}%'
            OR customer_email LIKE '%{$q}%'
            OR customer_document_number LIKE '%{$q}%'
        )";
    }

    $sortAllowed = ['created_at','total_amount','id'];
    $sort = in_array(($filters['sort'] ?? 'created_at'), $sortAllowed, true)
        ? ($filters['sort'] ?? 'created_at')
        : 'created_at';

    $dir = strtoupper($filters['dir'] ?? 'DESC');
    if (!in_array($dir, ['ASC','DESC'], true)) $dir = 'DESC';

    $limit = (int)($filters['limit'] ?? 10);
    if ($limit < 1) $limit = 10;
    if ($limit > 200) $limit = 200;

    $offset = (int)($filters['offset'] ?? 0);
    if ($offset < 0) $offset = 0;

    $sqlWhere = implode(' AND ', $where);

    $totalRow = get_result("SELECT COUNT(*) AS c FROM tb_orders WHERE {$sqlWhere}");
    $total = (int)($totalRow['c'] ?? 0);

    $orders = get_results("
        SELECT *
        FROM tb_orders
        WHERE {$sqlWhere}
        ORDER BY {$sort} {$dir}
        LIMIT {$limit} OFFSET {$offset}
    ");

    return [
        'code' => 'success',
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
        'orders' => $orders ?: [],
    ];
}
