<?php
if (!isset($seg)) exit;

global $payment_gateways;
$payment_gateways = $payment_gateways ?? [];

/**
 * Register Mercado Pago gateway
 */
$payment_gateways['pagbank.credit_card'] = [
    'label' => 'Cartão de crédito',
    'icon' => 'fas fa-credit-card',
    'method' => 'credit_card',
];

$payment_gateways['pagbank.boleto'] = [
    'label' => 'Boleto',
    'icon' => 'fas fa-barcode',
    'method' => 'boleto',
];

$payment_gateways['pagbank.pix'] = [
    'label' => 'PIX',
    'icon' => 'fab fa-pix',
    'method' => 'pix',
    'description' => 'Aprovação imediata'
];


$sandbox = get_system_info('pyrosales_is_sandbox')
    ? '_SB'
    : '';

$pagbank_endpoint = get_system_info('pyrosales_is_sandbox')
    ? 'https://sandbox.api.pagseguro.com'
    : 'https://api.pagseguro.com';

define("PAGBANK_TOKEN",      env("PAGBANK{$sandbox}_TOKEN"));
define("PAGBANK_CRYPTO",     env("PAGBANK{$sandbox}_CRYPTO"));
define("PAGBANK_ENDPOINT",     $pagbank_endpoint);



function pagbank_credit_card_head()
{
    $res = pagbank_request('/public-keys', 'POST', [
        'type' => 'card'
    ]);

    if ($res['code'] == 'success') {
        $pb_key = $res['response']['public_key'] ?? '';
        add_asset('head', "<meta name='pagbank-public-key' content='{$pb_key}'>");
    }
}

/**
 * Normalize PagBank transactional status to local status id.
 *
 * Adjust ids to match your database.
 *
 * Suggested mapping:
 * 1 = pending
 * 2 = approved
 * 3 = refused
 * 4 = canceled
 * 5 = authorized
 *
 * @param string $status PagBank charge status.
 *
 * @return int|null
 */
function pagbank_map_status_id(string $status): ?int
{
    $status = strtoupper(trim($status));

    $map = [
        'WAITING'     => 1,
        'IN_ANALYSIS' => 1,
        'PAID'        => 2,
        'DECLINED'    => 3,
        'CANCELED'    => 4,
        'AUTHORIZED'  => 5,
    ];

    return $map[$status] ?? null;
}


/**
 * Normalize PagBank transactional status to generic gateway result.
 *
 * @param string $status PagBank charge status.
 *
 * @return string success|processing|error
 */
function pagbank_normalize_return_code(string $status): string
{
    $status = strtoupper(trim($status));

    if (in_array($status, ['PAID', 'AUTHORIZED'], true)) {
        return 'success';
    }

    if (in_array($status, ['WAITING', 'IN_ANALYSIS'], true)) {
        return 'processing';
    }

    return 'error';
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
 * @param string $endpoint API endpoint path (ex: /public-keys)
 * @param string $method HTTP method (GET|POST|PUT|DELETE)
 * @param array $params Request body or query params
 * @return array
 */
function pagbank_request(string $endpoint, string $method = 'GET', array $params = [], bool $debug = false)
{
    $url = rtrim(PAGBANK_ENDPOINT, '/') . '/' . ltrim($endpoint, '/');

    $headers = [
        'Authorization: Bearer ' . PAGBANK_TOKEN,
        'Accept: application/json',
        'Content-Type: application/json'
    ];

    if ($method === 'GET' && !empty($params)) {
        $url .= '?' . http_build_query($params);
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
        dump($response);
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
        'http_code' => $httpCode,
        'response' => $json ?? $response
    ];
}
