<?php
if (!isset($seg)) exit;

require __DIR__ .'/customer.php';
require __BASE_DIR__ . '/vendor/autoload.php';

function mercadopago_credit_card_process_payment(array $data, bool $debug = true): array
{
    global $info, $seg;

    $order       = (array)($data['order'] ?? []);
    // print_r($data);

    if ($order['order_type'] == 'plan') {
        require __DIR__ .'/subscriptions.php';
        return mercadopago_preapproval_create($data, $debug);
    }

    else {
        require __DIR__ .'/payment.php';
        return mercadopago_credit_card_payment($data, $debug);
    }
}
