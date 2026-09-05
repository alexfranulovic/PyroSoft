<?php
if (!isset($seg)) exit;

require_once __DIR__ .'/status.php';

/**
 * Reverse a PagBank charge.
 *
 * Supported flows:
 * - AUTHORIZED + empty amount => cancel authorized charge
 * - PAID + empty amount       => full refund
 * - PAID + amount             => partial refund
 *
 * Expected params:
 * $params = [
 *     'charge_id' => 'CHARGE_ID',
 *     'status'    => 'AUTHORIZED|PAID|CANCELED|CANCELLED',
 *     'amount'    => 19.90, // optional, in BRL
 * ];
 *
 * @param array $params Charge reversal parameters.
 * @param bool  $debug  Print debug information when true.
 *
 * @return array
 */
function pagbank_cancel_refund(array $params, bool $debug = false): array
{
    $charge_id         = trim((string)($params['provider_payment_id'] ?? ''));
    $status_id         = $params['status_id'] ?? null;

    // Total payment amount
    $total_amount     = isset($params['total_amount']) && $params['total_amount'] !== '' ? (float)$params['total_amount'] : 0;
    $total_amount     = $total_amount * 100;

    // Already refunded payment amount
    $refunded_amount    = isset($params['refunded_amount']) && $params['refunded_amount'] !== '' ? (float)$params['refunded_amount'] : 0;
    $refunded_amount    = $refunded_amount * 100;

    // How much you want to refund in this request
    $to_refund_amount  = isset($params['to_refund_amount']) && $params['to_refund_amount'] !== '' ? (float)$params['to_refund_amount'] : 0;
    $to_refund_amount  = $to_refund_amount * 100;

    if ($charge_id === '')
    {
        return [
            'code' => 'error',
            'detail'  => [
                'msg' => 'Charge ID is required.',
                'provider' => 'pagbank',
                'code' => 'invalid_charge_id',
            ],
        ];
    }

    if ($status_id === '')
    {
        return [
            'code' => 'error',
            'detail'  => [
                'msg' => 'Charge status_id is required.',
                'provider' => 'pagbank',
                'code' => 'missing_charge_status_id',
            ],
        ];
    }

    // if ($total_amount !== null && $total_amount <= 0)
    // {
    //     return [
    //         'code' => 'error',
    //         'msg'  => [
    //             'msg' => => 'Total amount must be greater than zero.',
    //             'provider' => 'pagbank',
    //             'provider_type_code' => 'invalid_amount',
    //         ],
    //     ];
    // }

    // if ($to_refund_amount > 0) {
    //     $total_amount = $to_refund_amount;
    // }

    $flow = null;
    $payload = [];

    if (!is_string($status_id))
    {
        $status_id = pagbank_payment_status_id([
            'status'  => $status_id,
            'reverse' => true,
        ]);
    }

    switch ($status_id)
    {
        case 'AUTHORIZED':
            $payload['amount'] = [
                'value' => (int) round($to_refund_amount),
            ];

            $flow = 'cancel_authorized';
            break;

        case 'PAID':
        case 'FAILED':
        case 'PARTIALLY_REFUNDED':
        case 'REFUNDED':
            // $flow = (($total_amount === $to_refund_amount) OR ($total_amount === ($refunded_amount+$to_refund_amount)))
            //     ? 'full_refunded'
            //     : 'partial_refund';

            $flow = (($total_amount === $to_refund_amount) OR ($total_amount === ($refunded_amount)))
                ? 'full_refunded'
                : 'partial_refund';


            $payload['amount'] = [
                'value' => (int) round($to_refund_amount),
            ];
            break;

        case 'CANCELED':
        case 'CANCELLED':
            return [
                'code' => 'error',
                    'msg' => "Charge is already canceled. **Flow: {$status_id}**",
                'detail'  => [
                    'provider' => 'pagbank',
                    'code' => 'charge_already_canceled',
                ],
            ];

        default:
            return [
                'code' => 'error',
                'detail'  => [
                    'msg' => "This charge status_id does not support reversal in this flow. **Flow: {$status_id}**",
                    'provider' => 'pagbank',
                    'code' => 'unsupported_charge_status_id',
                ],
            ];
    }


    $max_attempts      = PAGBANK_CANCEL_MAX_ATTEMPTS;
    $raw_response_json = null;
    $last_error        = null;
    for ($attempt = 1; $attempt <= $max_attempts; $attempt++)
    {
        try
        {
            $raw_response_json = pagbank_request("/charges/{$charge_id}/cancel", 'POST', $payload, [], $debug);
            $response = (array)($raw_response_json['response'] ?? []);

            // Success, exit the loop
            if (!empty($response['error_messages'])) {
                continue;
            }

            $last_error = null;
            break;
        }
        catch (\Throwable $e)
        {
            $last_error = $e;

            if ($debug) {
                echo "Attempt {$attempt} failed: " . $e->getMessage() . PHP_EOL;
            }
        }
    }

    // Both attempts failed, return error
    if ($last_error !== null)
    {
        return [
            'code' => 'error',
            'detail'  => [
                'msg' => $last_error->getMessage(),
                'provider' => 'pagbank',
                'code' => 'request_exception',
            ],
        ];
    }

    if ($debug)
    {
        dump([
            'flow'      => $flow,
            'charge_id' => $charge_id,
            'status_id' => $status_id,
            'payload'   => $payload,
        ]);
    }

    $response_status = strtoupper((string)($response['status'] ?? ''));
    $amount_response = (array)($response['amount'] ?? []);
    $amount_value    = isset($amount_response['value'])
        ? ((float)$amount_response['value'] / 100)
        : null;
    $refunded_amount = isset($amount_response['summary']['refunded'])
        ? ((float)$amount_response['summary']['refunded'] / 100)
        : null;

    $res = [
        'code' => !empty($response_status) ? 'success' : 'error',
        'data'  => [
            'attempts'            => $attempt,
            'provider'            => 'pagbank',
            'provider_payment_id' => (string)($response['id'] ?? $charge_id),
            'provider_type_code'  => $response_status ?: $flow,
            'raw_response_json'   => $response,
        ],
    ];

    // Successful return
    if (!empty($response_status))
    {
        $res['data']+= [
            'flow'                => $flow,
            'original_status'     => $status_id,
            'new_status'          => $response_status,
            'amount'              => (float) $amount_value,
            'refunded_amount'     => (float) $refunded_amount,
        ];
    }

    // Unsuccessful return (translate gateway error to CMS).
    else
    {
        $error_messages = $response['error_messages'] ?? [];

        $res['detail'] = [
            'msg' => $error_messages[0]['description'] ?? null,
            'code' => $error_messages[0]['error'] ?? null,
        ];
    }

    return $res ?? [];
}
