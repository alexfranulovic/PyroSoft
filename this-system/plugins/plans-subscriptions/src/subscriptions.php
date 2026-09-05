<?php
if (!isset($seg)) exit;

function get_user_subscriptions(int|string $user_id = 0)
{
    global $current_user;

    if (($user_id == 0) && is_user_logged_in()) {
        $user_id = $current_user['id'];
    }

    $res = [];

    $subscriptions = get_results("SELECT * FROM tb_plan_user_subscriptions WHERE user_id = '{$user_id}'");
    foreach ($subscriptions as $sub)
    {
        $res[] = [
            'subscription' => $sub,
            'plan' => get_plan($sub['plan_id']),
        ];
    }

    return $res ?? [];
}

function get_subscription(int|string $subscription_id = 0)
{
    return get_result("SELECT * FROM tb_plan_user_subscriptions WHERE id = '{$subscription_id}'");
}

/**
 * Lists tb_plan_user_subscriptions rows joined with their customer
 * (tb_users) and plan (tb_plans), with search/sort/pagination -- backs both
 * the admin "All subscriptions" listing (custom-listings/subscriptions.php,
 * $attr without `user_id`) and, scoped to one customer via `user_id`, the
 * customer "My subscriptions" listing (custom-listings/my-subscriptions.php).
 *
 * Kept backward compatible in spirit with the old `get_subscriptions()`
 * (which just did `SELECT * FROM tb_plan_user_subscriptions`, no caller
 * anywhere in either plugin depended on that exact shape) -- calling with
 * no $attr now additionally joins customer/plan display columns instead of
 * returning bare tb_plan_user_subscriptions rows.
 *
 * @param string $mode '' (rows) | 'count'.
 * @param array  $attr {
 *     @type int    $user_id     Restrict to one customer's subscriptions (0 = every customer).
 *     @type string $search      Matches customer first/last name, e-mail, or plan name.
 *     @type string $order_field One of $sortable's keys below.
 *     @type string $order_dir   ASC|DESC.
 *     @type int    $limit       Omit for every matching row (unpaginated).
 *     @type int    $offset
 * }
 */
function get_subscriptions(string $mode = '', array $attr = [])
{
    $user_id     = (int) ($attr['user_id'] ?? 0);
    $search      = trim((string) ($attr['search'] ?? ''));
    $order_field = trim((string) ($attr['order_field'] ?? ''));
    $order_dir   = strtolower((string) ($attr['order_dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';
    $limit       = $attr['limit'] ?? null;
    $offset      = (int) ($attr['offset'] ?? 0);

    $where = [];

    if ($user_id > 0) {
        $where[] = "sub.user_id = '{$user_id}'";
    }

    if ($search !== '') {
        $needle  = addslashes($search);
        $where[] = "(u.first_name LIKE '%{$needle}%' OR u.last_name LIKE '%{$needle}%' OR u.email LIKE '%{$needle}%' OR p.name LIKE '%{$needle}%')";
    }

    $where_sql = !empty($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

    $base = "
        FROM tb_plan_user_subscriptions AS sub
        INNER JOIN tb_users AS u ON u.id = sub.user_id
        INNER JOIN tb_plans AS p ON p.id = sub.plan_id
        {$where_sql}
    ";

    if ($mode === 'count') {
        return (int) (get_result("SELECT COUNT(*) AS c {$base}")['c'] ?? 0);
    }

    // Whitelist -- $order_field comes straight from the listing's clicked
    // column header, never trusted directly into an ORDER BY clause.
    $sortable = [
        'id'              => 'sub.id',
        'auto_renew'      => 'sub.auto_renew',
        'status'          => 'sub.status',
        'customer_first_name' => 'u.first_name',
        'customer_email'  => 'u.email',
        'plan_name'       => 'p.name',
        'next_billing_at' => 'sub.next_billing_at',
        'cycles_quantity' => 'sub.cycles_quantity',
        'started_at'      => 'sub.started_at',
    ];
    $order_by = $sortable[$order_field] ?? 'sub.id';

    $sql = "
        SELECT
            sub.*,
            u.first_name        AS customer_first_name,
            u.last_name         AS customer_last_name,
            u.email             AS customer_email,
            p.name              AS plan_name,
            p.slug              AS plan_slug,
            p.currency          AS plan_currency,
            p.regular_price     AS plan_regular_price,
            p.sale_price        AS plan_sale_price,
            p.sale_price_cycles AS plan_sale_price_cycles,
            p.interval_unit     AS plan_interval_unit,
            p.interval_count    AS plan_interval_count
        {$base}
        ORDER BY {$order_by} {$order_dir}
    ";

    if ($limit !== null) {
        $limit  = max(1, (int) $limit);
        $offset = max(0, $offset);
        $sql   .= " LIMIT {$limit} OFFSET {$offset}";
    }

    return get_results($sql);
}
