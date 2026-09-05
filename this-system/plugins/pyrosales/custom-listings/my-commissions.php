<?php
if (!isset($seg)) exit;


return [
    'table'         => 'tb_orders',
    'get_data_by'   => 'function',
    'function_name' => function (array $args) {
        $result = list_orders([
            'q'         => $args['search'],
            'sort'      => $args['sort_field'] ?: 'created_at',
            'dir'       => $args['sort_dir'] ?: 'DESC',
            // 'dir'       => $args['sort_dir'],
            'limit'     => $args['limit'],
            'offset'    => $args['offset'],
            'vendor_id' => $GLOBALS['current_user']['id'] ?? 0,
        ]);

        return ['total' => $result['total'], 'data' => $result['orders']];
    },
    'fields' => [
        [
            'name'          => 'created_at',
            'label'         => 'Data',
            'function_view' => fn($value) => date('d/m/Y', strtotime((string) $value)),
        ],
        // [
        //     'name'  => 'total_amount',
        //     'label' => 'Data do pedido',
        //     'function_view' => fn($value, $row) =>
        //         is_callable($row['currency'] ?? null) ? ($row['currency'])($value) : $value,
        // ],
        [
            'name'          => 'commission_status_id',
            'label'         => 'Status comissão',
            'function_view' => fn($value, $row) => general_stats($value, 'commission_status', 'button'),
        ],
    ],
];
