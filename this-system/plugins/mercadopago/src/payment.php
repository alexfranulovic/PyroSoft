<?php
if (!isset($seg)) exit;

MercadoPago\SDK::setAccessToken(MERCADOPAGO_ACCESS_TOKEN);

function mercadopago_credit_card_payment(array $data, bool $debug = false): array
{
    global $info;

    $order       = (array)($data['order'] ?? []);
    $amount      = (float)($data['payment_template']['amount'] ?? 0);
    $paymentData = (array)($data['payment_data'] ?? []);
    $itemsLines  = (array)($data['items_lines'] ?? []);
    $deviceId    = !empty($data['device_id']) ? (string)$data['device_id'] : null;
    $userId      = (int)($order['user_id'] ?? 0);
    $token       = trim((string)($paymentData['token'] ?? ''));
    $email       = trim((string)($order['customer_email'] ?? ''));
    $customerId  = mercadopago_get_or_create_customer_id($email, $debug);

    if (empty($token)) {
        return ["code" => "error", "msg" => ["reason" => "Token is empty"]];
    }


    /**
     *
     * Build payer
     *
     */
    $payer = [
        "email" => $email,
        "first_name" => (string)($order['customer_first_name'] ?? ''),
        "last_name"  => (string)($order['customer_last_name'] ?? ''),
        "identification" => [
            "type"   => (string)($order['customer_document_type'] ?? ''),
            "number" => (string)($order['customer_document_number'] ?? ''),
        ],
    ];

    if (!empty($order['customer_phone'])) {
        $payer['phone'] = ["number" => (string)$order['customer_phone']];
    }

    if (!empty($order['address']) && is_array($order['address'])) {
        $addr = $order['address'];
        $payer['address'] = [
            "zip_code"      => (string)($addr['zip_code'] ?? ''),
            "street_name"   => (string)($addr['street_name'] ?? ''),
            "street_number" => (string)($addr['street_number'] ?? ''),
            "neighborhood"  => (string)($addr['neighborhood'] ?? ''),
            "city"          => (string)($addr['city'] ?? ''),
            "federal_unit"  => (string)($addr['state'] ?? ''),
        ];
    }

    /**
     *
     * Build items
     *
     */
    $mpItems = [];
    foreach ($itemsLines as $i => $line) {
        $mpItems[] = [
            "id"          => (string)($line['id'] ?? $line['item_id'] ?? $line['product_id'] ?? ($i + 1)),
            "title"       => (string)($line['title'] ?? $line['item_name'] ?? 'Item'),
            "description" => (string)($line['description'] ?? $line['item_name'] ?? 'Item'),
            "category_id" => (string)($line['category_id'] ?? 'others'),
            "quantity"    => (int)($line['quantity'] ?? 1),
            "unit_price"  => (float)($line['unit_price'] ?? $line['price'] ?? 0),
        ];
    }


    /**
     *
     * Saved card flow (provider_card_id)
     *
     */
    $savedCard = null;
    try {
        $savedCard = mercadopago_apply_saved_card_if_provided($order, $paymentData, $payer, $debug);
    } catch (\Throwable $e) {
        return [
            "code" => "error",
            "msg"  => [
                "method" => "credit_card",
                "provider" => "mercadopago",
                "provider_type_code" => "saved_card_not_allowed",
                "raw_response_json" => [
                    "error_message" => $e->getMessage(),
                ],
            ],
        ];
    }


    // print_r($savedCard);


    /**
     *
     * Create payment
     *
     */
    $payment = new MercadoPago\Payment();

    $attempt = (int)($order['attempt'] ?? 1);
    $orderId = (string)($order['id'] ?? '');
    $payment->external_reference = $orderId !== '' ? "OR:{$orderId}:attempt:{$attempt}" : null;
    $installments = (int)($paymentData['installments'] ?? 1);

    $payment->transaction_amount = round($amount, 2);
    // If paying with a saved card, keep payer.id (customer_id) already set.
    // Token is still required (CVV tokenization on front-end).
    $payment->token              = $token;
    $payment->description        = $mpItems[0]['title'] ?? ($itemsLines[0]['item_name'] ?? 'Order payment');
    $payment->installments       = $installments;
    $payment->payment_method_id  = (string)($paymentData['card_brand'] ?? '');
    // $payment->notification_url   = rest_api_route_url('handle-order-notification');
    $payment->notification_url   = 'https://euphoriasystems.com.br/api/webhooks/mercadopago';
    $payment->additional_info    = ["items" => $mpItems];
    $payment->payer              = $payer;
    $payment->statement_descriptor = substr(
        strtoupper((string)($paymentData['statement_descriptor'] ?? ($info['name'] ?? 'PAYMENT'))),
        0,
        22
    );

    $options = [];
    if (!empty($deviceId)) {
        $options["headers"] = [ "X-meli-session-id" => $deviceId ];
    }


    /**
     *
     * Try payment
     *
     */
    try {
        $payment->save($options);
        if ($debug) print_r($payment);
    }

    catch (\Throwable $e)
    {
        return [
            "code" => "error",
            "msg"  => [
                "method" => "credit_card",
                "provider" => "mercadopago",
                "provider_type_code" => "sdk_exception",
                "raw_response_json" => [
                    "error_message" => $e->getMessage(),
                ],
            ],
        ];
    }

    /**
     *
     * Build normalized response
     *
     */
    $status             = (string)($payment->status ?? '');
    $statusDetail       = (string)($payment->status_detail ?? '');
    $currency           = (string)($payment->currency_id ?? '');
    $method             = (string)($payment->payment_type_id ?? 'credit_card');
    $amountPaid         = (float)($payment->transaction_amount ?? 0);


    /**
     *
     * Save raw response as json.
     *
     */
    $raw = null;
    try {
        $raw_response_json = MercadoPago\SDK::getEntityMapper()->toArray($payment);
    } catch (\Throwable $e) {
        $raw_response_json = [
            'id' => $payment->id ?? null,
            'status' => $payment->status ?? null,
            'status_detail' => $payment->status_detail ?? null,
            'message' => 'entity_mapper_failed',
            'error' => $e->getMessage(),
        ];
    }


    /**
     *
     * Save only if we have a user_id to bind this payment method.
     *
     */
    try
    {
        if ($userId > 0)
        // if (($status == 'approved') && $userId > 0)
        {
            if ($customerId !== '' && $token !== '' )
            {
                $pm = mercadopago_attach_card_to_customer($customerId, $token);

                $pm['holder_name']  = trim((string)($paymentData['name'] ?? ''));
                $pm['method']       = $method;
                $pm['provider']     = 'mercadopago';

                $savedId = save_user_payment_method($userId, $pm, true, $debug);

                if ($savedId && $debug) {
                    echo "\nSaved card local id: {$savedId}\n";
                }
            }

            else {
                if ($debug) echo "\nCard save skipped: could not get Mercado Pago customer id.\n";
            }

        }
    }
    catch (\Throwable $e)
    {
        if ($debug) echo "\nCard save skipped/failed: " . $e->getMessage() . "\n";
    }


    /**
     * installment amount
     */
    $installmentAmount = null;
    if (!empty($payment->transaction_details) && is_object($payment->transaction_details)) {
        if (isset($payment->transaction_details->installment_amount)) {
            $installmentAmount = (float)$payment->transaction_details->installment_amount;
        }
    }

    /**
     * gateway fee: sum charges_details fee amounts.original
     */
    $gatewayFee = 0.0;
    if (!empty($payment->charges_details) && is_array($payment->charges_details)) {
        foreach ($payment->charges_details as $cd) {
            if (!is_object($cd)) continue;
            $type = (string)($cd->type ?? '');
            $name = (string)($cd->name ?? '');
            $isFee = ($type === 'fee') || (stripos($name, 'fee') !== false);

            if ($isFee && isset($cd->amounts) && is_object($cd->amounts) && isset($cd->amounts->original)) {
                $gatewayFee += (float)$cd->amounts->original;
            }
        }
    }

    // net amount: prefer transaction_details.net_received_amount; fallback payment->net_received_amount
    $netAmount = (float) ($amountPaid - $gatewayFee);

    // provider_type_code: use status_detail (most specific)
    $providerTypeCode = $statusDetail !== '' ? $statusDetail : $status;

    // status_id mapping (adjust to your DB ids)
    $statusId = mercadopago_map_status_id($status, $statusDetail);

    $response = [
        'status_id'           => $statusId,
        'method'              => $data['payment_template']['method'],               // credit_card
        'provider'            => 'mercadopago',
        'currency'            => $currency ?: null,
        'amount'              => $amountPaid,
        'gateway_fee'         => round($gatewayFee, 2),
        'net_amount'          => round($netAmount, 2),
        'installments'        => (int)($payment->installments ?? 1),
        'installment_amount'  => is_null($installmentAmount) ? null : round((float)$installmentAmount, 2),
        'provider_payment_id' => (string)($payment->id ?? ''),
        'provider_type_code'  => $providerTypeCode,
        'raw_response_json'   => $raw_response_json, // keep full object for auditing
    ];

    return [
        "code" => ($status === 'approved') ? 'success' : (($status === 'in_process') ? 'processing' : 'error'),
        "msg"  => $response,
    ];
}
