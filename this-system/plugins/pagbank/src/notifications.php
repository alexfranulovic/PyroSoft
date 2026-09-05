<?php
if(!isset($seg)) exit;

require_once __DIR__ .'/status.php';

/**
 * Router: decide se a notificação recebida é do modelo legado (notificationCode)
 * ou do modelo novo Order/Charge (JSON cru).
 *
 * @param array $params
 * @param bool  $debug
 * @return array
 */
function pagbank_handle_order_notification(array $params = [], bool $debug = false): array
{
  $notification_code = trim((string)($params['notificationCode'] ?? ''));

  if (!empty($notification_code)) {
    return pagbank_handle_legacy_order_notification($params, $debug);
  }

  return pagbank_handle_new_order_notification($params, $debug);
}

/**
 * Handle PagBank legacy order notification: fetches the transaction data
 * from PagBank using notificationCode and translates it to the CMS.
 *
 * @param array $params
 * @param bool  $debug
 * @return array
 */
function pagbank_handle_legacy_order_notification(array $params = [], bool $debug = false): array
{
  $notification_code = trim((string)($params['notificationCode'] ?? ''));

  if (empty($notification_code))
  {
    return [
      'code' => 'error',
      'msg'  => [
        'reason'  => 'missing_notification_code',
        'message' => 'Notification code was not provided.'
      ]
    ];
  }

  $notification_code = preg_replace("/-/", "", $notification_code);

  /**
   * Fetch the transaction data from PagBank.
   */
  $email = PAGBANK_EMAIL;
  $token = PAGBANK_NOTIFICATION_TOKEN;
  if (!$email || !$token) {
    return [
      'code' => 'error',
      'msg'  => ['reason' => 'missing_pagbank_credentials']
    ];
  }

  $endpoint = get_system_info('pyrosales_is_sandbox')
    ? 'https://ws.sandbox.pagseguro.uol.com.br'
    : 'https://ws.pagseguro.uol.com.br';
  $endpoint.= "/v3/transactions/notifications/{$notification_code}";

  $res = pagbank_request($endpoint, 'GET', [
    'email' => $email,
    'token' => $token,
  ], [
    'Content-Type: application/xml',
  ], $debug);

  if (($res['code'] ?? '') !== 'success' || empty($res['response']))
  {
    return [
      'code' => 'error',
      'msg'  => ['reason' => 'pagbank_request_failed'],
      'data' => $res
    ];
  }

  $xml = @simplexml_load_string((string)$res['response'], 'SimpleXMLElement', LIBXML_NOCDATA);

  if ($xml === false)
  {
    return [
      'code' => 'error',
      'msg'  => ['reason' => 'invalid_xml']
    ];
  }

  $notification_data = json_decode(json_encode($xml), true);

  /**
   * Translate to CMS structure.
   */
  $translator_data               = (array)$notification_data;
  $translator_data['order_data'] = $params['order_data'];

  $translated = pagbank_translate_notification_to_cms($translator_data);
  $reference  = (string)($translated['reference'] ?? '');

  if ($debug) {
    var_dump($notification_data);
  }

  if (empty($reference))
  {
    return [
      'code' => 'error',
      'msg'  => [
        'reason'  => 'missing_reference',
        'message' => 'PagBank notification does not contain an internal reference.',
      ],
    ];
  }

  return [
    'code' => 'success',
    'data' => $translated
  ];
}

/**
 * Handle PagBank new-model Order notification (raw JSON payload,
 * charges[].status já vem pronto — sem necessidade de GET adicional).
 *
 * @param array $params
 * @param bool  $debug
 * @return array
 */
function pagbank_handle_new_order_notification(array $params = [], bool $debug = false): array
{
  $raw_body = (string)($params['raw_body'] ?? '');

  if ($raw_body === '') {
    return [
      'code' => 'error',
      'msg'  => ['reason' => 'empty_body', 'message' => 'No JSON payload received.'],
    ];
  }

  // Assinatura vem do header genérico repassado pelo endpoint
  $headers              = (array)($params['headers'] ?? []);
  $received_signature   = (string)($headers['HTTP_X_AUTHENTICITY_TOKEN'] ?? '');
  $account_token        = defined('PAGBANK_TOKEN') ? PAGBANK_TOKEN : '';

  if ($account_token !== '' && $received_signature !== '') {
    $expected_signature = hash('sha256', $account_token . '-' . $raw_body);
    if (!hash_equals($expected_signature, $received_signature)) {
      return [
        'code' => 'error',
        'msg'  => ['reason' => 'invalid_signature', 'message' => 'Authenticity token mismatch.'],
      ];
    }
  }

  $payload = json_decode($raw_body, true);
  if (json_last_error() !== JSON_ERROR_NONE || empty($payload)) {
    return [
      'code' => 'error',
      'msg'  => ['reason' => 'invalid_json', 'message' => 'Could not decode notification payload.'],
    ];
  }

  $charge = (array)($payload['charges'][0] ?? []);
  if (empty($charge)) {
    return [
      'code' => 'error',
      'msg'  => ['reason' => 'missing_charge', 'message' => 'No charge found in notification payload.'],
    ];
  }

  $translated = pagbank_translate_notification_to_cms($payload, $charge);

  if (empty($translated['reference'])) {
    return [
      'code' => 'error',
      'msg'  => ['reason' => 'missing_reference', 'message' => 'Notification has no reference_id.'],
    ];
  }

  return [
    'code' => 'success',
    'data' => $translated,
  ];
}

