<?php
if (!isset($seg)) exit;

/**
 * plugin/plans-subscriptions/custom-listings/subscriptions.php
 *
 * Loaded on demand by load_custom_listing('plugin/plans-subscriptions', 'subscriptions').
 * Admin "All subscriptions" listing -- every tb_plan_user_subscriptions row,
 * joined with its customer (tb_users) and plan (tb_plans) via get_subscriptions()
 * (index.php). `function_name` adapts get_subscriptions()'s (mode, $attr)
 * signature to the standardized args this feature calls with.
 *
 * Cobrar/pausar/cancelar/excluir (the "Botões" column) render visible but
 * disabled -- see render_subscription_admin_actions_html() (index.php).
 * Wiring each one up is a separate, later task.
 */

$subscription_manager = 'subscription-manager';
$subscription_controller = "redirect=true&table=tb_plan_user_subscriptions&permission_id={$subscription_manager}&foreign_key=subscription_id&id=";


return [
    'crud_panel' => [
        'show_panel' => true,
        'show_name'  => true,
    ],
    'table'         => 'tb_plan_user_subscriptions',
    'get_data_by'   => 'function',
    'function_name' => function (array $args) {
        $filters = [
            'search'      => $args['search'],
            'order_field' => $args['sort_field'],
            'order_dir'   => $args['sort_dir'] ?? 'DESC',
            'limit'       => $args['limit'],
            'offset'      => $args['offset'],
        ];

        return [
            'total' => get_subscriptions('count', $filters),
            'data'  => get_subscriptions('', $filters),
        ];
    },
    'fields' => [
        ['name' => 'id', 'label' => 'ID'],
        // [
        //     'name'          => 'auto_renew',
        //     'label'         => 'Renovação automática',
        //     'function_view' => fn($value, $row) => render_subscription_auto_renew_switch_html($row),
        // ],
        [
            'name'          => 'status',
            'label'         => 'Status',
            'function_view' => fn($value) => general_stats($value, 'subscription_status', 'button'),
        ],
        [
            'name'          => 'customer_first_name',
            'label'         => 'Customer',
            'function_view' => function ($value, $row) {
                $name = e(trim("{$value} " . ($row['customer_last_name'] ?? '')));

                // Links to the user's subscription journey timeline -- this
                // same listing page doubles as the timeline view when called
                // with ?user_id= (see custom-pages/subscriptions.php) -- only
                // meaningful under d2c_single_plan (see render_user_plans_timeline_html(), src/ui.php).
                if (get_system_info('subscriptions_business_model') == 'd2c_single_plan') {
                    $url = get_url_page('subscriptions', 'full') . '?user_id=' . (int) $row['user_id'];
                    return "<a href='{$url}' title='Ver jornada de assinaturas'>{$name} " . icon('fas fa-timeline') . "</a>";
                }

                return $name;
            },
        ],
        ['name' => 'customer_email', 'label' => 'E-mail customer'],
        [
            'name'          => 'plan_name',
            'label'         => 'Nome plano',
            'function_view' => fn($value, $row) => "<a href='" . get_url_page('plan-manager', 'full') . "?id=" . (int)$row['plan_id'] . "'>" . e((string)$value) . "</a>",
        ],
        [
            'name'          => 'plan_regular_price',
            'label'         => 'Preço plano',
            'function_view' => fn($value, $row) => render_subscription_price_html($row, [
                'currency'          => $row['plan_currency'] ?? 'BRL',
                'regular_price'     => $row['plan_regular_price'] ?? 0,
                'sale_price'        => $row['plan_sale_price'] ?? null,
                'sale_price_cycles' => $row['plan_sale_price_cycles'] ?? 0,
            ]),
        ],
        ['name' => 'next_billing_at', 'label' => 'Próxima cobrança', 'function_view' => 'format_date'],
        ['name' => 'cycles_quantity', 'label' => 'Ciclos'],
        ['name' => 'started_at', 'label' => 'Início', 'function_view' => 'format_date'],
        [
            'name'          => 'actions',
            'label'         => 'Botões',
            'function_view' => fn($value, $row) => render_subscription_admin_actions_html($row),
        ],
    ],
    'actions' => [
        'edit' => ['permission' => load_permission($subscription_manager), 'url' => get_url_page('subscription-manager', 'full')],
        'delete' => ['permission' => true, 'url' => $subscription_controller],
    ],
    'insert_button' => [
        'permission' => load_permission($subscription_manager),
        'title'      => 'Cadastrar',
        'url'        => get_url_page('subscription-manager', 'full'),
    ],
];
