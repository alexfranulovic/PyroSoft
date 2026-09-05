<?php
if (!isset($seg)) exit;

global $payment_gateways;
$payment_gateways = $payment_gateways ?? [];

require_once __DIR__ .'/src/cancel-refund.php';
require_once __DIR__ .'/src/payment-methods.php';


/**
 * Register Pagbank gateway
 *
 * `editable_payment_method`: whether pagbank_save_payment_method() may be
 * called a second time to REPLACE an already-saved card (see
 * src/payment-methods.php's docblock for why "editing" a saved card always
 * means tokenizing a brand new one -- the PAN/token itself can never be
 * mutated in place, on PagBank or on any other card gateway). A future
 * gateway plugin that can't re-verify a card without a full, non-refundable
 * charge should set this to false so PyroSales' payment-methods page hides
 * the "editar" action for it and only exposes activate/deactivate/default.
 */
$payment_gateways['pagbank.credit_card'] = [
    'label' => 'Cartão de crédito',
    'icon' => 'fas fa-credit-card',
    'method' => 'credit_card',
    'refundable_api' => true,
    'editable_payment_method' => true,
    'accepted_brands' => [
        'visa',
        'mastercard',
        'elo',
        'amex',
        'diners',
        'discover',
        'hipercard',
        // 'jcb',
        // 'aura',
        // 'cabal',
    ]
];

$payment_gateways['pagbank.debit_card'] = [
    'label' => 'Cartão de débito',
    'icon' => 'fas fa-credit-card',
    'method' => 'debit_card',
    'refundable_api' => true,
    'editable_payment_method' => true,
    'accepted_brands' => [
        'visa',
        'mastercard',
        'elo',
        'amex',
        'diners',
        'discover',
        'hipercard',
        // 'jcb',
        // 'aura',
        // 'cabal',
    ]
];

$payment_gateways['pagbank.boleto'] = [
    'label' => 'Boleto',
    'icon' => 'fas fa-barcode',
    'method' => 'boleto',
    'refundable_api' => true,
];

$payment_gateways['pagbank.pix'] = [
    'label' => 'PIX',
    'icon' => 'fab fa-pix',
    'method' => 'pix',
    'description' => 'Aprovação imediata',
    'refundable_api' => true,
];


$sandbox = get_system_info('pyrosales_is_sandbox')
    ? '_SB'
    : '';

$pagbank_endpoint = get_system_info('pyrosales_is_sandbox')
    ? 'https://sandbox.api.pagseguro.com'
    : 'https://api.pagseguro.com';

// Separate host used only for the 3DS SDK session endpoint -- NOT the
// same host as PAGBANK_ENDPOINT (checkout-sdk vs. the regular Orders API).
$pagbank_sdk_endpoint = get_system_info('pyrosales_is_sandbox')
    ? 'https://sandbox.sdk.pagseguro.com'
    : 'https://sdk.pagseguro.com';

// Card-verification amount (BRL) used by pagbank_save_payment_method() to
// tokenize+authorize a card with NO real charge to the customer -- see
// src/payment-methods.php. Kept above zero because PagBank's /orders API
// rejects amount.value <= 0 outright; the authorization is voided right
// after (capture: false + immediate pagbank_cancel_refund()), the exact
// same authorize-then-reverse mechanic this plugin already uses for plan
// trial validations (see pagbank_credit_card_process_payment()'s "Post not
// capture the payment" block and payment_to_order_status()'s
// 'trial_validation' map, where a reversed/canceled authorization is
// treated as a successful outcome, not a failed one).
define("PAGBANK_CARD_VERIFICATION_AMOUNT", 1.00);

define("PAGBANK_TOKEN",                 env("PAGBANK{$sandbox}_TOKEN"));
define("PAGBANK_CRYPTO",                env("PAGBANK{$sandbox}_CRYPTO"));
define("PAGBANK_EMAIL",                 env("PAGBANK{$sandbox}_EMAIL"));
define("PAGBANK_NOTIFICATION_TOKEN",    env("PAGBANK{$sandbox}_NOTIFICATION_TOKEN"));
define("PAGBANK_ENDPOINT",              $pagbank_endpoint);
define("PAGBANK_SDK_ENDPOINT",          $pagbank_sdk_endpoint);
define("PAGBANK_CANCEL_MAX_ATTEMPTS",   2);



