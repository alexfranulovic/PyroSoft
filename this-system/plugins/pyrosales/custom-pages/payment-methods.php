<?php
if (!isset($seg)) exit;

/**
 * "Meus cartões" -- customer-facing payment-methods CRUD page.
 *
 * Mirrors the front-end template flavor used by plan-checkout-template.php /
 * plan-receipt-template.php (include "include/*.php" resolved by the active
 * theme, $current_user scoped), NOT the admin AREAS_PATH flavor used by
 * order-manager.php -- this page belongs to the logged-in customer's own
 * account area.
 *
 * Cards are add-only here -- there is no "editar" action anymore. A saved
 * card's number/token can never be mutated on any real gateway, so the
 * offcanvas only ever creates a new row; ativar/desativar and marcar-como-
 * padrão (below) are the only mutations an existing card supports.
 */

// add_asset('head', "<link rel='stylesheet' href='". plugin_path('/pyrosales/assets/styles/checkout.css', 'url') ."'>");
add_asset('head', "<link rel='stylesheet' href='". plugin_path('/pyrosales/assets/styles/payment-methods.css', 'url') ."'>");
add_asset('footer', "<script src='". plugin_path('/pyrosales/assets/scripts/checkout.js', 'url') ."' defer></script>");
add_asset('footer', "<script src='". plugin_path('/pyrosales/assets/scripts/payment-methods.js', 'url') ."' defer></script>");
add_asset('footer', "<script src='". plugin_path('/pyrosales/assets/scripts/payment-methods-carousel.js', 'url') ."' defer></script>");
checkout_load_gateways_head();

include_once AREAS_PATH .'/app/include/head.php';
include_once AREAS_PATH .'/app/include/menu.php';


/**
 * checkout_load_gateways_head() calls each active gateway's own "_head()"
 * hook (e.g. pagbank_credit_card_head()), which is what puts PagBank's
 * public key <meta> tag on the page -- required by credit_card.js before
 * PagSeguro.encryptCard() can run.
 */

$cards               = list_user_saved_payment_methods();
$card_gateway_keys   = active_card_payment_gateway_keys();


// Individual mode's "Pedidos"/"Assinaturas" sections start already populated
// for whichever card renders first (index 0) -- avoids an empty-state flash
// on first paint; assets/scripts/payment-methods-carousel.js takes over from
// there on every slide (it is only ever active while Individual mode is
// visible, but stays enqueued regardless -- it no-ops harmlessly otherwise).
$first_card_id     = (int)($cards[0]['id'] ?? 0);
$first_transactions = $first_card_id > 0
    ? list_payment_method_transactions($first_card_id)
    : [];
$first_subscriptions = $first_card_id > 0
    ? list_payment_method_subscriptions($first_card_id)
    : [];
?>

<main class="container payment-methods-page" role="main">

<div class="d-flex justify-content-between align-items-center page-header">
    <h1>Meus cartões</h1>
    <button class="btn btn-st" type="button" data-add-payment-method data-bs-toggle="offcanvas" data-bs-target="#payment-method-form" aria-controls="payment-method-form">
        <?= icon('fas fa-plus') ?>
    </button>
</div>

<?php if (empty($cards)): ?>

<div class="card hero">
<div class="card-body">
    <div class="big-icon"><?= icon('fas fa-credit-card') ?></div>
    <h1>Você ainda não tem nenhum cartão salvo</h1>
    <p>Adicione um cartão para usar nas suas próximas compras e renovações.</p>
</div>
</div>

<?php else: ?>

<!-- Visualização -->
<div class="payment-methods-view-toggle-wrap">
<p>Visualização:</p>
<div class="btn-group payment-methods-view-toggle" role="group" aria-label="Modo de visualização" data-view-toggle>
    <button type="button" class="btn btn-sm btn-primary active" data-view-toggle-btn="individual" aria-current="page"><?= icon('fas fa-file-lines') ?> Individual</button>
    <button type="button" class="btn btn-sm btn-outline-primary" data-view-toggle-btn="table"><?= icon('fas fa-table') ?> Tabela</button>
</div>
</div>

<!-- Individual: carrossel em tela cheia (todos os breakpoints) + pedidos/
     assinaturas do cartão em foco, portado de payment-methods-carousel.php. -->
<div data-view-mode="individual">
<div class="payment-methods-carousel-full-wrap">
<div id="payment-methods-carousel" class="carousel slide payment-methods-carousel" data-bs-touch="true">

<?php if (count($cards) > 1): ?>
<div class="carousel-counter" data-carousel-counter>
    <span data-carousel-counter-current>1</span>/<span data-carousel-counter-total><?= count($cards) ?></span>
</div>
<?php endif; ?>

<div class="carousel-inner">
<?php foreach ($cards as $i => $card): ?>
<div class="carousel-item <?= $i === 0 ? 'active' : '' ?>" data-payment-method-row="<?= (int)$card['id'] ?>">

    <?= render_credit_card_visual_html($card, $i) ?>

    <div class="payment-method-mobile-actions">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch"
                data-toggle-payment-method="<?= (int)$card['id'] ?>"
                <?= $card['is_active'] ? 'checked' : '' ?>>
            <label class="form-check-label">Cartão ativo</label>
        </div>

        <div data-payment-method-default-cell>
            <span class="badge text-bg-primary" data-default-badge="<?= (int)$card['id'] ?>" <?= empty($card['is_default']) ? 'style="display:none"' : '' ?>>Padrão</span>
            <button type="button" class="btn btn-link btn-sm p-0" data-set-default-payment-method="<?= (int)$card['id'] ?>"
                <?= !empty($card['is_default']) ? 'style="display:none"' : '' ?>
            <?= !$card['is_active'] ? 'disabled' : '' ?>>Marcar como padrão</button>
        </div>
    </div>

