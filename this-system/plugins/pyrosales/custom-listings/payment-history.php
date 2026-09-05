<?php
if (!isset($seg)) exit;

/**
 * "Histórico de pagamentos" listing -- loaded on demand by
 * custom_listing_table('plugin/pyrosales', 'payment-history') from
 * custom-pages/payment-history.php.
 *
 * Unlike custom-listings/orders.php (admin, sees every order), this one is
 * strictly scoped to the logged-in customer -- list_user_payment_history()
 * (src/payment_methods.php) filters by $current_user['id'] via an INNER
 * JOIN to tb_orders, the same idiom get_order()'s $only_current_user flag
 * and api.php's view-my-payment route already use. There is no
 * insert_button/actions here on purpose: a customer only ever reads their
 * own payment history, never edits it.
 */

require_once plugin_path('pyrosales/src/helpers.php');

return [
    'title' => 'Histórico de pagamentos',
    'crud_panel' => [
        'show_panel' => false,
        'show_name'  => true,
    ],
    'table'         => 'tb_order_payments',
    'get_data_by'   => 'function',
    'function_name' => function (array $args) {
        $result = list_user_payment_history([
            'q'      => $args['search'],
            'sort'   => $args['sort_field'] ?: 'created_at',
            'dir'    => $args['sort_dir'] ?: 'DESC',
            'limit'  => $args['limit'],
            'offset' => $args['offset'],
        ]);

        return ['total' => $result['total'], 'data' => $result['data']];
    },
    'fields' => [
        [
            'name'          => 'order_id',
            'label'         => 'ID',
            'function_view' => fn($value) => "#{$value}",
        ],
        [
            'name'          => 'created_at',
            'label'         => 'Data',
            'function_view' => fn($value) => date('d/m/Y', strtotime((string) $value)),
        ],
        [
            'name'          => 'first_item_name',
            'label'         => 'Item',
            'function_view' => function ($value, $row)
            {
                $label = e((string) ($value ?? ''));
                $extra = (int) ($row['total_items'] ?? 0) - 1;
                if ($extra > 0) {
                    $label .= " <span class='badge text-bg-secondary'>+{$extra}</span>";
                }

                return $label;
            },
        ],
        [
            'name'          => 'order_status_id',
            'label'         => 'Status',
            'function_view' => fn($value) => general_stats($value, 'order_status', 'button'),
        ],
        [
            'name'          => 'amount',
            'label'         => 'Valor',
            'function_view' => function ($value, $row) {
                $currency_code = strtoupper((string) ($row['currency'] ?? '')) ?: DEFAULT_CURRENCY;
                return trim($currency_code((float) $value));
            },
        ],
        [
            'name'          => 'method',
            'label'         => 'Meio de pagamento',
            'function_view' => function ($value, $row) {
                if (in_array($value, ['credit_card', 'debit_card'], true) && !empty($row['last4'])) {
                    return render_card_brand_html($row);
                }
                return e(ucfirst((string) $value));
            },
        ],
        [
            'name'          => 'statement_descriptor',
            'label'         => 'Nome fatura',
            'function_view' => function ($value, $row) {
                $descriptor    = e((string) ($row['statement_descriptor'] ?? ''));
                return trim($descriptor);
            },
        ],
    ],
];
