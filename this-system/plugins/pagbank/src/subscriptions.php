<?php
if (!isset($seg)) exit;

use MercadoPago\SDK;
use MercadoPago\Client\PreApproval\PreApprovalClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\Exceptions\MPException;

MercadoPago\SDK::setAccessToken(MERCADOPAGO_ACCESS_TOKEN);

/**
 * Create a Mercado Pago subscription (preapproval) for your "plan" role.
 *
 * Expected $data shape (similar style to your payment function):
 * - order: array (customer snapshot, user_id, id, attempt, etc.)
 * - role: array (tb_user_roles row: id, name, slug, currency, sale_price, interval_unit, interval_count, trial_days, auto_renew)
 * - urls: array (notification_url, back_url_success, back_url_failure, back_url_pending)
 * - metadata: array (optional custom metadata to attach)
 *
 * Returns:
 * - code: success|processing|error
 * - msg: normalized provider response
 */
function mercadopago_preapproval_create(array $data, bool $debug = false): array
{
    global $info;

    $order    = (array)($data['order'] ?? []);
    $role     = (array)($data['items_lines'][0] ?? []);
    $urls     = (array)($data['urls'] ?? []);
    $metadata = (array)($data['metadata'] ?? []);

    $userId = (int)($order['user_id'] ?? 0);

    // Plan fields (tb_user_roles)
    $roleId        = (int)($role['id'] ?? 0);
    $planName      = trim((string)($role['name'] ?? 'Plan'));
    $currency      = trim((string)($role['currency'] ?? 'BRL'));
    $amount        = (float)($role['unit_price'] ?? $role['regular_unit_price'] ?? 0);
    $intervalUnit  = (string)($role['interval_unit'] ?? 'month');
    $intervalCount = (int)($role['interval_count'] ?? 1);
    $trialDays     = (int)($role['trial_days'] ?? 0);
    $autoRenew     = (int)($role['auto_renew'] ?? 1);

        // print_r($data);

    if ($roleId <= 0) {
        return ["code" => "error", "msg" => ["provider_type_code" => "missing_role", "reason" => "Role(plan) not provided."]];
    }

    if ($amount <= 0) {
        return ["code" => "error", "msg" => ["provider_type_code" => "invalid_amount", "reason" => "Plan amount must be > 0."]];
    }

    // Customer / payer
    $email = trim((string)($order['customer_email'] ?? ''));
    if ($email === '') {
        return ["code" => "error", "msg" => ["provider_type_code" => "missing_email", "reason" => "customer_email is required for preapproval."]];
    }

    // (Optional) ensure customer exists; you already have this helper
    $customerId = mercadopago_get_or_create_customer_id($email, $debug);

    // Build auto_recurring
    $ar = mercadopago_map_interval_to_auto_recurring($intervalUnit, $intervalCount);

    $autoRecurring = [
        "frequency"         => (int)$ar['frequency'],
        "frequency_type"    => (string)$ar['frequency_type'], // days|months
        "transaction_amount"=> round($amount, 2),
        "currency_id"       => $currency,
    ];

    // Trial (free_trial)
    if ($trialDays > 0) {
        $autoRecurring["free_trial"] = [
            "frequency"      => $trialDays,
            "frequency_type" => "days",
        ];
    }

    // URLs
    $notificationUrl = rest_api_route_url('handle-order-notification');
    $notificationUrl = 'https://euphoriasystems.com.br/rest-api/handle-order-notification';

    $returnUrl = rest_api_route_url('handle-order-notification');
    $returnUrl = 'https://euphoriasystems.com.br/rest-api/handle-subscription-notifications';

    $backUrls = [
        "success" => $returnUrl,
        "failure" => $returnUrl,
        "pending" => $returnUrl
    ];

    // External reference (helps you find it later)
    $attempt = (int)($order['attempt'] ?? 1);
    $orderId = (string)($order['id'] ?? '');
    $externalReference = $orderId !== '' ? "SUB:role:{$roleId}:OR:{$orderId}:attempt:{$attempt}" : "SUB:role:{$roleId}:U:{$userId}:attempt:{$attempt}";

    // Call MP API
    try
    {
        $preapproval = new MercadoPago\Preapproval();

        $preapproval->notification_url = $notificationUrl;
        $preapproval->back_url  = $returnUrl;
        $preapproval->back_urls = [
            "success" => $returnUrl,
            "failure" => $returnUrl,
            "pending" => $returnUrl
        ];
        $preapproval->reason = $planName;
        $preapproval->external_reference = $externalReference;
        $preapproval->payer_email = $email;
        $preapproval->auto_recurring = [
            "frequency" => $ar['frequency'],
            "frequency_type" => $ar['frequency_type'],
            "transaction_amount" => round($amount, 2),
            "currency_id" => $currency
        ];

        if ($trialDays > 0)
        {
            $preapproval->auto_recurring["free_trial"] = [
                "frequency" => $trialDays,
                "frequency_type" => "days"
            ];
        }

        if (!empty($customerId)) {
            // $preapproval->payer = [
            //     "email" => $email,
            //     "id" => $customerId
            // ];
        }

        // If your SDK supports metadata on Preapproval entity, keep it.
        // If not, it will be ignored (safe).
        $preapproval->metadata = array_merge([
            "app" => $info['name'],
            "user_id" => $userId,
            "role_id" => $roleId
        ], $metadata);

        $preapproval->save();

        if ($debug) {
            echo "\n--- MP PREAPPROVAL ---\n";
            print_r($preapproval);
            echo "\n----------------------\n";
        }

        // ✅ IMPORTANT: return success/processing
        return [
            "code" => "processing",
            "msg"  => [
                "provider" => "mercadopago",
                "provider_subscription_id" => (string)($preapproval->id ?? ''),
                "provider_status" => (string)($preapproval->status ?? ''),
                "provider_init_point" => (string)($preapproval->init_point ?? $preapproval->sandbox_init_point ?? ''),
                "next_billing_at" => (string)($preapproval->next_payment_date ?? ''),
            ],
        ];
    }

    catch (\Throwable $e)
    {
        if ($debug) {
            print_r($e->getMessage());
        }

        return [
            "code" => "error",
            "msg"  => [
                "provider" => "mercadopago",
                "provider_type_code" => "sdk_exception",
                "raw_response_json" => [
                    "error_message" => $e->getMessage(),
                ],
            ],
        ];
    }
}

/**
 * Map your interval_unit/interval_count to Mercado Pago auto_recurring.
 * MP commonly uses frequency_type: 'days' or 'months'.
 *
 * @param string $unit day|week|month|year
 * @param int $count
 * @return array{frequency:int,frequency_type:string}
 */
function mercadopago_map_interval_to_auto_recurring(string $unit, int $count): array
{
    $unit  = strtolower(trim($unit));
    $count = max(1, (int)$count);

    switch ($unit) {
        case 'day':
        case 'days':
            return ['frequency' => $count, 'frequency_type' => 'days'];

        case 'week':
        case 'weeks':
            return ['frequency' => $count * 7, 'frequency_type' => 'days'];

        case 'month':
        case 'months':
            return ['frequency' => $count, 'frequency_type' => 'months'];

        case 'year':
        case 'years':
            // Annual -> months(12)
            return ['frequency' => $count * 12, 'frequency_type' => 'months'];

        default:
            // Safe default
            return ['frequency' => 1, 'frequency_type' => 'months'];
    }
}
