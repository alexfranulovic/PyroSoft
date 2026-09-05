<?php
if (!isset($seg)) exit;


$GLOBALS['subscription_target_audience']+= [];

$GLOBALS['alerts']+=
[
    'SC_TO_UPDATE_STATEMENT_DESCRIPTOR' => [
        'color' => 'success', 'close_button' => true,
        'title' => 'Sucesso!',
        'body' => 'Nome da fatura alterado alterado com sucesso'
    ],
    'SC_TO_UPDATE_PAYMENT_METHOD' => [
        'color' => 'success', 'close_button' => true,
        'title' => 'Sucesso!',
        'body' => 'Método de pagamento alterado alterado com sucesso'
    ],
    'SC_TO_UPDATE_PAYMENT_SETTINGS' => [
        'color' => 'success', 'close_button' => true,
        'title' => 'Sucesso!',
        'body' => 'Dados de pagamento atualizados com sucesso'
    ],
];

require_once __DIR__ .'/src/ui.php';
require_once __DIR__ .'/src/status.php';
require_once __DIR__ .'/src/subscriptions.php';


/**
 * get_plans() — extended with the params needed for the listings feature,
 * kept backward compatible with existing callers:
 *
 *   - Old calls with no $attr (`get_plans()`, `get_plans('list')`) behave
 *     exactly as before: `d2c` defaults to `true`, which reproduces the
 *     original hardcoded `WHERE user_id IS NULL` — nothing changes for
 *     any code that already calls this function today.
 *   - `d2c` (bool, default true): adds `user_id IS NULL` when true. Pass
 *     `false` to include plans that belong to a specific user.
 *   - `target_audience` / `interval_count` / `interval_unit` (optional,
 *     exact match): scopes to one segment group — this is what the
 *     grouped plans listing (one table per group) filters by.
 *   - `segment_intervals` (bool, default false): forces
 *     `ORDER BY order_reg, interval_unit, interval_count ASC`, overriding
 *     `order_field`/`order_dir` when both are given.
 *   - `search` (string): matches against name/slug/target_audience.
 *   - `order_field` / `order_dir`: single-column sort (ignored when
 *     `segment_intervals` is true).
 *   - `limit` / `offset`: SQL-level pagination — omit `limit` to get every
 *     matching row (unpaginated), same as the original function always did.
 *   - `mode = 'count'` (new): returns just the matching row count (int),
 *     using the exact same filters as the row-fetching branch — this is
 *     what the listings feature uses to report recordsTotal/recordsFiltered
 *     without duplicating the WHERE-building logic in a second place.
 */
