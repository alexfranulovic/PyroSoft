<?php
if (!isset($seg)) exit;

/**
 * PagBank configuration.
 *
 * Required constants:
 * - PAGBANK_API_BASE
 * - PAGBANK_API_TOKEN
 *
 * Example:
 * define('PAGBANK_API_BASE', 'https://sandbox.api.pagseguro.com');
 * define('PAGBANK_API_TOKEN', 'YOUR_TOKEN');
 */


/**
 * Create and pay an order with credit card using PagBank Order API.
 *
 * Expected input structure follows the same pattern used in Mercado Pago:
 *
 * $data = [
 *   'order' => [
 *     'id' => 123,
 *     'attempt' => 1,
 *     'user_id' => 1,
 *     'customer_first_name' => 'John',
 *     'customer_last_name' => 'Doe',
 *     'customer_name' => 'John Doe',
 *     'customer_email' => 'john@domain.com',
 *     'customer_phone' => '11999999999',
 *     'customer_document_type' => 'CPF',
 *     'customer_document_number' => '12345678909',
 *     'address' => [
 *       'zip_code' => '01310100',
 *       'street_name' => 'Avenida Paulista',
 *       'street_number' => '1000',
 *       'complement' => 'Suite 1',
 *       'neighborhood' => 'Bela Vista',
 *       'city' => 'Sao Paulo',
 *       'state' => 'SP',
 *       'country' => 'BRA',
 *     ],
 *   ],
 *   'payment_template' => [
 *     'amount' => 199.90,
 *     'method' => 'credit_card',
 *   ],
 *   'payment_data' => [
 *     // First purchase:
 *     'token' => 'PAGBANK_ENCRYPTED_CARD',
 *
 *     // Saved card purchase:
 *     // 'provider_card_id' => 'CARD_XXXX',
 *     // 'cvv' => '123',
 *
 *     'installments' => 1,
 *     'name' => 'JOHN DOE',
 *     'statement_descriptor' => 'MYSTORE',
 *
 *     // Optional 3DS:
 *     // 'threeds_id' => '...'
 *   ],
 *   'items_lines' => [
 *     [
 *       'id' => 10,
 *       'item_name' => 'My product',
 *       'title' => 'My product',
 *       'description' => 'My product',
 *       'quantity' => 1,
 *       'price' => 199.90,
 *       'unit_price' => 199.90,
 *     ]
 *   ]
 * ];
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
function pagbank_credit_card_process_payment(array $data, bool $debug = false): array
{
    global $info;

    $order       = (array)($data['order'] ?? []);
    $amount      = (float)($data['payment_template']['amount'] ?? 0);
    $paymentData = (array)($data['payment_data'] ?? []);
    $itemsLines  = (array)($data['items_lines'] ?? []);
    $userId      = (int)($order['user_id'] ?? 0);

    $orderId     = (string)($order['id'] ?? '');
    $attempt     = (int)($order['attempt'] ?? 1);
    $email       = trim((string)($order['customer_email'] ?? ''));
    $token       = trim((string)($paymentData['token'] ?? '')); // Encrypted card from PagBank JS SDK
    $savedCardId = trim((string)($paymentData['provider_card_id'] ?? ''));
    $cvv         = trim((string)($paymentData['cvv'] ?? ''));
    $installments = max(1, (int)($paymentData['installments'] ?? 1));

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
     * Customer
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
     * Shipping / billing address snapshot.
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

    foreach ($itemsLines as $i => $line) {
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

    if (empty($pagbankItems)) {
        $pagbankItems[] = [
            'reference_id' => $orderId !== '' ? $orderId : '1',
            'name'         => (string)($itemsLines[0]['item_name'] ?? 'Order payment'),
            'quantity'     => 1,
            'unit_amount'  => (int)round($amount * 100),
        ];
    }

    /**
     * Charge
     */
    $charge = [
        'reference_id' => $orderId !== '' ? "OR:{$orderId}:AT:{$attempt}" : uniqid('OR:', true),
        'description'  => (string)($itemsLines[0]['title'] ?? $itemsLines[0]['item_name'] ?? 'Order payment'),
        'amount'       => [
            'value'    => (int)round($amount * 100),
            'currency' => 'BRL',
        ],
        'payment_method' => [
            'type'         => 'CREDIT_CARD',
            'installments' => $installments,
            'capture'      => true,
        ],
    ];

    /**
     * Statement descriptor (soft descriptor)
     */
    $softDescriptor = strtoupper((string)($paymentData['statement_descriptor'] ?? ($info['name'] ?? 'PAYMENT')));
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
    if ($savedCardId !== '') {
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
    } else {
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
        $charge['authentication_method'] = [
            'type' => 'THREEDS',
            'id'   => $threeDsId,
        ];
    }

    /**
     * Order payload
     */
    $payload = [
        'reference_id' => $orderId !== '' ? (string)$orderId : uniqid('ORDER_', true),
        'customer'     => $customer,
        'items'        => $pagbankItems,
        'charges'      => [$charge],
        'notification_urls' => [
            'https://euphoriasystems.com.br/api/webhooks/pagbank',
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
        $raw_response_json = pagbank_request('/orders', 'POST', $payload, $debug);
//         $raw_response_json = json_decode('{
//     "code": "success",
//     "http_code": 201,
//     "response": {
//         "id": "ORDE_DC0C0DA4-EE19-48BB-B566-1456678633C3",
//         "reference_id": 340,
//         "created_at": "2026-03-06T05:16:38.694-03:00",
//         "customer": {
//             "name": "Admin PyroSoft",
//             "email": "euforiagrup@gmail.com",
//             "tax_id": 66118297096
//         },
//         "items": [
//             {
//                 "reference_id": 340,
//                 "name": "Plano básico",
//                 "quantity": 1,
//                 "unit_amount": 290
//             }
//         ],
//         "charges": [
//             {
//                 "id": "CHAR_0443AF11-910D-4BA7-8767-AE2F3C442419",
//                 "reference_id": "OR:340:attempt:1",
//                 "status": "PAID",
//                 "created_at": "2026-03-06T05:16:39.351-03:00",
//                 "paid_at": "2026-03-06T05:16:41.000-03:00",
//                 "description": "Plano básico",
//                 "amount": {
//                     "value": 290,
//                     "currency": "BRL",
//                     "summary": {
//                         "total": 290,
//                         "paid": 290,
//                         "refunded": 0
//                     }
//                 },
//                 "payment_response": {
//                     "code": 20000,
//                     "message": "SUCESSO",
//                     "reference": "032416400102",
//                     "raw_data": {
//                         "authorization_code": 145803,
//                         "nsu": "032416400102",
//                         "reason_code": "00"
//                     }
//                 },
//                 "payment_method": {
//                     "type": "CREDIT_CARD",
//                     "installments": 1,
//                     "capture": true,
//                     "card": {
//                         "id": "CARD_BEA28E90-036B-4119-A498-95F536F9690E",
//                         "brand": "mastercard",
//                         "first_digits": 516292,
//                         "last_digits": 6444,
//                         "exp_month": 11,
//                         "exp_year": 2033,
//                         "holder": {
//                             "name": "Alex Franulovic"
//                         },
//                         "store": true,
//                         "issuer": {
//                             "name": "NU PAGAMENTOS SA",
//                             "product": "Platinum Mastercard Card"
//                         },
//                         "country": "BRA"
//                     },
//                     "soft_descriptor": "CONQUISTE"
//                 },
//                 "metadata": {
//                     "ps_order_id": "ORDE_DC0C0DA4-EE19-48BB-B566-1456678633C3"
//                 },
//                 "links": [
//                     {
//                         "rel": "SELF",
//                         "href": "https://sandbox.api.pagseguro.com/charges/CHAR_0443AF11-910D-4BA7-8767-AE2F3C442419",
//                         "media": "application/json",
//                         "type": "GET"
//                     },
//                     {
//                         "rel": "CHARGE.CANCEL",
//                         "href": "https://sandbox.api.pagseguro.com/charges/CHAR_0443AF11-910D-4BA7-8767-AE2F3C442419/cancel",
//                         "media": "application/json",
//                         "type": "POST"
//                     }
//                 ]
//             }
//         ],
//         "notification_urls": [
//             "https://euphoriasystems.com.br/api/webhooks/pagbank"
//         ],
//         "links": [
//             {
//                 "rel": "SELF",
//                 "href": "https://sandbox.api.pagseguro.com/orders/ORDE_DC0C0DA4-EE19-48BB-B566-1456678633C3",
//                 "media": "application/json",
//                 "type": "GET"
//             },
//             {
//                 "rel": "PAY",
//                 "href": "https://sandbox.api.pagseguro.com/orders/ORDE_DC0C0DA4-EE19-48BB-B566-1456678633C3/pay",
//                 "media": "application/json",
//                 "type": "POST"
//             }
//         ]
//     }
// }', true);
        $response = $raw_response_json['response'];
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
        echo '<pre>';
        dump($raw_response_json);
        echo '</pre>';
    }

    /**
     * Extract first charge
     */
    $chargeResponse = (array)($response['charges'][0] ?? []);
    $paymentMethod  = (array)($chargeResponse['payment_method'] ?? []);
    $paymentResp    = (array)($chargeResponse['payment_response'] ?? []);
    $amountResp     = (array)($chargeResponse['amount'] ?? []);

    $status              = strtoupper((string)($chargeResponse['status'] ?? ''));
    $providerPaymentId   = (string)($chargeResponse['id'] ?? '');
    $providerTypeCode    = (string)($paymentResp['code'] ?? $status);
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

        if ($userId > 0 && $returnedCardId !== '')
        {
            $pm = [
              'provider'             => 'pagbank',
              'method'               => 'credit_card',
              'provider_customer_id' => null,
              'provider_card_id'     => $response['charges'][0]['payment_method']['card']['id'] ?? null,
              'brand'                => null,
              'brand_name'           => $response['charges'][0]['payment_method']['card']['brand'] ?? null,
              'issuer_name'          => $response['charges'][0]['payment_method']['card']['issuer']['name'] ?? null,
              'last4'                => $response['charges'][0]['payment_method']['card']['last_digits'] ?? null,
              'exp_month'            => $response['charges'][0]['payment_method']['card']['exp_month'] ?? null,
              'exp_year'             => $response['charges'][0]['payment_method']['card']['exp_year'] ?? null,
              'holder_name'          => $response['charges'][0]['payment_method']['card']['holder']['name'] ?? null,
            ];

            save_user_payment_method($userId, $pm, true, $debug);
        }
    } catch (\Throwable $e) {
        if ($debug) {
            echo "\nCard save skipped/failed: " . $e->getMessage() . "\n";
        }
    }

    $statusId = pagbank_map_status_id($status);

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
        'provider_type_code'  => $providerTypeCode,
        'raw_response_json'   => $raw_response_json,
    ];

    return [
        'code' => pagbank_normalize_return_code($status),
        'msg'  => $response,
    ];
}
