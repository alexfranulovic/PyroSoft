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
$utm_data = $order['utm_data'] ?? null;
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

if (empty($order)) :
    echo alert_message("IF_NONEXISTENT_ID", 'alert');
else:

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
        echo input('copy', $type_form, [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Order number',
            'name' => 'id',
            'Value' => $order['id'] ?? '',
            'disabled' => ($type_form=='update'),
        ]).
        input('selection_type', $type_form, [
            'size' => 'col-md col-lg-4',
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
        ]).
        input('basic', $type_form, [
            'size' => 'col-lg-4',
            'label' => 'Purpose',
            'name' => 'order_purpose',
            'Value' => $order['order_purpose'] ?? 'charge',
            'disabled' => ($type_form=='update'),
        ]);

        echo
        input('selection_type', $type_form, [
            // 'type' => 'radio',
            'size' => 'col-lg-6',
            'label' => 'Order status',
            // 'variation' => 'balloons',
            'name' => 'status_id',
            'Options' => order_status(true, 'title'),
            'Value' => ($type_form=='update') ? $order['status_id'] : '',
            'Required' => true
        ]);
        echo "<section class='col-lg-6'>
            ". order_customer_data($order) ."
        </section>";


        if ($order['order_type'] == 'plan')
        {
            echo "
            <a target='_blank' href='". get_url_page('subscription-manager') ."?id={$order['subscription_id']}'>
                Subscription details ". icon('fas fa-arrow-right') ."
            </a>";
        }
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
            'regular_unit_price' => $currency($value['regular_unit_price']),
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
            'Regular price',
            'Sale Price',
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
            'id' => "#".$value['id'],
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
            'ID',
            'Status',
            'Provider',
            'Method',
            'Amount',
            '',
        ],
        'body' => $payment_formatted ?? [],
    ];
    echo table($table);
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

        <button class="btn btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#notes" aria-controls="notes">Order notes</button>
        <button class="btn btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#order-headers-utm" aria-controls="order-headers-utm">Headers &amp; UTM</button>

        <section class='col-12'>
        <div class="subject-data-list">
            <p><strong>Order date: <bdi><?= date("d/m/y H:i", strtotime($order['created_at'])) ?></bdi></strong></p>
            <p><strong>Last modified at: <bdi><?= date("d/m/y H:i", strtotime($order['updated_at'])) ?></bdi></strong></p>
        </div>
        </section>

    </div>
    </div>
    </div>

    <?php if (!empty($order['vendor_id'])) : ?>
        <div class="card p-0 box-fields">
        <div class="card-body">
        <div class="form-row">
            <?php
            /**
             * Vendor commission control -- the settlement state
             * (commission_status_id) plus the hook that releases it
             * (commission_activation_function, frozen on the order). Both go
             * through update_order()'s whitelist; the status is otherwise
             * moved automatically by resolve_order_commission_status() once
             * the order is paid (src/status.php).
             */
            echo "<section class='col-12'>
                ". order_vendor_data($order) ."
            </section>".
            input('selection_type', $type_form, [
                'size' => 'col-12',
                'label' => 'Commission status',
                'name' => 'commission_status_id',
                'Options' => commission_status(true, 'title'),
                'Value' => ($type_form == 'update') ? ($order['commission_status_id'] ?? 'pending') : 'pending',
            ]).
            input('textarea', $type_form, [
                'size' => 'col-12',
                'label' => 'Commission activation function',
                'name' => 'commission_activation_function',
                'Value' => $order['commission_activation_function'] ?? '',
                'disabled' => true,
            ]);
            ?>
        </div>
        </div>
        </div>
    <?php endif; ?>

    <?php endif; ?>

</div>

</div>
</main>



<?php
// Which note body-types are shown by default when the panel opens.
$default_show = [
  '.body-type-accordion' => false,
  '.body-type-alert' => true,
  '.private' => true,
  '.to-customer, .from-customer' => true,
];

