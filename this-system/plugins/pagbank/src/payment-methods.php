<?php
if (!isset($seg)) exit;

require_once __DIR__ .'/process-payment.php';
require_once __DIR__ .'/cancel-refund.php';

/**
 * Tokenize a card and persist it as a saved payment method (tb_user_payment_methods)
 * WITHOUT charging the customer -- this is PyroSales' gateway-agnostic contract for
 * "cadastrar/editar cartão" (see pyrosales/src/payment_methods.php::save_payment_method(),
 * which resolves the active gateway for the chosen method and calls
 * "{provider}_save_payment_method" by name, exactly like it already does for
 * "{provider}_cancel_refund").
 *
 * Why there's no separate "edit" function:
 * A saved card's PAN/token can never be mutated in place on PagBank (or on any
 * card gateway, for that matter) -- the only way to "change" a stored card is to
 * tokenize a brand new one and retire the old one. So "editar" and "cadastrar"
 * are the exact same gateway operation; PyroSales' orchestrator is the one that
 * decides whether to insert a new tb_user_payment_methods row or replace an
 * existing one, gated by this gateway's `editable_payment_method` flag
 * (see pagbank/index.php).
 *
 * How it avoids charging the customer:
 * It reuses pagbank_credit_card_process_payment()/pagbank_debit_card_process_payment()
 * with `capture: false` and a small fixed amount (PAGBANK_CARD_VERIFICATION_AMOUNT) --
 * the exact same authorize-then-void mechanic this plugin already uses for plan trial
 * validations. Both functions already call save_user_payment_method() as soon as
 * PagBank returns AUTHORIZED, then this function's `capture: false` makes them
 * immediately reverse/cancel that authorization via pagbank_cancel_refund() --
 * so the card ends up saved locally while the authorization itself is voided
 * right after, with no lasting charge.
 *
 * Expected $params:
 * [
 *   'method'       => 'credit_card' | 'debit_card',
 *   'user_id'      => int,
 *   'customer'     => ['name' => ..., 'email' => ..., 'phone' => ..., 'document_number' => ..., 'address' => [...] (optional, debit only)],
 *   'payment_data' => [ ...raw fields posted by pagbank_credit_card_fields()/pagbank_debit_card_fields() and
 *                        tokenized client-side by assets/scripts/credit_card.js: token/debit_token,
 *                        name/debit_name, threeds_id/debit_threeds_id, card_brand/debit_card_brand ],
 * ]
 *
 * @param array $params
 * @param bool  $debug
 * @return array ['code' => 'success', 'data' => ['user_payment_method_id' => int]] | ['code' => 'error', 'detail' => [...]]
 */
function pagbank_save_payment_method(array $params, bool $debug = false): array
{
    $method = strtolower(trim((string)($params['method'] ?? '')));

    if (!in_array($method, ['credit_card', 'debit_card'], true))
    {
        return [
            'code' => 'error',
            'detail' => [
                'provider' => 'pagbank',
                'code' => 'unsupported_method',
                'msg' => 'Só é possível salvar cartão de crédito ou débito por este gateway.',
            ],
        ];
    }

    $userId = (int)($params['user_id'] ?? 0);
    if ($userId <= 0)
    {
        return [
            'code' => 'error',
            'detail' => [
                'provider' => 'pagbank',
                'code' => 'missing_user',
                'msg' => 'Usuário inválido.',
            ],
        ];
    }

    $customer = (array)($params['customer'] ?? []);
    $email    = trim((string)($customer['email'] ?? ''));

    if ($email === '')
    {
        return [
            'code' => 'error',
            'detail' => [
                'provider' => 'pagbank',
                'code' => 'missing_customer_email',
                'msg' => 'Não foi possível identificar o e-mail do cliente.',
            ],
        ];
    }

    $process_payment_function = "pagbank_{$method}_process_payment";
    if (!function_exists($process_payment_function))
    {
        return [
            'code' => 'error',
            'detail' => [
                'provider' => 'pagbank',
                'code' => 'method_not_available',
                'msg' => 'Este método de pagamento não está disponível no momento.',
            ],
        ];
    }

    $payment_hash = hash('sha256', random_bytes(32));

    // Synthetic order/payment payload -- shaped exactly like what
    // create_order() builds for a real charge, just with no order row
    // behind it (id: 0) and capture forced off.
    $data = [
        'order' => [
            'id'                        => 0,
            'user_id'                   => $userId,
            'attempt'                   => 1,
            'order_purpose'             => 'payment_method_verification',
            'customer_name'             => trim((string)($customer['name'] ?? '')),
            'customer_email'            => $email,
            'customer_phone'            => (string)($customer['phone'] ?? ''),
            'customer_document_number'  => (string)($customer['document_number'] ?? ''),
            'address'                   => $customer['address'] ?? null,
        ],
        'payment_template' => [
            'amount'       => (float) PAGBANK_CARD_VERIFICATION_AMOUNT,
            'method'       => $method,
            'payment_hash' => $payment_hash,
        ],
        'payment_data' => (array)($params['payment_data'] ?? []),
        'items_lines'  => [[
            'item_name'  => 'Verificação de cartão',
            'title'      => 'Verificação de cartão',
            'quantity'   => 1,
            'unit_price' => (float) PAGBANK_CARD_VERIFICATION_AMOUNT,
        ]],
        'items_full' => [],
        'capture'    => false,
    ];

    $response = $process_payment_function($data, $debug);

    if (($response['code'] ?? 'error') === 'error')
    {
        $error_message = $response['error_messages'][0]
            ?? ($response['msg']['raw_response_json']['message'] ?? null)
            ?? 'Não foi possível validar o cartão. Verifique os dados e tente novamente.';

        return [
            'code' => 'error',
            'detail' => [
                'provider' => 'pagbank',
                'code' => $response['msg']['provider_type_code'] ?? 'card_verification_failed',
                'msg' => $error_message,
            ],
        ];
    }

    $user_payment_method_id = (int)($response['user_payment_method_id'] ?? 0);

    if ($user_payment_method_id <= 0)
    {
        // Should not happen once $response['code'] === 'success' (PagBank
        // returned AUTHORIZED/PAID and save_user_payment_method() ran) --
        // guarded anyway since the caller persists nothing without this id.
        return [
            'code' => 'error',
            'detail' => [
                'provider' => 'pagbank',
                'code' => 'card_not_saved',
                'msg' => 'O cartão foi validado, mas não foi possível salvá-lo. Tente novamente.',
            ],
        ];
    }

    return [
        'code' => 'success',
        'data' => [
            'user_payment_method_id' => $user_payment_method_id,
        ],
    ];
}