function get_plans(string $mode = '', array $attr = [])
{
    $id                = $attr['id'] ?? '';
    $d2c               = $attr['d2c'] ?? true;
    $target_audience   = $attr['target_audience'] ?? null;
    $interval_count    = $attr['interval_count'] ?? null;
    $interval_unit     = $attr['interval_unit'] ?? null;
    $segment_intervals = $attr['segment_intervals'] ?? false;
    $search            = $attr['search'] ?? '';
    $order_field       = $attr['order_field'] ?? null;
    $order_dir         = strtolower($attr['order_dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';
    $limit             = $attr['limit'] ?? null;
    $offset            = (int) ($attr['offset'] ?? 0);

    $where     = [];
    $raw_where = [];

    if ($d2c) {
        // Not a typical "field = value" clause (it's a NULL check), so this
        // goes through raw_where instead of guessing how safe_where()
        // handles an operator/value pair for IS NULL.
        $raw_where[] = 'user_id IS NULL';
    }

    if (!empty($id)) {
        $where[] = ['field' => 'id', 'operator' => '=', 'value' => (int) $id];
    }

    if ($target_audience !== null) {
        $where[] = ['field' => 'target_audience', 'operator' => '=', 'value' => (string) $target_audience];
    }
    if ($interval_count !== null) {
        $where[] = ['field' => 'interval_count', 'operator' => '=', 'value' => (int) $interval_count];
    }
    if ($interval_unit !== null) {
        $where[] = ['field' => 'interval_unit', 'operator' => '=', 'value' => (string) $interval_unit];
    }

    if (!empty($search)) {
        $needle = addslashes($search);
        $raw_where[] = "(name LIKE '%{$needle}%' OR slug LIKE '%{$needle}%' OR target_audience LIKE '%{$needle}%')";
    }

    if ($mode === 'count') {
        return (int) (get_result(query_builder([
            'table'       => 'tb_plans',
            'fields'      => ['COUNT(*) AS c'],
            'where'       => $where,
            'where_logic' => 'AND',
            'raw_where'   => $raw_where,
        ]))['c'] ?? 0);
    }

    $order_by = [];
    if ($segment_intervals) {
        $order_by = [
            ['field' => 'order_reg', 'way' => 'ASC'],
            ['field' => 'interval_unit', 'way' => 'ASC'],
            ['field' => 'interval_count', 'way' => 'ASC'],
        ];
    } elseif (!empty($order_field)) {
        $order_by = [['field' => $order_field, 'way' => $order_dir]];
    } else {
        // Default when nothing was explicitly requested: order_reg ASC —
        // matches drag-and-drop position. Without this, no ORDER BY at all
        // was applied, so a freshly-loaded group table (no column sort
        // clicked yet) came back in arbitrary MySQL order instead of the
        // order the plans were actually dragged into.
        $order_by = [['field' => 'order_reg', 'way' => 'ASC']];
    }

    $query_params = [
        'table'       => 'tb_plans',
        'fields'      => $mode === 'list' ? ['id AS value', "CONCAT(
                                    '#',
                                    id,
                                    ' - ',
                                    target_audience,
                                    ': ',
                                    name,
                                    ' | ',
                                    interval_count,
                                    'x ',
                                    interval_unit
                                ) AS display"] : ['*'],
        'where'       => $where,
        'where_logic' => 'AND',
        'raw_where'   => $raw_where,
        'order_by'    => $mode === 'list' ? [
            ['field' => 'target_audience', 'way' => "ASC"],
            ['field' => 'order_reg', 'way' => "ASC"],
        ] : $order_by,
    ];

    if ($limit !== null) {
        $limit = max(1, (int) $limit);
        $query_params['registers_per_page'] = $limit;
        $query_params['current_page']       = floor($offset / $limit) + 1;
    }

    return get_results(query_builder($query_params));
}

function get_plan(int|string $value = '')
{
    return get_result("
    SELECT *
    FROM tb_plans
    WHERE
        id = '{$value}'
        OR slug = '{$value}'");
}


function load_plan_form()
{
    global $seg;
    require_once __DIR__ .'/src/plan-manager.php';
}

function load_subscription_form()
{
    global $seg;
    require_once __DIR__ .'/src/subscription-manager.php';
}

/**
 * Get role IDs linked to a plan.
 *
 * @param int $plan_id The plan ID.
 *
 * @return array
 */
function get_plan_role_ids(int $plan_id): array
{
    $roles = get_results("SELECT role_id FROM tb_plan_roles WHERE plan_id = '{$plan_id}'");
    return array_map('intval', array_column($roles, 'role_id'));
}


/**
 * Sync plan roles.
 *
 * @param int   $plan_id   The plan ID.
 * @param array $role_ids  List of role IDs.
 * @param bool  $debug     Enable debug mode.
 *
 * @return bool
 */
function sync_plan_roles(int $plan_id, array $role_ids = [], bool $debug = false): bool
{
    $role_ids = array_unique(array_filter(array_map('intval', $role_ids)));

    delete_record([
        'table'           => 'tb_plan_roles',
        'where_field'     => 'plan_id',
        'where_value'     => $plan_id,
    ]);

    foreach ($role_ids as $role_id)
    {
        insert('tb_plan_roles', [
            'plan_id' => $plan_id,
            'role_id' => $role_id,
        ], false, $debug);
    }

    return true;
}

// dump( get_plans() );
