<?php
if (!isset($seg)) exit;


/**
 * Get or create Mercado Pago customer by email.
 */
function mercadopago_get_or_create_customer_id(string $email, bool $debug = false): string
{
    $email = trim(strtolower($email));
    if ($email === '') {
        throw new Exception("Missing customer email.");
    }

    // SEARCH
    try {
        $customers = MercadoPago\Customer::search(['email' => $email]);

        if ($debug) {
            echo "\nCustomer search raw:\n";
            print_r($customers);
        }

        // Fix: SDK returns ArrayObject
        if ($customers instanceof ArrayObject) {
            $customers = $customers->getArrayCopy();
        }

        if (is_array($customers) && !empty($customers[0]->id)) {
            return (string)$customers[0]->id;
        }
    } catch (\Throwable $e) {
        if ($debug) echo "\nSearch error: {$e->getMessage()}\n";
    }

    // CREATE
    $c = new MercadoPago\Customer();
    $c->email = $email;
    $c->save();

    if ($debug) print_r($c);

    if (!empty($c->id)) {
        return (string)$c->id;
    }

    // If already exists but SDK structure was weird
    if (!empty($c->error) && is_object($c->error)) {
        $causes = $c->error->causes ?? null;
        if (is_array($causes)) {
            foreach ($causes as $cause) {
                if ((string)($cause->code ?? '') === '101') {
                    if ($debug) echo "\nCustomer exists but SDK search structure mismatch.\n";
                    return ''; // Let caller decide
                }
            }
        }
    }

    throw new Exception("Failed to create Mercado Pago customer.");
}

/**
 * Attach card token to a Mercado Pago customer (creates a saved card) and returns card snapshot.
 */
function mercadopago_attach_card_to_customer(string $customerId, string $token): array
{
    $token = trim((string)$token);
    if ($token === '') throw new Exception("Missing card token.");

    $card = new MercadoPago\Card();
    $card->customer_id = $customerId;
    $card->token = $token;
    $card->save();

    if (empty($card->id)) {
        throw new Exception("Failed to attach card to Mercado Pago customer.");
    }

    return [
        'provider_customer_id' => $customerId,
        'provider_card_id'     => (string)$card->id,
        'last4'                => $card->last_four_digits ?? null,
        'exp_month'            => $card->expiration_month ?? null,
        'exp_year'             => $card->expiration_year ?? null,
        'brand'                => $card->payment_method->id ?? null,
        'brand_name'           => $card->payment_method->name ?? null,
        'issuer_name'          => $card->issuer->name ?? null,
    ];
}

/**
 * Fetch a saved Mercado Pago card snapshot for a given user.
 * This validates ownership (user_id) + provider + provider_card_id.
 *
 * Expected columns on tb_user_payment_methods:
 * - user_id
 * - provider
 * - provider_customer_id
 * - provider_card_id
 * - brand
 */
function mercadopago_get_saved_card_for_user(int $userId, string $CardId): ?array
{
    $userId = (int)$userId;
    $CardId = trim($CardId);

    if ($userId <= 0 || $CardId === '') return null;

    $provider = 'mercadopago';

    // NOTE: adjust table/columns if your schema differs.
    $sql = "
        SELECT
            provider_customer_id,
            provider_card_id,
            brand
        FROM tb_user_payment_methods
        WHERE user_id = " . (int)$userId . "
          AND provider = '" . addslashes($provider) . "'
          AND id = '" . addslashes($CardId) . "'
        LIMIT 1
    ";

    // Use your DB helper. Must return associative array or null.
    $row = get_result($sql);

    // If your query_it returns mysqli_result, replace this block accordingly.
    // Here we assume it returns an array row or empty.
    if (empty($row) || !is_array($row)) return null;

    if (empty($row['provider_customer_id']) || empty($row['provider_card_id'])) return null;

    return [
        'provider_customer_id' => (string)$row['provider_customer_id'],
        'provider_card_id'     => (string)$row['provider_card_id'],
        'brand'                => !empty($row['brand']) ? (string)$row['brand'] : null,
    ];
}


/**
 * If payment_data[provider_card_id] is provided, validate it belongs to the user
 * and enrich payer/paymentData accordingly.
 */
