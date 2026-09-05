<?php
if (!isset($seg)) exit;

/**
 * plugin/plans-subscriptions/custom-listings/my-subscriptions.php
 *
 * Loaded on demand by load_custom_listing('plugin/plans-subscriptions', 'my-subscriptions').
 * Customer-facing "My subscriptions" listing -- same get_subscriptions()
 * (index.php) as the admin listing, scoped to the logged-in customer via
 * `listing_params.user_id` (see custom-pages/my-subscriptions.php).
 *
 * statement_descriptor is inline-editable (save on blur --
 * assets/scripts/my-subscriptions.js posts to update-subscription-field,
 * api.php). payment_method uses pyrosales' `user_payment_methods` input
 * (plugins/pyrosales/inputs/user_payment_methods) with `data-subscription-id`
 * passed through `attributes` -- its own dedicated script
 * (pyrosales/assets/scripts/user-payment-methods-input.js) saves on pick
 * (same update-subscription-field route) since this listing has no
 * wrapping <form> to submit. auto_renew reuses the same
 * toggle-subscription-auto-renew route the admin listing uses
 * (assets/scripts/subscriptions.js).
 *
 * renovar/pausar/cancelar render visible but disabled -- wiring each one up
 * is a separate, later task (cancelar is a template only for now, see
 * custom-pages/cancel-subscription.php).
 */

return [
    'table'         => 'tb_plan_user_subscriptions',
    'get_data_by'   => 'function',
    'function_name' => function (array $args) {
        $extra   = $args['extra'] ?? [];
        $filters = array_merge([
            'search'      => $args['search'],
            'order_field' => $args['sort_field'],
            'order_dir'   => $args['sort_dir'] ?? 'DESC',
            'limit'       => $args['limit'],
            'offset'      => $args['offset'],
        ], $extra); // extra carries the logged-in user_id -- see custom-pages/my-subscriptions.php

        return [
            'total' => get_subscriptions('count', $filters),
            'data'  => get_subscriptions('', $filters),
        ];
    },
    'fields' => [
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
        ['name' => 'plan_name', 'label' => 'Plano'],
        [
            'name'          => 'plan_interval_unit',
            'label'         => 'Recorrência',
            'function_view' => fn($value, $row) => format_subscription_recurrence((int)($row['plan_interval_count'] ?? 1), (string)$value),
        ],
        [
            'name'          => 'plan_regular_price',
            'label'         => 'Valor',
            'function_view' => fn($value, $row) => render_subscription_price_html($row, [
                'currency'          => $row['plan_currency'] ?? 'BRL',
                'regular_price'     => $row['plan_regular_price'] ?? 0,
                'sale_price'        => $row['plan_sale_price'] ?? null,
                'sale_price_cycles' => $row['plan_sale_price_cycles'] ?? 0,
            ]),
        ],
        [
            'name' => 'next_billing_at',
            'label' => 'Próxima cobrança',
            'function_view' => function ($value) {
                return format_date_long_ptbr($value, false);
            },
        ],
        [
            'name'          => 'statement_descriptor',
            'label'         => 'Nome fatura',
            'function_view' => fn($value, $row) => "<input type='text' class='form-control form-control-sm' maxlength='20' data-subscription-statement-descriptor='" . (int)$row['id'] . "' value='" . e((string)($value ?? '')) . "'>",
        ],
        [
            'name'          => 'user_payment_method_id',
            'label'         => 'Meio de pagamento',
            'function_view' => function ($value, $row) {
                static $cards = null;
                if ($cards === null) {
                    $cards = function_exists('list_user_saved_payment_methods')
                        ? list_user_saved_payment_methods((int) $row['user_id'], true)
                        : [];
                }

                return input('user_payment_methods', 'update', [
                    'name'         => 'user_payment_method_id',
                    'input_id'     => 'user_payment_method_id-' . (int) $row['id'],
                    'Value'        => $value,
                    'Options'      => $cards,
                    'show_default' => true,
                    'size'         => 'col-12',
                    'attributes'   => 'data-subscription-id:(' . (int) $row['id'] . ');',
                ]);
            },
        ],
        ['name' => 'cycles_quantity', 'label' => 'Ciclos'],
        ['name' => 'started_at', 'label' => 'Início', 'function_view' => 'format_date'],
        [
            'name'          => 'actions',
            'label'         => 'Botões',
            'function_view' => fn($value, $row) => render_subscription_customer_actions_html($row),
        ],
    ],
];
