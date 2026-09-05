<?php
if (!isset($seg)) exit;

/**
 * plugin/pyrosales/custom-listings/orders.php
 *
 * Loaded on demand by load_custom_listing('plugin/pyrosales', 'orders').
 * `function_name` is a closure adapting list_orders()'s own param names
 * (q/sort/dir/limit/offset) and return shape (`orders` key) to the
 * standardized args this feature calls it with.
 */

$orders_manager    = 'order-manager';
$orders_controller = "redirect=true&table=tb_orders&permission_id={$orders_manager}&foreign_key=order_id&id=";

return [
    'table'         => 'tb_orders',
    'get_data_by'   => 'function',
    'function_name' => function (array $args) {
        $result = list_orders([
            'q'      => $args['search'],
            'sort'   => $args['sort_field'] ?: 'created_at',
            'dir'    => $args['sort_dir'] ?: 'DESC',
            // 'dir'    => $args['sort_dir'],
            'limit'  => $args['limit'],
            'offset' => $args['offset'],
        ]);

        return ['total' => $result['total'], 'data' => $result['orders']];
    },
    'fields' => [
        ['name' => 'id', 'label' => 'ID'],
        ['name' => 'created_at', 'label' => 'Date', 'function_view' => 'format_datetime'],
        [
            'name'          => 'customer_first_name', // drives search/sort; display combines it with the last name
            'label'         => 'Customer',
            'function_view' => fn($value, $row) => "{$value} {$row['customer_last_name']}",
        ],
        [
            'name'          => 'customer_document_number',
            'label'         => 'Customer document',
            'function_view' => fn($value, $row) => format_cnpj_cpf($value),
        ],
        ['name' => 'customer_email', 'label' => 'Customer e-mail'],
        [
            'name'          => 'status_id',
            'label'         => 'Order status',
            'function_view' => fn($value, $row) => status_buttons($row['id'], $value, 'tb_orders', 'order_status'),
        ],
        [
            'name'          => 'payment_status_id',
            'label'         => 'Payment status',
            'function_view' => fn($value, $row) => general_stats($value, 'payment_status', 'button'),
        ],
        ['name' => 'order_purpose', 'label' => 'Purpose'],
        ['name' => 'currency', 'label' => 'Currency'],
        [
            'name'  => 'total_amount',
            'label' => 'Total Amount',
            'function_view' => fn($value, $row) =>
                is_callable($row['currency'] ?? null) ? ($row['currency'])($value) : $value,
        ],
        // list_orders() now joins tb_order_payments and returns this
        // directly on each row (as `method`, via MIN(op.method) in the
        // SELECT) — no extra per-row query needed anymore.
        ['name' => 'method', 'label' => 'Method'],
        ['name' => 'order_type', 'label' => 'Type'],
    ],
    // Only edit + delete today — no order/duplicate, same as list-orders.php.
    'actions' => [
        'edit'   => ['permission' => load_permission($orders_manager), 'url' => get_url_page($orders_manager, 'full')],
        'delete' => ['permission' => true, 'url' => $orders_controller],
    ],
    'insert_button' => [
        'permission' => load_permission($orders_manager),
        'title'      => 'Cadastrar',
        'url'        => get_url_page($orders_manager, 'full'),
    ],

];
