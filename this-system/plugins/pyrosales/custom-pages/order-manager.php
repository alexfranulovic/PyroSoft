<?php
if (!isset($seg)) exit;

add_asset('head', "<link rel='stylesheet' href='". plugin_path('/pyrosales/assets/styles/pyrosales.css', 'url') ."'></script>");
add_asset('footer', "<script src='". plugin_path('/pyrosales/assets/scripts/pyrosales.js', 'url') ."' defer></script>");

include_once AREAS_PATH .'/admin/include/head.php';
include_once AREAS_PATH .'/admin/include/menu.php';

$id = id_by_get();

pageBaseTop();
?>

<!--main-container-part-->
<main class="row m-0" role="main">
<?php
$manager = 'order-manager';
if (load_permission($manager))
$hooks_out[] = [
    'title' => 'Cadastrar',
    'url'  => get_url_page($manager, 'full'),
    'color' => 'outline-success',
    'pre_icon' => 'fas fa-plus',
];

$order = get_order($id ?? 0);
// dump($order);

$type_form = (count($order) == 0)
    ? 'insert'
    : 'update';

$items = $order['items'] ?? [];
$coupon_lines = $order['coupon_lines'] ?? [];
$fee_lines = $order['fee_lines'] ?? [];
$payments = $order['payments'] ?? [];
$order = $order['order'] ?? [];
$currency = $order['currency'] ?? DEFAULT_CURRENCY;

if ($type_form == 'update')
{
    $panel['hooks_out'][] = [
        'title' => 'Listar',
        'url'  => get_url_page('list-orders', 'full'),
        'color' => 'outline-info',
        'pre_icon' => 'fas fa-list',
    ];

    $panel['hooks_out'][] = [
        'title' => 'Duplicar',
        'url'  => rest_api_route_url("duplicate-record?id={$id}&table=tb_orders&foreign_key=order_id"),
        'attr'  => 'data-controller: (duplicate);',
        'color' => 'outline-info',
        'pre_icon' => 'fas fa-copy',
    ];

    $panel['hooks_out'][] = [
        'title' => 'Apagar',
        'url'  => rest_api_route_url("delete-record?id={$id}&table=tb_orders&foreign_key=order_id"),
        'attr'  => 'data-controller: (delete);',
        'color' => 'outline-danger',
        'pre_icon' => 'fas fa-trash',
    ];
}

echo crud_panel( $panel ?? [] );
?>

<div class="row">
<div class="col-md-8">

    <div class="card p-0 box-fields">
    <div class="card-header">
        <h6 class="mb-0">Details</h6>
    </div>
    <div class="card-body">
    <div class="form-row">

        <?php
        echo input('basic', $type_form, [
            'size' => 'col-md-6',
            'label' => 'Order number',
            'name' => 'id',
            'Value' => $order['id'] ?? '',
            'disabled' => ($type_form=='update'),
        ]).
        input('selection_type', $type_form, [
            'size' => 'col-md-6',
            'label' => 'Type',
            'name' => 'order_type',
            'Options' => [
                [
                    'value' => 'product',
                    'display' => 'Product',
                ],
                [
                    'value' => 'plan',
                    'display' => 'Plan',
                ],
                [
                    'value' => 'one_off',
                    'display' => 'One off',
                ],
            ],
            'Value' => ($type_form=='update') ? $order['order_type'] : '',
            'Required' => true
        ]);

        echo
        input('selection_type', $type_form, [
            'type' => 'radio',
            'size' => 'col-12',
            'label' => 'Status',
            'variation' => 'balloons',
            'name' => 'status_id',
            'Options' => order_status(true),
            'Value' => ($type_form=='update') ? $order['status_id'] : '',
            'Required' => true
        ]);
        ?>

    </div>
    </div>
    </div>

    <?php if ($type_form == 'update' && $order['requires_address']) : ?>
    <div class="card p-0 box-fields">
    <div class="card-header">
        <h6 class="mb-0">Shipping</h6>
    </div>
    <div class="card-body">
    <div class="form-row">
        <?php
        echo input('address_form', $type_form, [
            'Value' => $order['address'] ?? '',
        ]);
        ?>
    </div>
    </div>
    </div>
    <?php endif; ?>



    <?php
    foreach ($items as $key => $value)
    {
        $items_formatted[] = [
            'item_name' => $value['item_name'],
            'quantity' => "{$value['quantity']}x",
            'unit_price' => $currency($value['unit_price']),
            'line_total' => $currency($value['line_total']),
        ];
    }

    $table = [
        'crud_panel' => [
            'form_name' => 'Items',
            'show_name' => true,
            'show_panel' => true,
        ],
        'head' => [
            'Item',
            'Quantity',
            'Unit Price',
            'Subtotal',
        ],
        'body' => $items_formatted ?? [],
    ];
    echo table($table);
    ?>


    <?php
    foreach ($payments as $key => $value)
    {
        $payment_formatted[] = [
            'status_id' => general_stats($value['status_id'], 'payment_status'),
            'provider' => $value['provider'],
            'method' => $value['method'],
            'amount' => $currency($value['amount']),
            'action' => "<button type='button' class='btn btn-outline-info btn-sm' payment-id='{$value['id']}'>". icon('fas fa-eye') ."</button>"
        ];
    }

    $table = [
        'crud_panel' => [
            'form_name' => 'Payments',
            'show_name' => true,
            'show_panel' => true,
        ],
        'head' => [
            'Status',
            'Provider',
            'Method',
            'Amount',
            '',
        ],
        'body' => $payment_formatted ?? [],
    ];
    echo table($table);

    echo block('modal', [
        'id' => 'view-payment',
        'title' => 'Payment detail',
        'close_button' => true,
        'body' => 'teste',
    ]);
    ?>

</div>

<div class="col-md-4">

    <div class="card p-0 box-fields">
    <div class="card-body">
    <div class="form-row">

        <?php
        echo input('submit_button', $type_form, [
            'size' => 'col-12',
            'class' => 'btn btn-st',
            'block' => true,
            'Value' => ($type_form == 'update') ? 'Update' : 'Insert'
        ]);

        echo build_order_total($order);
        ?>

    </div>
    </div>
    </div>

    <?php if ($type_form == 'update') : ?>
    <div class="card p-0 box-fields">
    <div class="card-body">
    <div class="form-row">

        <section class='col-12'>
        <div class="subject-data-list">
            <p>Order date: <bdi><?= date("d/m/y", strtotime($order['created_at'])) ?></bdi></p>
            <p>Last modified at: <bdi><?= date("d/m/y", strtotime($order['updated_at'])) ?></bdi></p>
        </div>
        </section>

        <?php
        echo "<section class='col-xl-6'>". order_customer_data($order) ."</section>";
        echo "<section class='col-xl-6'>". order_headers_data($order) ."</section>";
        ?>

    </div>
    </div>
    </div>
    <?php endif; ?>

</div>

</div>
</main>

<?php include_once AREAS_PATH .'/admin/include/script_libs.php'; ?>