$note_filter_meta = [
  '.body-type-accordion' => ['icon' => 'fas fa-robot', 'label' => 'System / raw payload'],
  '.body-type-alert' => ['icon' => 'fas fa-triangle-exclamation', 'label' => 'Alerts'],
  '.private' => ['icon' => 'fas fa-note-sticky', 'label' => 'Private note'],
  '.to-customer, .from-customer' => ['icon' => 'fas fa-message', 'label' => 'Messages'],
];
?>

<section class="offcanvas offcanvas-end" tabindex="-1" id="notes" aria-labelledby="notesLabel">
<div class="offcanvas-header">
    <h5 class="offcanvas-title" id="notesLabel">Order notes</h5>
    <div class="dropdown">
      <a class="btn btn-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
        <?= icon('fas fa-filter') ?>
      </a>

      <ul class="dropdown-menu note-filters">
        <p>Display</p>
        <?php foreach ($default_show as $selector => $show):
            $meta = $note_filter_meta[$selector] ?? ['icon' => 'fas fa-circle', 'label' => $selector];
            $switch_id = 'note-filter-' . trim(preg_replace('/[^a-z0-9]+/i', '-', $selector), '-');
        ?>
        <li>
        <?= input('selection_type', 'insert', [
            'type' => 'switch',
            'size' => 'col-12',
            'Options' => [
                [
                    'value' => e($selector),
                    'display' => icon($meta['icon']) ." ". e($meta['label']),
                    'checked' => $show,
                    'attributes' => "data-note-filter:($selector);",
                ]
            ]
        ]) ?>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
</div>

<?php
$notes = get_order_notes([
    'order_id'     => $id,
    'show_private' => true,
    'note_type'    => []
]);

// Owner of this order — used to tell staff messages (to-customer) apart
// from messages actually written by the customer (from-customer).
$order_owner_id = (int) ($order['user_id'] ?? 0);
?>

<?php if (!empty($notes)): ?>
<div class="offcanvas-body list-notes">
<div class="dialog">

    <?php
    $last_date = null;
    foreach ($notes as $i => $note)
    {
        $created_at = $note['created_at'] ?? null;
        $date_key   = date('Y-m-d', strtotime($created_at));
        $today_key  = date('Y-m-d');

        if ($date_key !== $last_date)
        {
            $date_label = ($date_key === $today_key)
                ? 'TODAY'
                : date('l, d/m/Y', strtotime($created_at));

            echo "<span class='badge date'>" . e($date_label) . "</span>";

            $last_date = $date_key;
        }

        $author = $note['first_name'] ?: 'System';
        $time   = date('H:i', strtotime($created_at));
        $title  = $note['title'] ?: ucfirst(str_replace('_', ' ', $note['note_type']));

        // A customer-visible note actually written by the order owner
        // renders as a reply from the customer, not a staff message.
        $is_from_customer = $order_owner_id > 0
            && (int) ($note['created_by'] ?? 0) === $order_owner_id;

        $icon = 'fas fa-note-sticky';
        if ($note['note_type'] === 'gateway_payload' || empty($note['created_by'])) {
            $icon = 'fas fa-robot';
        } elseif ($is_from_customer) {
            $icon = 'fas fa-user';
        } elseif ($note['visibility'] === 'customer') {
            $icon = 'fas fa-message';
        }

        if ($note['body_type'] === 'accordion')
        {
            $html = "
            <span class='created-by'>" . icon($icon) . " " . e($author) . "</span>
            <div class='accordion' id='accordion-note-{$note['id']}'>
            <div class='accordion-item'>
                <span class='accordion-header'>
                <button class='accordion-button collapsed' type='button' data-bs-toggle='collapse' data-bs-target='#collapse-note-{$note['id']}' aria-expanded='false' aria-controls='collapse-note-{$note['id']}'>
                    " . e($title) . "
                </button>
                </span>

                <div id='collapse-note-{$note['id']}' class='accordion-collapse collapse' data-bs-parent='#accordion-note-{$note['id']}'>
                    <pre class='accordion-body'><code>" . e($note['content']) . "</code></pre>
                </div>
            </div>
            </div>
            <p class='time' title='Sent at {$time}'>{$time}</p>";

            echo block('alert', [
                'class'     => 'body-type-accordion',
                'body'      => $html,
                'variation' => 'alert-disclaimer',
                'color'     => 'primary'
            ]);

            continue;
        }

        if ($note['body_type'] === 'alert')
        {
            $author = 'Alerts';
            $icon   = 'fas fa-triangle-exclamation';

            $html = "
            <span class='created-by'>" . icon($icon) . " " . e($author) . "</span>
            <p class='content'>" . format_text($note['content']) . "</p>
            <p class='time' title='Sent at {$time}'>{$time}</p>";

            echo block('alert', [
                'class'     => 'body-type-alert',
                'body'      => $html,
                'variation' => 'alert-disclaimer',
                'color'     => ['background' => 'primary']
            ]);

            continue;
        }

        $class = $note['visibility'] === 'customer'
            ? ($is_from_customer ? 'from-customer' : 'to-customer')
            : 'private';

        echo "
        <article class='{$class} body-type-message'>
            <span title='Note by " . e($author) . "' class='created-by'>" . icon($icon) . " " . e($author) . "</span>
            <p class='content'>" . format_text($note['content']) . "</p>
            <p class='time' title='Sent at {$time}'>{$time}</p>
        </article>";
    }
    ?>
