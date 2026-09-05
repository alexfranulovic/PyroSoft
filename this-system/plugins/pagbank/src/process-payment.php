<?php
if (!isset($seg)) exit;

require_once __DIR__ .'/status.php';

/**
 * Create and pay an order with credit card using PagBank Order API.
 * Important:
 * - For first purchase, payment_data['token'] must contain the encrypted card generated in the browser
 *   using PagBank JS SDK.
 * - For saved card flow, payment_data['provider_card_id'] must contain the PagBank card token.
 *
 * @param array $data Payment payload.
 * @param bool $debug Print debug information when true.
 *
 * @return array Normalized payment response.
 */
function pagbank_credit_card_process_payment(array $data, bool $debug = false): array
{
    global $info, $seg;

    // print_r($data);
    // die;

    $order              = (array)($data['order'] ?? []);
    $paymentHash        = (string)($data['payment_template']['payment_hash'] ?? '');
    $orderId            = (string)($order['id'] ?? '');
    $userId             = (int)($order['user_id'] ?? 0);
    $amount             = (float)($data['payment_template']['amount'] ?? 0);
    $paymentData        = (array)($data['payment_data'] ?? []);
    $itemsLines         = (array)($data['items_lines'] ?? []);
    $itemsFull          = (array)($data['items_full'] ?? []);
    $order_purpose      = (string)($order['order_purpose'] ?? 'charge');

    $attempt            = (int)($order['attempt'] ?? 1);
    $paymentReference   = $orderId !== '' ? "OR:{$orderId};AT:{$attempt}" : uniqid('OR:', true);
    $email              = trim((string)($order['customer_email'] ?? ''));
    $token              = trim((string)($paymentData['token'] ?? '')); // Encrypted card from PagBank JS SDK
    $savedCardId        = trim((string)($paymentData['provider_card_id'] ?? ''));
    $cvv                = trim((string)($paymentData['cvv'] ?? ''));
    $installments       = max(1, (int)($paymentData['installments'] ?? 1));

    if ($amount <= 0) {
        return [
            'code' => 'error',
            'msg'  => [
                'method' => 'credit_card',
                'provider' => 'pagbank',
                'provider_type_code' => 'invalid_amount',
                'raw_response_json' => [
                    'message' => 'Amount must be greater than zero.',
                ],
            ],
        ];
    }

    if (empty($email)) {
        return [
            'code' => 'error',
            'msg'  => [
                'method' => 'credit_card',
                'provider' => 'pagbank',
                'provider_type_code' => 'invalid_customer_email',
                'raw_response_json' => [
                    'message' => 'Customer email is required.',
                ],
            ],
        ];
    }

    if ($savedCardId === '' && $token === '') {
        return [
            'code' => 'error',
            'msg'  => [
                'method' => 'credit_card',
                'provider' => 'pagbank',
                'provider_type_code' => 'missing_card_token',
                'raw_response_json' => [
                    'message' => 'Either encrypted card token or provider_card_id must be provided.',
                ],
            ],
        ];
    }

    /**
     *
     * Customer
     *
     */
    $customerName = trim((string)(
        $order['customer_name']
        ?? trim((string)($order['customer_first_name'] ?? '') . ' ' . (string)($order['customer_last_name'] ?? ''))
    ));

    $customer = [
        'name'  => $customerName,
        'email' => $email,
    ];

    $taxId = preg_replace('/\D+/', '', (string)($order['customer_document_number'] ?? ''));
    if ($taxId !== '') {
        $customer['tax_id'] = $taxId;
    }

    $phone = pagbank_normalize_phone((string)($order['customer_phone'] ?? ''));
    if (!empty($phone)) {
        $customer['phones'] = [$phone];
    }

    /**
     *
     * Shipping / billing address snapshot.
     *
     */
    if (!empty($order['address']) && is_array($order['address']))
    {
        $addr = $order['address'];
        $customer['address'] = [
            'street'      => (string)($addr['street_name'] ?? ''),
            'number'      => (string)($addr['street_number'] ?? ''),
            'complement'  => (string)($addr['complement'] ?? ''),
            'locality'    => (string)($addr['neighborhood'] ?? ''),
            'city'        => (string)($addr['city'] ?? ''),
            'region_code' => strtoupper((string)($addr['state'] ?? '')),
            'country'     => strtoupper((string)($addr['country'] ?? 'BRA')),
            'postal_code' => preg_replace('/\D+/', '', (string)($addr['zip_code'] ?? '')),
        ];
    }

    /**
     *
     * Items
     *
     */
    $pagbankItems = [];
    foreach ($itemsLines as $i => $line)
    {
        $qty       = max(1, (int)($line['quantity'] ?? 1));
        $unitPrice = (float)($line['unit_price'] ?? $line['price'] ?? 0);
        $valueCents = (int)round($unitPrice * 100);

        $pagbankItems[] = [
            'reference_id' => (string)($line['id'] ?? $line['item_id'] ?? $line['product_id'] ?? ($i + 1)),
            'name'         => (string)($line['title'] ?? $line['item_name'] ?? 'Item'),
            'quantity'     => $qty,
            'unit_amount'  => $valueCents,
        ];
    }
    if (empty($pagbankItems))
    {
        $pagbankItems[] = [
            'reference_id' => $orderId !== '' ? $orderId : '1',
            'name'         => (string)($itemsLines[0]['item_name'] ?? 'Order payment'),
            'quantity'     => 1,
            'unit_amount'  => (int)round($amount * 100),
        ];
    }


    /**
     *
     * Capture logic for plans.
     *
     */
    $capture = (bool)($data['capture'] ?? false);

    /**
     *
     * Charge.
     *
     */
    $charge = [
        'reference_id' => $paymentReference,
        'description'  => (string)($itemsLines[0]['title'] ?? $itemsLines[0]['item_name'] ?? 'Order payment'),
        'amount'       => [
            'value'    => (int)round($amount * 100),
            'currency' => 'BRL',
        ],
        'payment_method' => [
            'type'         => 'CREDIT_CARD',
            'installments' => $installments,
            'capture'      => $capture,
        ],
    ];

    /**
     * Statement descriptor (soft descriptor)
     */
    $softDescriptor = strtoupper((string)($paymentData['statement_descriptor'] ?? ($info['short_name'] ?? 'PAYMENT')));
    $softDescriptor = preg_replace('/[^A-Z0-9 ]/', '', $softDescriptor);
    $softDescriptor = substr(trim($softDescriptor), 0, 17);

    if ($softDescriptor !== '') {
        $charge['payment_method']['soft_descriptor'] = $softDescriptor;
    }

    /**
     * Card payload:
     * - First purchase: encrypted card
     * - Saved card: card.id + security_code
     */
    if ($savedCardId !== '')
    {
        $charge['payment_method']['card'] = [
            'id' => $savedCardId,
        ];

        if ($cvv !== '') {
            $charge['payment_method']['card']['security_code'] = $cvv;
        }

        $holderName = trim((string)($paymentData['name'] ?? $customerName));
        if ($holderName !== '' || $taxId !== '') {
            $charge['payment_method']['card']['holder'] = [];
            if ($holderName !== '') {
                $charge['payment_method']['card']['holder']['name'] = $holderName;
            }
            if ($taxId !== '') {
                $charge['payment_method']['card']['holder']['tax_id'] = $taxId;
            }
        }
    }

    else
    {
        $charge['payment_method']['card'] = [
            'encrypted' => $token,
            'store'     => ($userId > 0),
        ];

        $holderName = trim((string)($paymentData['name'] ?? $customerName));
        if ($holderName !== '' || $taxId !== '') {
            $charge['payment_method']['holder'] = [];
            if ($holderName !== '') {
                $charge['payment_method']['holder']['name'] = $holderName;
            }
            if ($taxId !== '') {
                $charge['payment_method']['holder']['tax_id'] = $taxId;
            }
        }
    }


    /**
     * Optional 3DS flow
     */
    $threeDsId = trim((string)($paymentData['threeds_id'] ?? ''));
    if ($threeDsId !== '') {
        // Nested inside payment_method, NOT a sibling of it -- confirmed
        // both by PagBank's own sample payloads and by the parameter_name
        // ("charges[0].payment_method.authentication_method") on error
        // 40002 when it's misplaced.
        $charge['payment_method']['authentication_method'] = [
            'type' => 'THREEDS',
            'id'   => $threeDsId,
        ];
    }


    /**
     * Order payload
     */
    $payload = [
        'reference_id' => $paymentReference,
        'customer'     => $customer,
        'items'        => $pagbankItems,
        'charges'      => [$charge],
        'notification_urls' => [
            rest_api_route_url('order-notification?gateway=pagbank&hash='. urlencode($paymentHash)),
        ],
    ];

    if ($debug) {
        echo '<pre>';
        dump($payload);
        echo '</pre>';
    }

    /**
     * Send request
     */
    try {
        $request = pagbank_request('/orders', 'POST', $payload, [], $debug);
        $raw_response_json[] = $request['response'];

        $response = $request['response'];
    }

    catch (\Throwable $e)
    {
        if ($debug) {
            print_r($e->getMessage());
        }

        return [
            'code' => 'error',
            'msg'  => [
                'method' => 'credit_card',
                'provider' => 'pagbank',
                'provider_type_code' => 'request_exception',
                'raw_response_json' => [
                    'error_message' => $e->getMessage(),
                ],
            ],
        ];
    }

    if ($debug) {
        print_r($raw_response_json);
    }

    /**
     * Extract first charge
     */
    $chargeResponse = (array)($response['charges'][0] ?? []);
    $status         = strtoupper((string)($chargeResponse['status'] ?? ''));
    $statusId = pagbank_payment_status_id([
        'status' => $status,
    ]);

    $paymentMethod       = (array)($chargeResponse['payment_method'] ?? []);
    $paymentResp         = (array)($chargeResponse['payment_response'] ?? []);
    $amountResp          = (array)($chargeResponse['amount'] ?? []);
    $providerPaymentId   = (string)($chargeResponse['id'] ?? '');

    $errorMessages       = (array)($response['error_messages'] ?? []);
    $providerOrderId     = (string)($response['id'] ?? '');
    $providerTypeCode    = (string)($paymentResp['code'] ?? $status);
    $providerMessage     = (string)($paymentResp['message'] ?? '');
    $currency            = (string)($amountResp['currency'] ?? 'BRL');
    $amountPaid          = isset($amountResp['value']) ? ((float)$amountResp['value'] / 100) : round($amount, 2);
    $installmentAmount   = $installments > 0 ? round($amountPaid / $installments, 2) : null;
    $gatewayFee          = null;
    $netAmount           = null;

    /**
     * Save card token locally if PagBank returned it.
     */
    try
    {
        $returnedCard = (array)($paymentMethod['card'] ?? []);
        $returnedCardId = trim((string)($returnedCard['id'] ?? ''));

        if ($userId > 0 && $returnedCardId !== '' && ($status == 'PAID' || $status == 'AUTHORIZED'))
        {
            $pm = [
              'provider'             => 'pagbank',
              'method'               => 'credit_card',
              'provider_customer_id' => null,
              'provider_card_id'     => $response['charges'][0]['payment_method']['card']['id'] ?? null,
              'brand'                => null,
              'brand_name'           => $response['charges'][0]['payment_method']['card']['brand'] ?? null,
              'issuer_name'          => $response['charges'][0]['payment_method']['card']['issuer']['name'] ?? null,
              'first6'               => $response['charges'][0]['payment_method']['card']['first_digits'] ?? null,
              'last4'                => $response['charges'][0]['payment_method']['card']['last_digits'] ?? null,
              'exp_month'            => $response['charges'][0]['payment_method']['card']['exp_month'] ?? null,
              'exp_year'             => $response['charges'][0]['payment_method']['card']['exp_year'] ?? null,
              'holder_name'          => $response['charges'][0]['payment_method']['card']['holder']['name'] ?? null,
            ];

            $user_payment_method_id = save_user_payment_method($userId, $pm, true, $debug);
        }
    } catch (\Throwable $e) {
        if ($debug) {
            echo "\nCard save skipped/failed: " . $e->getMessage() . "\n";
        }
    }


    /**
     *
     * Post not capture the payment.
     *
     */
    if (!$capture)
    {
        $reverse = [
            'provider_payment_id' => $providerPaymentId,
            'status_id' => $status,
            'to_refund_amount' => $amountPaid,
            'total_amount' => $amountPaid,
        ];
        $reverse = pagbank_cancel_refund($reverse);

        // $order_purpose    = 'trial_validation';
        if (!empty($reverse['data']))
        {
            $reverse = $reverse['data'];

            // Payment status
            $statusId = pagbank_payment_status_id([
                'status' => $reverse['new_status'],
            ]);

            $status           = "BYPASS";
            $providerTypeCode = (string)($status ?: 'WAITING');
            if ($reverse['raw_response_json']) {
                $raw_response_json[] = $reverse['raw_response_json'];
            }
        }
    }

    $response = [
        'status_id'           => $statusId,
        'method'              => (string)($data['payment_template']['method'] ?? 'credit_card'),
        'provider'            => 'pagbank',
        'currency'            => $currency ?: null,
        'amount'              => round($amountPaid, 2),
        'gateway_fee'         => $gatewayFee,
        'net_amount'          => $netAmount,
        'installments'        => $installments,
        'installment_amount'  => $installmentAmount,
        'provider_payment_id' => $providerPaymentId,
        'provider_order_id'   => $providerOrderId,
        'provider_reference'  => $paymentReference,
        'provider_type_code'  => $providerTypeCode,
        'raw_response_json'   => $raw_response_json,
    ];

    $res = [
        'code' => pagbank_response_code($status),
        'msg'  => $response,
    ];

    // Order purpose
    // if (!empty($order_purpose)) {
    //     $res['order_purpose'] = $order_purpose;
    // }

    // Return user payment method id
    if (!empty($user_payment_method_id)) {
        $res['user_payment_method_id'] = $user_payment_method_id;
    }

    // Error messages
    if (!empty($errorMessages)) {
        $res['error_messages'][] = pagbank_resolve_error_message($errorMessages, $status);
    }

    // print_r($res);

    return $res ?? [];
}