function mercadopago_apply_saved_card_if_provided(array $order, array &$paymentData, array &$payer, bool $debug = false): ?array
{
    $CardId = trim((string)($paymentData['user_payment_method'] ?? ''));
    if ($CardId === '') return null;

    $userId = (int)($order['user_id'] ?? 0);
    if ($userId <= 0) {
        throw new Exception("Missing user_id for saved card payment.");
    }


    $saved = mercadopago_get_saved_card_for_user($userId, $CardId);
    if (!$saved) {
        throw new Exception("Saved card not found for this user (user_payment_method mismatch).");
    }

    // print_r($saved);

    // Attach Mercado Pago customer id to payer
    $payer['id'] = $saved['provider_customer_id'];

    // If front-end didn't send card_brand, try to infer from saved snapshot
    if (empty($paymentData['card_brand']) && !empty($saved['brand'])) {
        $paymentData['card_brand'] = $saved['brand'];
    }

    if ($debug) {
        echo "\nUsing saved card for user_id={$userId}: {$paymentData['user_payment_method']}\n";
    }

    return $saved;
}


/**
 * Create a total or partial refund in Mercado Pago.
 *
 * API: POST /v1/payments/{id}/refunds
 * - Total refund: amount = null
 * - Partial refund: amount = float
 *
 * @param int|string $providerPaymentId Mercado Pago payment id (e.g. 147724049621)
 * @param float|null $amount Amount to refund (partial). Null = total refund.
 * @param bool $debug Print request/response details.
 * @return array Normalized response for your system.
 * @throws Exception on transport errors or invalid input.
 */
function mercadopago_refund_payment($providerPaymentId, ?float $amount = null, bool $debug = false): array
{
    $paymentId = trim((string)$providerPaymentId);
    if ($paymentId === '') {
        throw new Exception("Missing providerPaymentId.");
    }

    if (!defined('MERCADOPAGO_ACCESS_TOKEN') || !MERCADOPAGO_ACCESS_TOKEN) {
        throw new Exception("Missing MERCADOPAGO_ACCESS_TOKEN.");
    }

    // Validate partial amount
    if ($amount !== null) {
        if (!is_finite($amount) || $amount <= 0) {
            throw new Exception("Invalid refund amount.");
        }
        // Keep 2 decimals
        $amount = (float) number_format($amount, 2, '.', '');
    }

    $url = "https://api.mercadopago.com/v1/payments/{$paymentId}/refunds";

    $payload = [];
    if ($amount !== null) {
        $payload['amount'] = $amount; // partial refund requires amount :contentReference[oaicite:1]{index=1}
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER     => [
            "Authorization: Bearer " . MERCADOPAGO_ACCESS_TOKEN,
            "Content-Type: application/json",
            "Accept: application/json",

            // Optional: gives clearer "in_process" refund responses in some flows (documented for Pix, harmless elsewhere)
            "X-Render-In-Process-Refunds: true", // :contentReference[oaicite:2]{index=2}
        ],
        CURLOPT_TIMEOUT        => 30,
    ]);

    $raw = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        throw new Exception("cURL error: {$curlErr}");
    }

    $json = json_decode((string)$raw, true);

    if ($debug) {
        echo "\n[MP Refund] HTTP {$httpCode}\n";
        echo "URL: {$url}\n";
        echo "Payload: " . json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n";
        echo "Raw: {$raw}\n";
    }

    // Mercado Pago usually returns 201 Created on refund creation (or 200 in some cases)
    $ok = ($httpCode === 200 || $httpCode === 201);

    return [
        'code' => $ok ? 'success' : 'error',
        'msg'  => [
            'provider'            => 'mercadopago',
            'method'              => 'refund',
            'provider_payment_id' => $paymentId,
            'refund_amount'       => $amount,              // null => total
            'http_code'           => $httpCode,
            'provider_type_code'  => $ok ? 'refund_created' : 'refund_error',
            'raw_response_json'   => $json ?? ['raw' => $raw],
        ],
    ];
}


// $r1 = mercadopago_refund_payment(147724049621);

// Partial refund (e.g. 10.00)
// $r2 = mercadopago_refund_payment(147724049621, 10.00);