/**
 * Translate a PagBank notification payload to CMS-friendly structure.
 *
 * Handles both formats:
 * - Legado: passe apenas $data (array decodificado do XML de notificationCode,
 *   com order_data embutido). $charge fica null.
 * - Novo (Order): passe $data como o payload completo do Order e $charge
 *   como o array de charges[0]. Nesse caso $data['reference_id'] vira a
 *   referência interna, e o valor vem em centavos.
 *
 * NOTA: o payload do webhook Order NÃO traz taxa/valor líquido (diferente do
 * legado, que já chega calculado via `creditorFees`/`netAmount`). Pro modelo
 * novo, `gateway_fee`, `installment_fee_amount` e `net_amount` ficam null;
 * se precisar deles, é necessário um GET separado em /orders/{id}/fees
 * (Consultar taxas de uma transação) ou aguardar o evento pós-transacional
 * de disponibilização de saldo.
 *
 * @param array      $data   Payload legado completo, OU payload do Order (modelo novo).
 * @param array|null $charge Charge do modelo novo ($data['charges'][0]). Null = legado.
 * @return array
 */
function pagbank_translate_notification_to_cms(array $data = [], ?array $charge = null): array
{
  $is_legacy = ($charge === null);

  if ($is_legacy)
  {
    $status_code = (string)($data['status'] ?? '');
    $status_id = pagbank_payment_status_id([
      'status' => $status_code,
      'for_notification' => true,
      'legacy' => true,
      'order_data' => $data['order_data'],
    ]);

    return [
      'flow'                   => 'LEGACY',
      'provider'               => 'pagbank',
      'provider_reference'     => (string)($data['code'] ?? ''),
      'provider_status_code'   => $status_code,
      'status_id'              => $status_id,
      'reference'              => (string)($data['reference'] ?? ''),
      'amount'                 => (float)($data['grossAmount'] ?? '0'),
      'gateway_fee'            => (float)($data['creditorFees']['intermediationFeeAmount'] ?? ''),
      'installment_fee_amount' => (float)($data['creditorFees']['installmentFeeAmount'] ?? ''),
      'net_amount'             => (float)($data['netAmount'] ?? '0'),
      'installments'           => (int)($data['installmentCount'] ?? 1),
      'provider_method_code'   => (string)($data['paymentMethod']['type'] ?? ''),
      'raw_response_json'      => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ];
  }

  // Modelo novo (Order)
  $status_code = (string)($charge['status'] ?? '');
  if (!empty($charge['amount']['summary']['refunded']) && ($charge['amount']['summary']['refunded'] > 0))
  {
    $status_code = ($charge['amount']['summary']['refunded'] == $charge['amount']['summary']['total'])
      ? 'REFUNDED'
      : 'PARTIALLY_REFUNDED';
  }

  $status_id = pagbank_payment_status_id([
    'status' => $status_code,
    // 'for_notification' => true,
    'legacy' => false,
    'order_data' => $data['order_data'] ?? [],
  ]);

  $amount       = (array)($charge['amount'] ?? []);
  $amount_value = isset($amount['value']) ? ((float)$amount['value'] / 100) : 0.0;

  return [
    'flow'                   => 'NEW',
    'provider'               => 'pagbank',
    // 'provider_reference'     => (string)($data['id'] ?? ''),            // OR:xxx;AT:xxx
    // 'provider_order_id'      => (string)($data['id'] ?? ''),            // ORDE_xxx
    'provider_payment_id'    => $charge['id'] ?? null,                   // CHAR_xxx
    'provider_status_code'   => $status_code,
    'status_id'              => $status_id,
    'reference'              => (string)($data['reference_id'] ?? ''),   // seu "OR:{id};AT:{n}"
    'amount'                 => round($amount_value, 2),
    'gateway_fee'            => null,
    'installment_fee_amount' => null,
    'net_amount'             => null,
    'installments'           => (int)($charge['payment_method']['installments'] ?? 1),
    'provider_method_code'   => (string)($charge['payment_method']['type'] ?? ''),
    'raw_response_json'      => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
  ];
}