/**
 * Create and pay an order with debit card using PagBank Order API.
 *
 * Differences from pagbank_credit_card_process_payment():
 * - Always captured immediately -- debit has no authorize-now /
 *   capture-later concept, so there's no "not $capture" reversal branch
 *   like the credit_card trial-validation flow uses.
 * - No installments (always a single payment).
 *
 * Like credit_card, the card can be stored for reuse (save_user_payment_method())
 * and later charged automatically (e.g. subscription renewal) via a saved
 * user_payment_method_id, with no CVV re-entry. Be aware debit rails in
 * Brazil often require cardholder presence/authentication per transaction,
 * so an unattended renewal charge on a saved debit card can be declined by
 * the issuer more often than the same flow on a saved credit card --
 * that's a card-network/issuer behavior, not something this integration
 * controls.
 *
 * Important:
 * - For first purchase, payment_data['token'] must contain the encrypted card generated in the browser
 *   using PagBank JS SDK.
 * - For saved card flow, payment_data['provider_card_id'] must contain the PagBank card token.
 *
 * @param array $data Payment payload.
 * @param bool $debug Print debug information when true.
 *
 * @return array Normalized payment response.
 */
function pagbank_debit_card_process_payment(array $data, bool $debug = false): array
{
    global $info, $seg;

    $order              = (array)($data['order'] ?? []);
    $paymentHash        = (string)($data['payment_template']['payment_hash'] ?? '');
    $orderId            = (string)($order['id'] ?? '');
    $userId             = (int)($order['user_id'] ?? 0);
    $amount             = (float)($data['payment_template']['amount'] ?? 0);
    $paymentData        = (array)($data['payment_data'] ?? []);
    $itemsLines         = (array)($data['items_lines'] ?? []);
    $itemsFull          = (array)($data['items_full'] ?? []);
    $order_purpose      = (string)($order['order_purpose'] ?? 'charge');

    $attempt            = (int)($order['attempt'] ?? 1);
    $paymentReference   = $orderId !== '' ? "OR:{$orderId};AT:{$attempt}" : uniqid('OR:', true);
    $email              = trim((string)($order['customer_email'] ?? ''));
    // Owned exclusively by pagbank_debit_card_fields()'s new-card block --
    // no generic fallback here, so a stale/leftover credit_card token can
    // never be picked up by mistake.
    $token              = trim((string)($paymentData['debit_token'] ?? '')); // Encrypted card from PagBank JS SDK
    $savedCardId        = trim((string)($paymentData['provider_card_id'] ?? ''));
    // CVV re-entry for a saved card is rendered by a shared component
    // (outside pagbank_debit_card_fields()) under the generic key --
    // fall back to it when the debit-specific new-card field is empty.
    $cvv                = trim((string)($paymentData['debit_cvv'] ?? $paymentData['cvv'] ?? ''));

    if ($amount <= 0) {
        return [
            'code' => 'error',
            'msg'  => [
                'method' => 'debit_card',
                'provider' => 'pagbank',
                'provider_type_code' => 'invalid_amount',
                'raw_response_json' => [
                    'message' => 'Amount must be greater than zero.',
                ],
            ],
        ];
    }

    if (empty($email)) {
        return [
            'code' => 'error',
            'msg'  => [
                'method' => 'debit_card',
                'provider' => 'pagbank',
                'provider_type_code' => 'invalid_customer_email',
                'raw_response_json' => [
                    'message' => 'Customer email is required.',
                ],
            ],
        ];
    }

    if ($savedCardId === '' && $token === '') {
        return [
            'code' => 'error',
            'msg'  => [
                'method' => 'debit_card',
                'provider' => 'pagbank',
                'provider_type_code' => 'missing_card_token',
                'raw_response_json' => [
                    'message' => 'Either encrypted card token or provider_card_id must be provided.',
                ],
            ],
        ];
    }

    // Unlike credit_card, PagBank REQUIRES 3DS authentication for debit
    // charges (charges[0].payment_method.authentication_method) -- without
    // it the /orders call is rejected outright with error 40002. Fail
    // early here instead of sending a request PagBank will reject anyway.
    // The id comes from the browser running PagSeguro.authenticate3DS()
    // (see assets/scripts/credit_card.js) before submitting the form.
    $threeDsId = trim((string)($paymentData['debit_threeds_id'] ?? $paymentData['threeds_id'] ?? ''));

    if ($threeDsId === '') {
        return [
            'code' => 'error',
            'msg'  => [
                'method' => 'debit_card',
                'provider' => 'pagbank',
                'provider_type_code' => 'missing_3ds_authentication',
                'raw_response_json' => [
                    'message' => 'Debit card charges require 3DS authentication.',
                ],
            ],
        ];
    }

    /**
     *
     * Customer
     *
     */
    $customerName = trim((string)(
        $order['customer_name']
        ?? trim((string)($order['customer_first_name'] ?? '') . ' ' . (string)($order['customer_last_name'] ?? ''))
    ));

    $customer = [
        'name'  => $customerName,
        'email' => $email,
    ];

    $taxId = preg_replace('/\D+/', '', (string)($order['customer_document_number'] ?? ''));
    if ($taxId !== '') {
        $customer['tax_id'] = $taxId;
    }

    $phone = pagbank_normalize_phone((string)($order['customer_phone'] ?? ''));
    if (!empty($phone)) {
        $customer['phones'] = [$phone];
    }

    /**
     *
     * Shipping / billing address snapshot.
     *
     */
    if (!empty($order['address']) && is_array($order['address']))
    {
        $addr = $order['address'];
        $customer['address'] = [
            'street'      => (string)($addr['street_name'] ?? ''),
            'number'      => (string)($addr['street_number'] ?? ''),
            'complement'  => (string)($addr['complement'] ?? ''),
            'locality'    => (string)($addr['neighborhood'] ?? ''),
            'city'        => (string)($addr['city'] ?? ''),
            'region_code' => strtoupper((string)($addr['state'] ?? '')),
            'country'     => strtoupper((string)($addr['country'] ?? 'BRA')),
            'postal_code' => preg_replace('/\D+/', '', (string)($addr['zip_code'] ?? '')),
        ];
    }

    /**
     *
     * Items
     *
     */
    $pagbankItems = [];
    foreach ($itemsLines as $i => $line)
    {
        $qty       = max(1, (int)($line['quantity'] ?? 1));
        $unitPrice = (float)($line['unit_price'] ?? $line['price'] ?? 0);
        $valueCents = (int)round($unitPrice * 100);

        $pagbankItems[] = [
            'reference_id' => (string)($line['id'] ?? $line['item_id'] ?? $line['product_id'] ?? ($i + 1)),
            'name'         => (string)($line['title'] ?? $line['item_name'] ?? 'Item'),
            'quantity'     => $qty,
            'unit_amount'  => $valueCents,
        ];
    }
    if (empty($pagbankItems))
    {
        $pagbankItems[] = [
            'reference_id' => $orderId !== '' ? $orderId : '1',
            'name'         => (string)($itemsLines[0]['item_name'] ?? 'Order payment'),
            'quantity'     => 1,
            'unit_amount'  => (int)round($amount * 100),
        ];
    }

    /**
     *
     * Charge.
     * Debit is always captured immediately -- there's no
     * authorize-now/capture-later concept to support here.
     *
     */
    $charge = [
        'reference_id' => $paymentReference,
        'description'  => (string)($itemsLines[0]['title'] ?? $itemsLines[0]['item_name'] ?? 'Order payment'),
        'amount'       => [
            'value'    => (int)round($amount * 100),
            'currency' => 'BRL',
        ],
        'payment_method' => [
            'type'    => 'DEBIT_CARD',
            'capture' => true,
        ],
    ];

    /**
     * Statement descriptor (soft descriptor)
     */
    $softDescriptor = strtoupper((string)($paymentData['debit_statement_descriptor'] ?? $paymentData['statement_descriptor'] ?? ($info['short_name'] ?? 'PAYMENT')));
    $softDescriptor = preg_replace('/[^A-Z0-9 ]/', '', $softDescriptor);
    $softDescriptor = substr(trim($softDescriptor), 0, 17);

    if ($softDescriptor !== '') {
        $charge['payment_method']['soft_descriptor'] = $softDescriptor;
    }

    /**
     * Card payload:
     * - First purchase: encrypted card
     * - Explicit provider_card_id: card.id + security_code
     */
    if ($savedCardId !== '')
    {
        $charge['payment_method']['card'] = [
            'id' => $savedCardId,
        ];

        if ($cvv !== '') {
            $charge['payment_method']['card']['security_code'] = $cvv;
        }

        $holderName = trim((string)($paymentData['debit_name'] ?? $customerName));
        if ($holderName !== '' || $taxId !== '') {
            $charge['payment_method']['card']['holder'] = [];
            if ($holderName !== '') {
                $charge['payment_method']['card']['holder']['name'] = $holderName;
            }
            if ($taxId !== '') {
                $charge['payment_method']['card']['holder']['tax_id'] = $taxId;
            }
        }
    }

    else
    {
        $charge['payment_method']['card'] = [
            'encrypted' => $token,
            'store'     => ($userId > 0),
        ];

        $holderName = trim((string)($paymentData['debit_name'] ?? $customerName));
        if ($holderName !== '' || $taxId !== '') {
            $charge['payment_method']['holder'] = [];
            if ($holderName !== '') {
                $charge['payment_method']['holder']['name'] = $holderName;
            }
            if ($taxId !== '') {
                $charge['payment_method']['holder']['tax_id'] = $taxId;
            }
        }
    }


    /**
     * 3DS authentication -- required for debit (validated earlier in this
     * function; $threeDsId is guaranteed non-empty at this point).
     * Nested inside payment_method, NOT a sibling of it -- confirmed both
     * by PagBank's own sample payloads and by the parameter_name
     * ("charges[0].payment_method.authentication_method") on error 40002
     * when it's misplaced.
     */
    $charge['payment_method']['authentication_method'] = [
        'type' => 'THREEDS',
        'id'   => $threeDsId,
    ];


    /**
     * Order payload
     */
    $payload = [
        'reference_id' => $paymentReference,
        'customer'     => $customer,
        'items'        => $pagbankItems,
        'charges'      => [$charge],
        'notification_urls' => [
            rest_api_route_url('order-notification?gateway=pagbank&hash='. urlencode($paymentHash)),
        ],
    ];

    if ($debug) {
        echo '<pre>';
        dump($payload);
        echo '</pre>';
    }

    /**
     * Send request
     */
    try {
        $request = pagbank_request('/orders', 'POST', $payload, [], $debug);
        $raw_response_json[] = $request['response'];

        $response = $request['response'];
    }

    catch (\Throwable $e)
    {
        if ($debug) {
            print_r($e->getMessage());
        }

        return [
            'code' => 'error',
            'msg'  => [
                'method' => 'debit_card',
                'provider' => 'pagbank',
                'provider_type_code' => 'request_exception',
                'raw_response_json' => [
                    'error_message' => $e->getMessage(),
                ],
            ],
        ];
    }

    if ($debug) {
        print_r($raw_response_json);
    }

    /**
     * Extract first charge
     */
    $chargeResponse = (array)($response['charges'][0] ?? []);
    $status         = strtoupper((string)($chargeResponse['status'] ?? ''));
    $statusId = pagbank_payment_status_id([
        'status' => $status,
    ]);

    $paymentMethod       = (array)($chargeResponse['payment_method'] ?? []);
    $paymentResp         = (array)($chargeResponse['payment_response'] ?? []);
    $amountResp          = (array)($chargeResponse['amount'] ?? []);
    $providerPaymentId   = (string)($chargeResponse['id'] ?? '');

    $errorMessages       = (array)($response['error_messages'] ?? []);
    $providerOrderId     = (string)($response['id'] ?? '');
    $providerTypeCode    = (string)($paymentResp['code'] ?? $status);
    $currency            = (string)($amountResp['currency'] ?? 'BRL');
    $amountPaid          = isset($amountResp['value']) ? ((float)$amountResp['value'] / 100) : round($amount, 2);
    $gatewayFee          = null;
    $netAmount           = null;

    /**
     * Save card token locally if PagBank returned it.
     */
    try
    {
        $returnedCard = (array)($paymentMethod['card'] ?? []);
        $returnedCardId = trim((string)($returnedCard['id'] ?? ''));

        if ($userId > 0 && $returnedCardId !== '' && ($status == 'PAID' || $status == 'AUTHORIZED'))
        {
            $pm = [
              'provider'             => 'pagbank',
              'method'               => 'debit_card',
              'provider_customer_id' => null,
              'provider_card_id'     => $response['charges'][0]['payment_method']['card']['id'] ?? null,
              'brand'                => null,
              'brand_name'           => $response['charges'][0]['payment_method']['card']['brand'] ?? null,
              'issuer_name'          => $response['charges'][0]['payment_method']['card']['issuer']['name'] ?? null,
              'first6'               => $response['charges'][0]['payment_method']['card']['first_digits'] ?? null,
              'last4'                => $response['charges'][0]['payment_method']['card']['last_digits'] ?? null,
              'exp_month'            => $response['charges'][0]['payment_method']['card']['exp_month'] ?? null,
              'exp_year'             => $response['charges'][0]['payment_method']['card']['exp_year'] ?? null,
              'holder_name'          => $response['charges'][0]['payment_method']['card']['holder']['name'] ?? null,
            ];

            $user_payment_method_id = save_user_payment_method($userId, $pm, true, $debug);
        }
    } catch (\Throwable $e) {
        if ($debug) {
            echo "\nCard save skipped/failed: " . $e->getMessage() . "\n";
        }
    }

    $response = [
        'status_id'           => $statusId,
        'method'              => (string)($data['payment_template']['method'] ?? 'debit_card'),
        'provider'            => 'pagbank',
        'currency'            => $currency ?: null,
        'amount'              => round($amountPaid, 2),
        'gateway_fee'         => $gatewayFee,
        'net_amount'          => $netAmount,
        'installments'        => 1,
        'installment_amount'  => round($amountPaid, 2),
        'provider_payment_id' => $providerPaymentId,
        'provider_order_id'   => $providerOrderId,
        'provider_reference'  => $paymentReference,
        'provider_type_code'  => $providerTypeCode,
        'raw_response_json'   => $raw_response_json,
    ];

    $res = [
        'code' => pagbank_response_code($status),
        'msg'  => $response,
    ];

    // Return user payment method id
    if (!empty($user_payment_method_id)) {
        $res['user_payment_method_id'] = $user_payment_method_id;
    }

    // Error messages
    if (!empty($errorMessages)) {
        $res['error_messages'][] = pagbank_resolve_error_message($errorMessages, $status);
    }

    // print_r($res);

    return $res ?? [];
}