/**
 * These used to call PagBank's own /public-keys endpoint synchronously,
 * right here in <head>, and print the result into a
 * <meta name="pagbank-public-key"> tag -- which meant every page load
 * waited on an extra external HTTP round trip (PHP -> PagBank) just to
 * draw the checkout form, before the browser could do anything else.
 *
 * The public key is now fetched from the BROWSER instead, on demand, via
 * the pagbank-public-key REST route (api.php) -- which wraps the exact
 * same call through pagbank_get_public_key() below. These hooks are kept
 * as no-ops (rather than removed) only so checkout_load_gateways_head()'s
 * "{provider}_{method}_head" convention still finds a defined function for
 * both card methods.
 */
function pagbank_credit_card_head()
{
}

function pagbank_debit_card_head()
{
}

/**
 * Fetches PagBank's public key, used by the browser SDK
 * (PagSeguro.encryptCard()) to tokenize card data client-side without it
 * ever touching our own server. Called by the pagbank-public-key REST
 * route (api.php); kept as its own function, rather than inlined in the
 * route, since it mirrors pagbank_create_3ds_session() below and may be
 * useful to future server-side code needing a fresh key.
 *
 * @return string Public key, or '' on failure.
 */
function pagbank_get_public_key(): string
{
    $res = pagbank_request('/public-keys', 'POST', [
        'type' => 'card',
    ]);

    if (($res['code'] ?? '') !== 'success') {
        return '';
    }

    return (string)($res['response']['public_key'] ?? '');
}


function pagbank_load_notifications() {
    global $seg;
    require_once __DIR__ .'/src/notifications.php';
}


/**
 * Normalize Brazilian phone into PagBank phone object.
 *
 * @param string $phone Raw phone.
 *
 * @return array
 */
function pagbank_normalize_phone(string $phone): array
{
    $digits = preg_replace('/\D+/', '', $phone);

    if ($digits === '') {
        return [];
    }

    if (strpos($digits, '55') === 0 && strlen($digits) >= 12) {
        $digits = substr($digits, 2);
    }

    if (strlen($digits) < 10) {
        return [];
    }

    $area   = substr($digits, 0, 2);
    $number = substr($digits, 2);

    return [
        'country' => '55',
        'area'    => $area,
        'number'  => $number,
        'type'    => 'MOBILE',
    ];
}

/**
 * Send request to PagBank API
 *
 * @param string $endpoint API endpoint path or absolute URL
 * @param string $method HTTP method (GET|POST|PUT|DELETE)
 * @param array $params Request body or query params
 * @param bool $debug Print debug information if true
 * @return array
 */
function pagbank_request(string $endpoint, string $method = 'GET', array $params = [], array|null $headers = [], bool $debug = false)
{
    $url = filter_var($endpoint, FILTER_VALIDATE_URL)
        ? $endpoint
        : rtrim(PAGBANK_ENDPOINT, '/') . '/' . ltrim($endpoint, '/');

    $headers = !empty($headers) ? $headers : [
        'Authorization: Bearer ' . PAGBANK_TOKEN,
        'Accept: application/json',
        'Content-Type: application/json'
    ];

    if ($method === 'GET' && !empty($params)) {
        $url .= (strpos($url, '?') !== false ? '&' : '?') . http_build_query($params);
    }

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
    ]);

    if ($method !== 'GET' && !empty($params)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);

    curl_close($ch);

    if ($debug) {
        dump([
            'url' => $url,
            'headers' => $headers,
            'request' => $params,
            'response' => $response,
            'http_code' => $httpCode,
            'error' => $error
        ]);
    }

    if ($error) {
        return [
            'code' => 'error',
            'detail' => $error
        ];
    }

    $json = json_decode($response, true);

    return [
        'code' => 'success',
        'url' => $url,
        'headers' => $headers,
        'request' => $params,
        'response' => $response,
        'http_code' => $httpCode,
        'response' => $json ?? $response,
    ];
}

/**
 * Create a PagBank 3DS SDK session -- required before the browser can call
 * PagSeguro.setUp()/authenticate3DS(). Valid for 30 minutes.
 *
 * https://developer.pagbank.com.br/reference/criar-sessao-autenticacao-3ds
 *
 * @param bool $debug Print debug information when true.
 * @return array
 */
function pagbank_create_3ds_session(bool $debug = false): array
{
    return pagbank_request(PAGBANK_SDK_ENDPOINT . '/checkout-sdk/sessions', 'POST', [], [
        'Authorization: Bearer ' . PAGBANK_TOKEN,
        'Accept: application/json',
        'Content-Type: application/json',
    ], $debug);
}
