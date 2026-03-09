<?php
if (!isset($seg)) exit;

global $payment_gateways;
$payment_gateways = $payment_gateways ?? [];

/**
 * Register Mercado Pago gateway
 */
$payment_gateways['mercadopago.credit_card'] = [
    'label' => 'Cartão de crédito',
    'icon' => 'fas fa-credit-card',
    'method' => 'credit_card',
];

$payment_gateways['pagbank.boleto'] = [
    'label' => 'Boleto',
    'icon' => 'fas fa-barcode',
    'method' => 'boleto',
];

$payment_gateways['mercadopago.pix'] = [
    'label' => 'PIX',
    'icon' => 'fab fa-pix',
    'method' => 'pix',
    'description' => 'Aprovação imediata'
];


$sandbox = get_system_info('pyrosales_is_sandbox')
    ? '.sb'
    : '';

define("MERCADOPAGO_ACCESS_TOKEN",      env("mercadopago{$sandbox}.access.token"));
define("MERCADOPAGO_PUBLIC_KEY",        env("mercadopago{$sandbox}.public.key"));
define("MERCADOPAGO_NOTIFICATION_URL",  env("mercadopago{$sandbox}.notification.url"));

function mercadopago_credit_card_head() {
    $pb_key = htmlspecialchars(MERCADOPAGO_PUBLIC_KEY, ENT_QUOTES);
    add_asset('head', "<meta name='mp-public-key' content='{$pb_key}'>");
}

/**
 * Map Mercado Pago status/status_detail to your internal status_id.
 * IMPORTANT: adjust numeric IDs to your tb_status table.
 */
function mercadopago_map_status_id(?string $status, ?string $statusDetail): int
{
    $s  = strtolower((string)$status);
    $sd = strtolower((string)$statusDetail);

    // APPROVED
    if ($s === 'approved') {
        return 2; // paid
    }

    // REFUNDED
    if ($s === 'refunded') {
        return 4; // refunded
    }

    // PARTIAL REFUND
    if ($s === 'partially_refunded') {
        return 5; // partial_refund
    }

    // FAILED / REJECTED / CANCELLED
    if (in_array($s, ['rejected', 'cancelled'], true)) {
        return 3; // failed
    }

    // MANUAL REVIEW → treat as pending
    if ($sd === 'pending_review_manual') {
        return 1; // pending
    }

    // PENDING / IN_PROCESS
    if (in_array($s, ['pending', 'in_process'], true)) {
        return 1; // pending
    }

    // Default safe fallback
    return 1; // pending
}
