<?php
if (!isset($seg)) exit;

/**
 * plugin/pyrosales/custom-listings/plans.php
 *
 * Loaded on demand by load_custom_listing('plugin/pyrosales', 'plans').
 * Guessed this belongs under plugin/pyrosales, same as orders — move it to
 * feature/... or this-system/... if that's wrong, nothing else changes.
 *
 * `function_name` adapts get_plans()'s own (mode, $attr) signature to the
 * standardized args this feature calls with. `$args['extra']` carries
 * whatever was passed as `listing_params` to custom_listing_table() — the
 * grouped-plans page (see plans-segments.php) uses this to scope one
 * rendered table to a single target_audience + interval_count +
 * interval_unit group; called without listing_params, it just lists
 * everything (extra is an empty array, so those three filters are simply
 * absent from get_plans()'s $attr).
 *
 * `orderable` + `order_endpoint`: drag-and-drop reordering is enabled,
 * pointed at the `order-plans` REST route (see order-plans.php), which
 * only reorders within the same target_audience/interval_count/
 * interval_unit group as the dragged plan.
 */

$plan_manager    = 'plan-manager';
$plans_controller = "redirect=true&table=tb_plans&permission_id={$plan_manager}&foreign_key=plan_id&id=";


return [
    'crud_panel' => [
        'show_panel' => true,
        'show_name'  => true,
    ],
    'table'         => 'tb_plans',
    'get_data_by'   => 'function',
    'function_name' => function (array $args) {
        $extra = $args['extra'] ?? [];

        $filters = array_merge([
            'd2c'         => true, // change to false if the admin listing should include user-owned plans too
            'search'      => $args['search'],
            'order_field' => $args['sort_field'],
            'order_dir'   => $args['sort_dir'],
            'limit'       => $args['limit'],
            'offset'      => $args['offset'],
        ], $extra); // extra can include target_audience / interval_count / interval_unit

        return [
            'total' => get_plans('count', $filters),
            'data'  => get_plans('', $filters),
        ];
    },
    'orderable'      => true,
    'order_endpoint' => 'order-plans',
    'fields'         => [
        ['name' => 'id', 'label' => 'ID'],
        // ['name' => 'order_reg', 'label' => 'Order'],
        ['name' => 'name', 'label' => 'Nome'],
        ['name' => 'currency', 'label' => 'Moeda'],
        [
            'name'          => 'regular_price',
            'label'         => 'Preço regular',
            'function_view' => fn($value, $row) => ($row['currency'] ?? '') . ' ' . number_format((float) $value, 2, ',', '.'),
        ],
        [
            'name'          => 'sale_price',
            'label'         => 'Preço promocional',
            'function_view' => fn($value, $row) => $value === null
                ? '-'
                : ($row['currency'] ?? '') . ' ' . number_format((float) $value, 2, ',', '.'),
        ],
        ['name' => 'target_audience', 'label' => 'Público-alvo'],
        [
            'name'          => 'trial_days',
            'label'         => 'Dias de teste',
            'function_view' => fn($value) => $value > 0 ? "{$value} dias" : '-',
        ],
        [
            'name'          => 'status_id',
            'label'         => 'Status',
            'function_view' => fn($value, $row) => status_buttons($row['id'], $value, 'tb_plans'),
        ],
    ],
    'actions' => [
        'edit'   => ['permission' => load_permission($plan_manager), 'url' => get_url_page($plan_manager, 'full')],
        'delete' => ['permission' => true, 'url' => $plans_controller],
    ],
    'insert_button' => [
        'permission' => load_permission($plan_manager),
        'title'      => 'Cadastrar',
        'url'        => get_url_page('plan-manager', 'full'),
    ],
    // pages.php/orders.php do (build_table_actions() + insert_button)
    // once there's an edit/delete flow for plans.
];