</div>
<?php endforeach; ?>
</div>

<?php if (count($cards) > 1): ?>
<button class="carousel-control-prev" type="button" data-bs-target="#payment-methods-carousel" data-bs-slide="prev">
    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
    <span class="visually-hidden">Anterior</span>
</button>
<button class="carousel-control-next" type="button" data-bs-target="#payment-methods-carousel" data-bs-slide="next">
    <span class="carousel-control-next-icon" aria-hidden="true"></span>
    <span class="visually-hidden">Próximo</span>
</button>
<?php endif; ?>

</div>
</div>

<?php
ob_start(); ?>
    <div class="payment-method-section" data-payment-method-section="transactions">
    <div class="payment-method-transactions-list" data-payment-method-section-body>
        <?= render_payment_method_transactions_html($first_transactions) ?>
    </div>
    <div class='payment-method-section-footer'>
        <a href='<?= site_url('/historico-pagamentos/') ?>' class='btn btn-link btn-sm p-0'>Ver todos pedidos <?= icon('fas fa-arrow-right') ?></a>
    </div>
    </div>
<?php $transactions_tab_body = ob_get_clean();

ob_start(); ?>
    <div class="payment-method-section" data-payment-method-section="subscriptions">
    <div class="payment-method-transactions-list" data-payment-method-section-body>
        <?= render_payment_method_subscriptions_html($first_subscriptions) ?>
    </div>
    <div class='payment-method-section-footer'>
        <a href='<?= site_url('/minhas-assinaturas') ?>' class='btn btn-link btn-sm p-0'>Gerenciar assinaturas <?= icon('fas fa-arrow-right') ?></a>
    </div>
    </div>
<?php $subscriptions_tab_body = ob_get_clean();
?>

<div class="payment-method-detail-sections" data-payment-method-current-id="<?= $first_card_id ?>">
<?= block('navtabs', [
    'id'         => 'payment-method-details',
    'variation'  => 'navtabs_underline',
    'contents'   => [
        [
            'id'     => 'transactions',
            'title'  => 'Pedidos',
            'body'   => $transactions_tab_body,
            'active' => true,
        ],
        [
            'id'    => 'subscriptions',
            'title' => 'Assinaturas',
            'body'  => $subscriptions_tab_body,
        ],
    ],
]) ?>
</div>
</div>



<div data-view-mode="table" class="d-none">
<?= table([
    // 'div_attributes'   => "class='crud crud-table payment-methods-table-card'",
    // 'table_attributes' => "class='table payment-methods-table mb-0'",
    'data_table'       => true,
    'settings'         => [],
    'head'             => ['Status', 'Cartão', 'Tipo', 'Titular', 'Padrão'],
    'body'             => array_map(function ($card) {
        return [
            input('selection_type', 'update', [
                'type' => 'switch',
                'name' => 'auto_renew',
                'Options' => [
                    [
                        'value' => '1',
                        'display' => '',
                        'attributes' => "data-toggle-payment-method:({$card['id']});",
                        'checked' => $card['is_active'],
                    ]
                ],
            ]),
            render_card_brand_html($card),
            e($card['type_label']),
            e($card['holder_name'] ?? ''),
            "<span class='badge rounded-pill text-bg-primary' data-default-badge='". (int)$card['id'] ."' ". (empty($card['is_default']) ? "style='display:none'" : '') .">Padrão</span> "
            ."<button type='button' class='btn btn-outline-st btn-sm' data-set-default-payment-method='". (int)$card['id'] ."' "
            . (!empty($card['is_default']) ? "style='display:none' " : '')
            . (!$card['is_active'] ? 'disabled' : '')
            .">Marcar como padrão</button>",
        ];
    }, $cards),
]) ?>
</div>
<?php endif; ?>

</main>

<section class="offcanvas offcanvas-end" tabindex="-1" id="payment-method-form" aria-labelledby="paymentMethodFormLabel">
<div class="offcanvas-header">
    <h5 class="offcanvas-title" id="paymentMethodFormLabel">Adicionar cartão</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
</div>
<div class="offcanvas-body">

<?php if (empty($card_gateway_keys)): ?>
<p class="text-muted">Nenhum meio de pagamento por cartão está disponível no momento.</p>
<?php else: ?>

<form data-send-without-reload class='form-row' data-payment-method-form method="POST" action="<?= rest_api_route_url('save-payment-method') ?>">
<?= input('payment_methods', 'insert', [
    'only_these' => $card_gateway_keys,
    'label' => 'Tipo de cartão',
    'bypass_saved_methods' => true,
    'Required' => true,
]) ?>

<?= input('submit_button', 'insert', [
    'size' => 'col-12',
    'class' => 'btn btn-st',
    'block' => true,
    'Value' => 'Salvar cartão',
]) ?>
</form>

<?php endif; ?>

</div>
</section>

<?php include_once AREAS_PATH .'/app/include/footer.php'; ?>