/**
 * Process a PIX payment using PagBank Order API.
 *
 * This flow creates an order with QR Code data. For PIX payments in PagBank,
 * the request should use the `qr_codes` object instead of `charges`.
 *
 * Expected structure:
 * $data = [
 *   'order' => [...],
 *   'payment_template' => [
 *     'amount' => 100.50,
 *     'method' => 'pix',
 *   ],
 *   'payment_data' => [
 *     'expiration_date' => '2026-03-11T23:59:59-03:00', // optional
 *   ],
 *   'items_lines' => [...],
 * ];
 *
 * @param array $data Payment payload.
 * @param bool $debug Optional. Dump request/response when true.
 *
 * @return array
 */
function pagbank_pix_process_payment(array $data, bool $debug = false): array
{
    global $info;

    $order            = (array)($data['order'] ?? []);
    $paymentHash      = (string)($data['payment_template']['payment_hash'] ?? '');
    $amount           = (float)($data['payment_template']['amount'] ?? 0);
    $paymentData      = (array)($data['payment_data'] ?? []);
    $itemsLines       = (array)($data['items_lines'] ?? []);
    $itemsFull        = (array)($data['items_full'] ?? []);
    $userId           = (int)($order['user_id'] ?? 0);
    $email            = trim((string)($order['customer_email'] ?? ''));

    $orderId          = (string)($order['id'] ?? '');
    $attempt          = (int)($order['attempt'] ?? 1);
    $paymentReference = $orderId !== '' ? "OR:{$orderId};AT:{$attempt}" : uniqid('OR:', true);


    if ($amount <= 0) {
        return [
            'code' => 'error',
            'msg'  => [
                'method' => 'pix',
                'provider' => 'pagbank',
                'provider_type_code' => 'invalid_amount',
                'raw_response_json' => [
                    'message' => 'Amount must be greater than zero.',
                ],
            ],
        ];
    }

    /**
     * Customer
     */
    $customerName = trim((string)(
        $order['customer_name']
        ?? trim((string)($order['customer_first_name'] ?? '') . ' ' . (string)($order['customer_last_name'] ?? ''))
    ));

    $customer = [];

    if ($customerName !== '') {
        $customer['name'] = $customerName;
    }

    if ($email !== '') {
        $customer['email'] = $email;
    }

    $taxId = preg_replace('/\D+/', '', (string)($order['customer_document_number'] ?? ''));
    if ($taxId !== '') {
        $customer['tax_id'] = $taxId;
    }

    $phone = pagbank_normalize_phone((string)($order['customer_phone'] ?? ''));
    if (!empty($phone)) {
        $customer['phones'] = [$phone];
    }

    /**
     * Address snapshot
     */
    if (!empty($order['address']) && is_array($order['address'])) {
        $addr = $order['address'];

        $customer['address'] = [
            'street'      => (string)($addr['street_name'] ?? ''),
            'number'      => (string)($addr['street_number'] ?? ''),
            'complement'  => (string)($addr['complement'] ?? ''),
            'locality'    => (string)($addr['neighborhood'] ?? ''),
            'city'        => (string)($addr['city'] ?? ''),
            'region_code' => strtoupper((string)($addr['state'] ?? '')),
            'country'     => strtoupper((string)($addr['country'] ?? 'BRA')),
            'postal_code' => preg_replace('/\D+/', '', (string)($addr['zip_code'] ?? '')),
        ];
    }

    /**
     * Items
     */
    $pagbankItems = [];

    foreach ($itemsLines as $i => $line)
    {
        $qty        = max(1, (int)($line['quantity'] ?? 1));
        $unitPrice  = (float)($line['unit_price'] ?? $line['price'] ?? 0);
        $valueCents = (int)round($unitPrice * 100);

        $pagbankItems[] = [
            'reference_id' => (string)($line['id'] ?? $line['item_id'] ?? $line['product_id'] ?? ($i + 1)),
            'name'         => (string)($line['title'] ?? $line['item_name'] ?? 'Item'),
            'quantity'     => $qty,
            'unit_amount'  => $valueCents,
        ];
    }

    if (empty($pagbankItems))
    {
        $pagbankItems[] = [
            'reference_id' => $orderId !== '' ? $orderId : '1',
            'name'         => (string)($itemsLines[0]['item_name'] ?? 'Order payment'),
            'quantity'     => 1,
            'unit_amount'  => (int)round($amount * 100),
        ];
    }

    /**
     * PIX QR Code
     *
     * PagBank generates the PIX QR Code automatically when the `qr_codes`
     * object is sent in the order creation request.
     */
    $expirationDate = trim((string)($paymentData['expiration_date'] ?? ''));
    if ($expirationDate === '')
    {
        $payment_expiriation = get_system_info('pyrosales_payment_expiriation');
        $expiriation_count   = (string)($payment_expiriation['count'] ?? '30');
        $expiriation_unit    = (string)($payment_expiriation['unit'] ?? 'minute');
        $expiriation_unit   .= ($expiriation_count > 1)
            ? 's'
            : '';

        $expirationDate = date('c', strtotime("+{$expiriation_count} {$expiriation_unit}"));
    }

    $payload = [
        'reference_id' => $paymentReference,
        'customer'     => $customer,
        'items'        => $pagbankItems,
        'qr_codes'     => [[
            'amount' => [
                'value' => (int)round($amount * 100),
            ],
            'expiration_date' => $expirationDate,
        ]],
        'notification_urls' => [
            rest_api_route_url('order-notification?gateway=pagbank&hash='. urlencode($paymentHash)),
            // 'https://stg.conquiste.me/rest-api/order-notification?gateway=pagbank&hash='. urlencode($paymentHash),
        ],
    ];

    if ($debug) {
        dump($payload);
    }

    /**
     * Send request
     */
    try {
        $request = pagbank_request('/orders', 'POST', $payload, [], $debug);
        $raw_response_json[] = $request['response'];

        $response = $request['response'];
    }

    catch (\Throwable $e)
    {
        if ($debug) {
            print_r($e->getMessage());
        }

        return [
            'code' => 'error',
            'msg'  => [
                'method' => 'pix',
                'provider' => 'pagbank',
                'provider_type_code' => 'request_exception',
                'raw_response_json' => [
                    'error_message' => $e->getMessage(),
                ],
            ],
        ];
    }

    if ($debug) {
        dump($raw_response_json);
    }

    /**
     * Extract QR Code response
     */
    $qrCode          = (array)($response['qr_codes'][0] ?? []);
    $qrCodeLinks     = (array)($qrCode['links'] ?? []);
    $amountResp      = (array)($qrCode['amount'] ?? []);
    $texts           = (array)($qrCode['texts'] ?? []);
    $images          = (array)($qrCode['images'] ?? []);
    $paymentCode     = (string)($qrCode['text'] ?? '');
    $errorMessages   = (array)($response['error_messages'] ?? []);

    $status          = !empty($errorMessages) ? 'FAILED' : 'WAITING';
    // $statusId        = pagbank_payment_status_id($status);
    $statusId        = pagbank_payment_status_id([
        'status' => $status,
    ]);
    $providerOrderId  = (string)($response['id'] ?? '');
    $providerTypeCode = (string)($status ?: 'WAITING');
    $currency         = (string)($amountResp['currency'] ?? 'BRL');
    $amountValue      = isset($amountResp['value']) ? ((float)$amountResp['value'] / 100) : round($amount, 2);

    /**
     * Try to extract copy-and-paste code and QR Code image from known response keys.
     */
    $pixCode = '';
    $pixQrCodeUrl = '';
    $pixQrCodeBase64 = '';

    if (!empty($texts))
    {
        foreach ($texts as $text)
        {
            if (!empty($text['value'])) {
                $pixCode = (string)$text['value'];
                break;
            }
        }
    }

    if (!empty($images))
    {
        foreach ($images as $image)
        {
            if (!empty($image['content'])) {
                $pixQrCodeBase64 = (string)$image['content'];
                break;
            }

            if (!empty($image['href'])) {
                $pixQrCodeUrl = (string)$image['href'];
                break;
            }
        }
    }

    if (!empty($qrCodeLinks))
    {
        foreach ($qrCodeLinks as $link)
        {
            if ($pixQrCodeUrl === '' && !empty($link['href'])) {
                $pixQrCodeUrl = (string)$link['href'];
            }
        }
    }

    /**
     * Apply the order purpose to the order type plan that has a trial period.
     */
    // if ($order['order_type'] == 'plan' && !empty($itemsFull[0]))
    // {
    //     if ($itemsFull[0]['trial_days'] > 0) {
    //         $order_purpose    = 'trial_validation';
    //     }
    // }

    /**
     * Normalize local response
     */
    $response = [
        'status_id'           => $statusId,
        'method'              => (string)($data['payment_template']['method'] ?? 'pix'),
        'provider'            => 'pagbank',
        'currency'            => $currency ?: null,
        'amount'              => round($amountValue, 2),
        'gateway_fee'         => null,
        'net_amount'          => null,
        'installments'        => 1,
        'installment_amount'  => round($amountValue, 2),
        // 'provider_payment_id' => $providerPaymentId,
        'provider_order_id'   => $providerOrderId,
        'provider_reference'  => $paymentReference,
        'provider_type_code'  => $providerTypeCode,
        'code'                => $paymentCode ?: null,
        'payment_link'        => $pixQrCodeUrl ?: null,
        'expires_at'          => (string)($qrCode['expiration_date'] ?? $expirationDate),
        'raw_response_json'   => $raw_response_json,
    ];

    $code = pagbank_response_code($status);

    $res = [
        'code' => $code,
        'msg'  => $response,
    ];

    if ($code != 'error') {
        $res['redirect_payment_page'] = true;
    }

    // Order purpose
    // if (!empty($order_purpose)) {
    //     $res['order_purpose'] = $order_purpose;
    // }

    if (!empty($errorMessages)) {
        $res['error_messages'][] = pagbank_resolve_error_message($errorMessages, $status);
    }

    return $res ?? [];
}