</div>
</div>
<?php endif; ?>

<?php if (empty($notes)): ?>
<div class="offcanvas-body empty-state">
<div class="container">
    <?= svg('empty-cuate') ?>
    <h3>No notes for while</h3>
    <p>You can add a note using the fields below.</p>
</div>
</div>
<?php endif; ?>

<div class="offcanvas-body add-note">
<form class="row" data-add-note-form data-order-id="<?= (int) $id ?>">
    <?= input('textarea', 'insert', [
        'size' => 'col-12',
        'name' => 'content',
        'style' => 'normal',
        'Placeholder' => 'Write a note',
    ]) .
    input('selection_type', 'insert', [
        'name' => 'visibility',
        'size' => 'col-8',
        'type' => 'radio',
        'variation' => 'balloons',
        'style' => 'normal',
        'Options' => [
            [
                'value' => 'private',
                'display' => 'Private',
            ],
            [
                'value' => 'customer',
                'display' => 'To customer',
                'disabled' => true
            ],
        ],
        'Value' => 'private',
        'Alert' => 'Soon you will be able to send messages to customer.',
        'Required' => true
    ]) .
    input('submit_button', 'insert', [
        'size' => 'col',
        'class' => 'btn btn-st',
        'div_class' => 'submit-btn',
        'block' => true,
        'Value' => icon('fas fa-arrow-right'),
        'disabled' => true,
    ])
    ?>
</form>
</div>

</section>

<!--
Headers & UTM offcanvas -- same pattern as #notes above (a button toggles
it open), but read-only: two tables, one for the request "headers" frozen
on tb_orders itself (ip_address/user_agent/device_type/origin/referrer),
one for whatever UTM tags were captured for this order (tb_order_utm_data,
see save_order_utm_data(), src/helpers.php).
-->
<section class="offcanvas offcanvas-end" tabindex="-1" id="order-headers-utm" aria-labelledby="orderHeadersUtmLabel">
<div class="offcanvas-header">
    <h5 class="offcanvas-title" id="orderHeadersUtmLabel">Headers &amp; UTM</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
</div>
<div class="offcanvas-body">

    <h6>Headers</h6>
    <?= render_order_headers_table_html($order) ?>

    <hr>

    <h6>UTM</h6>
    <?= render_order_utm_table_html($utm_data) ?>

</div>
</section>

<?php endif; ?>

<?php include_once AREAS_PATH .'/admin/include/script_libs.php'; ?>
