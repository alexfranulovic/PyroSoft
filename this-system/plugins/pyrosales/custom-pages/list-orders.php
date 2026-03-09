<?php
if (!isset($seg)) exit;

include_once AREAS_PATH .'/admin/include/head.php';
include_once AREAS_PATH .'/admin/include/menu.php';

$Table       = 'tb_orders';
$foreign_key = 'order_id';

pageBaseTop();
?>

<!--main-container-part-->
<main class="row m-0" role="main">
<?php
$manager = 'order-manager';
$controller = "redirect=true&table=$Table&permission_id=manage-pages&foreign_key={$foreign_key}&id=";


$table_actions = [
    'edit'        => [ 'permission' => load_permission($manager), 'url' => get_url_page($manager, 'full') ],
    'delete'      => [ 'permission' => true, 'url' => $controller ],
];


if (load_permission($manager))
$hooks_out[] = [
    'title' => 'Cadastrar',
    'url'  => get_url_page($manager, 'full'),
    'color' => 'outline-success',
    'pre_icon' => 'fas fa-plus',
];


$orders = list_orders()['orders'];
foreach ($orders as $key => $order)
{
    $row = [];

    $currency = $order['currency'] ?? '';

    $row[] = $order['id'];
    $row[] = "{$order['customer_first_name']} {$order['customer_last_name']}";
    $row[] = status_buttons($order['id'], $order['status_id'], 'tb_orders', 'order_status');
    $row[] = $currency;
    $row[] = $currency($order['total_amount']);
    $row[] = $order['order_type'];
    $row[] = format_datetime($order['created_at']);

    $row[] = build_table_actions($table_actions, $order['id']);

    $body[] = $row;
}


$table = [
    'data_table' => true,
    'crud_panel' => [
        'show_panel' => true,
        'hooks_out' => $hooks_out ?? [],
    ],
    'head' => [
        'ID', 'Customer', 'Status', 'Currency', 'Total Amount', 'Type', 'Date', 'Ações',
    ],
    'body' => $body ?? [],
];
echo table($table);
?>
</main>

<?php include_once AREAS_PATH .'/admin/include/script_libs.php'; ?>
