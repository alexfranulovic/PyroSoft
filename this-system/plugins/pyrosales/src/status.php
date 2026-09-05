<?php
if (!isset($seg)) exit;

global $all_status;
global $payment_status;
global $order_status;
global $commission_status;

$order_status =
[
    [
        'id'    => 1,
        'name' => icon('fas fa-hourglass-half') . " Aguardando pagamento",
        'title'  => "Aguardando pagamento",
        'slug'  => 'pending',
        'color' => 'subtle-warning',
    ],
    [
        'id'    => 2,
        'name' => icon('fas fa-spinner').' Processando',
        'title'  => 'Processando',
        'slug'  => 'processing',
        'color' => 'subtle-warning',
    ],
    [
        'id'    => 3,
        'name' => icon('fas fa-check-circle') . " Pago",
        'title'  => "Pago",
        'slug'  => 'paid',
        'color' => 'subtle-success',
    ],
    [
        'id'    => 4,
        'name' => icon('fas fa-ban') . " Cancelado",
        'title'  => "Cancelado",
        'slug'  => 'canceled',
        'color' => 'subtle-danger',
    ],
    [
        'id'    => 5,
        'name' => icon('fas fa-times-circle') . " Falhou",
        'title'  => "Falhou",
        'slug'  => 'failed',
        'color' => 'subtle-danger',
    ],
    [
        'id'    => 6,
        'name' => icon('fas fa-undo') . " Reembolsado",
        'title'  => "Reembolsado",
        'slug'  => 'refunded',
        'color' => 'subtle-dark',
    ],
    [
        'id'    => 7,
        'name' => icon('fas fa-exclamation-triangle') . " Chargeback",
        'title'  => "Chargeback",
        'slug'  => 'chargeback',
        'color' => 'subtle-danger',
    ],
    [
        'id'    => 8,
        'name' => icon('fas fa-exclamation-triangle') . " Em risco",
        'title'  => "Em risco",
        'slug'  => 'at_risk',
        'color' => 'subtle-danger',
    ],
    [
        'id'    => 9,
        'name' => icon('fas fa-file') . " Rascunho",
        'title'  => "Rascunho",
        'slug'  => 'draft',
        'color' => 'subtle-dark',
    ],
];



$all_status[] = [
    'function' => 'order_status',
    'name' => 'Order'
];

/**
 * Returns order status.
 *
 * @param bool $for_selects Indicates whether the output should be formatted for selects.
 * @return mixed|string|array The order status.
 */
function order_status(bool $for_selects = false, string $display = 'name')
{
    global $order_status;

    $res = $order_status;

    if ($for_selects == true)
    {
        $res = [];
        foreach($order_status as $stats)
        {
            $res[] = [
                'value' => $stats['id'],
                'display' => $stats[$display],
            ];
        }
    }

    return $res;
}

$payment_status =
[
    [
        'id'    => 1,
        'name'  => 'Pendente',
        'slug'  => 'pending',
        'color' => 'subtle-warning',
    ],
    [
        'id'    => 2,
        'name'  => 'Pago',
        'slug'  => 'paid',
        'color' => 'subtle-success',
    ],
    [
        'id'    => 3,
        'name'  => 'Falhou',
        'slug'  => 'failed',
        'color' => 'subtle-danger',
    ],
    [
        'id'    => 4,
        'name'  => 'Reembolsado',
        'slug'  => 'full_refunded',
        'color' => 'subtle-info',
    ],
    [
        'id'    => 5,
        'name'  => 'Cancelada',
        'slug'  => 'canceled',
        'color' => 'subtle-danger',
    ],
    [
        'id'    => 6,
        'name'  => 'Reembolso Parcial',
        'slug'  => 'partial_refund',
        'color' => 'subtle-info',
    ],
    [
        'id'    => 7,
        'name'  => 'Autorizado',
        'slug'  => 'authorized',
        'color' => 'subtle-success',
    ],
    [
        'id'    => 8,
        'name'  => 'Chargeback',
        'slug'  => 'chargeback',
        'color' => 'subtle-danger',
    ],
    [
        'id'    => 9,
        'name'  => "Em análise antifraude",
        'slug'  => 'fraud_review',
        'color' => 'subtle-warning',
    ],
    [
        'id'    => 10,
        'name'  => 'Em disputa',
        'slug'  => 'in_dispute',
        'color' => 'subtle-danger',
    ],
];

$all_status[] = [
    'function' => 'payment_status',
    'name' => 'Payment'
];

/**
 * Returns order status.
 *
 * @param bool $for_selects Indicates whether the output should be formatted for selects.
 * @return mixed|string|array The order status.
 */
function payment_status(bool $for_selects = false)
{
    global $payment_status;

    $res = $payment_status;

    if ($for_selects == true)
    {
        $res = [];
        foreach($payment_status as $stats)
        {
            $res[] = [
                'value' => $stats['id'],
                'display' => $stats['name'],
            ];
        }
    }

    return $res;
}

/**
 * VENDOR COMMISSION STATUS
 *
 * Lifecycle of a vendor's commission on a single order -- deliberately
 * tiny, it mirrors the tb_orders.commission_status_id ENUM 1:1:
 *
 *   pending  -> nothing released yet: no commission_activation_function
 *               frozen on the order, it doesn't exist, it threw, or it
 *               reported failure.
 *   complete -> the order's commission_activation_function ran and
 *               acknowledged the payout.
 *
 * Same array shape + $all_status threading as $order_status /
 * $payment_status above, so general_stats(), status_buttons() and
 * all_status() keep working with it for free.
 */
$commission_status =
[
    [
        'id'    => 1,
        'name'  => icon('fas fa-hourglass-half') . " Comissão pendente",
        'title' => "Pendente",
        'slug'  => 'pending',
        'color' => 'subtle-warning',
    ],
    [
        'id'    => 2,
        'name'  => icon('fas fa-check-circle') . " Comissão liberada",
        'title' => "Concluída",
        'slug'  => 'complete',
        'color' => 'subtle-success',
    ],
];

$all_status[] = [
    'function' => 'commission_status',
    'name' => 'Vendor'
];

/**
 * Returns vendor commission status.
 *
 * @param bool   $for_selects Indicates whether the output should be formatted for selects.
 * @param string $display     Which status key to use as the option label.
 * @return array The vendor commission status.
 */
function commission_status(bool $for_selects = false, string $display = 'name')
{
    global $commission_status;

    $res = $commission_status;

    if ($for_selects == true)
    {
        $res = [];
        foreach($commission_status as $stats)
        {
            $res[] = [
                'value' => $stats['id'],
                'display' => $stats[$display],
            ];
        }
    }

    return $res;
}

/**
 * Match payment status to order status.
 *
 * This function converts a payment status into the appropriate order status,
 * considering the payment purpose. This is important because the same payment
 * status may represent different business outcomes depending on the scenario.
 *
 * Supported purposes:
 * - charge
 * - trial_validation
 *
 * Accepted payment status values:
 * - numeric status ID
 * - status slug
 *
 * @param array $params {
 *     @type int|string $payment_status Payment status ID or slug.
 *     @type string     $purpose        Payment purpose. Defaults to 'charge'.
 * }
 *
 * @return int|null Order status ID or null when no match is found.
 */
function payment_to_order_status(array $params): ?int
{
    $payment_slug   = null;
    $payment_status = $params['payment_status'] ?? null;
    $purpose        = trim((string)($params['purpose'] ?? 'charge'));

    $purpose = ($purpose != 'trial_validation')
        ? 'charge'
        : 'trial_validation';

    if (is_numeric($payment_status)) {
        $payment_slug = get_status_slug_by_id('payment_status', (int)$payment_status);
    }

    elseif (is_string($payment_status) && $payment_status !== '') {
        $payment_slug = trim(strtolower($payment_status));
    }

    if (empty($payment_slug)) {
        return 5;
    }

        // print_r($params);
        // print_r($payment_slug);

    $map = [
        'charge' => [
            'pending'        => 1, // pending
            'authorized'     => 2, // processing
            'fraud_review'   => 2, // processing
            'paid'           => 3, // paid
            'canceled'       => 4, // canceled
            'failed'         => 5, // failed
            'full_refunded'  => 6, // refunded
            'partial_refund' => 3, // paid
            // 'partial_refund' => 6, // refunded
            'chargeback'     => 7, // chargeback
            'in_dispute'     => 8,
        ],

        'trial_validation' => [
            'pending'        => 1, // pending
            'authorized'     => 3, // paid
            'fraud_review'   => 3, // paid
            'paid'           => 3, // paid
            'canceled'       => 3, // paid (successful trial card validation flow)
            'failed'         => 5, // failed
            'full_refunded'  => 3, // paid
            'partial_refund' => 3, // paid
            'chargeback'     => 7, // chargeback
            'in_dispute'     => 8,
        ],
    ];

    if (empty($map[$purpose][$payment_slug])) {
        return 5;
    }

    return (int)$map[$purpose][$payment_slug];
}

/**
 * Get a status slug by its ID from a status provider function.
 *
 * Example providers:
 * - order_status
 * - payment_status
 *
 * @param string $function_name Status provider function name.
 * @param int    $id            Status ID.
 *
 * @return string|null
 */
function get_status_slug_by_id(string $function_name, int $id): ?string
{
    if (!function_exists($function_name)) {
        return null;
    }

    $all_status = call_user_func($function_name);

    foreach ($all_status as $status)
    {
        if ((int)($status['id'] ?? 0) === $id) {
            return (string)($status['slug'] ?? null);
        }
    }

    return 3;
}

/**
 * Resolve -- and persist -- an order's vendor commission status.
 *
 * Each order can freeze a `commission_activation_function` on tb_orders:
 * a callable reference in the SAME notation function_process()
 * (ep-includes/core/treatment-functions.php) already understands for
 * tb_order_items.activation_function -- a bare "fn", "fn({field})",
 * "fn({all})", etc. It is the single hook a marketplace / vendor
 * integration uses to actually release the commission (write a payout
 * row, credit a wallet, ping an external ledger...).
 *
 * Outcome, written straight to tb_orders.commission_status_id:
 *   - blank / missing / unknown callable / throws / returns a falsy
 *     value or ['code' => 'error']        => stays  'pending'
 *   - ran and returned anything truthy     => becomes 'complete'
 *
 * Idempotent: an order already 'complete' is never re-run, and an order
 * with no vendor_id is left 'pending' without calling anything. A real
 * transition is logged as an order note (same pattern as the order /
 * payment status changes elsewhere).
 *
 * @param array $order   Order row. Needs at least: id (or order_id),
 *                        vendor_id, commission_activation_function,
 *                        commission_status_id.
 * @param array $context Extra data merged into the function_process() payload.
 * @param bool  $debug
 * @return string Resulting slug: 'pending' | 'complete'.
 */
function resolve_order_commission_status(array $order, array $context = [], bool $debug = false): string
{
    $order_id = (int) ($order['id'] ?? $order['order_id'] ?? 0);
    $current  = ((string) ($order['commission_status_id'] ?? '1')) === '2'
        ? '2'
        : '1';

    // Nothing to settle, or already settled.
    if ($current === 'complete' || empty($order['vendor_id'])) {
        return $current;
    }

    $fn_ref  = trim((string) ($order['commission_activation_function'] ?? ''));
    // Base callable name, stripped of any "(...)" arg notation, so
    // "configured but undefined" is told apart from "ran and failed".
    $base_fn = $fn_ref !== '' ? trim(preg_replace('/\s*\(.*$/s', '', $fn_ref)) : '';

    $new_status = '1';

    if ($base_fn !== '' && function_exists($base_fn))
    {
        try {
            $result = function_process(
                $fn_ref,
                'commission_activation_function',
                array_merge($context, [
                    'order'                          => array_merge($order, ['id' => $order_id]),
                    'commission_activation_function' => $fn_ref,
                ])
            );

            // "didn't execute" == returned nothing usable / signalled failure.
            $failed = $result === false
                || $result === null
                || (is_array($result) && ($result['code'] ?? null) === 'error');

            if (!$failed) {
                $new_status = 'complete';
            }
        }
        catch (\Throwable $e)
        {
            if (function_exists('app_log'))
            {
                app_log('error', 'Commission activation function failed', [
                    'file_name' => 'payments.log',
                    'origin'    => 'commission',
                    'order_id'  => $order_id,
                    'function'  => $fn_ref,
                    'error'     => $e->getMessage(),
                ]);
            }
        }
    }

    if ($order_id > 0 && $new_status !== $current)
    {
        update('tb_orders', [
            'data'  => [
                'commission_status_id' => $new_status,
                'updated_at'          => 'NOW()',
            ],
            'where' => where_equal_id($order_id),
        ], false, $debug);

        if (function_exists('add_order_note'))
        {
            $from = general_stats($current, 'commission_status', 'title');
            $to   = general_stats($new_status, 'commission_status', 'title');

            add_order_note([
                'order_id'  => $order_id,
                'title'     => 'Commission status updated',
                'content'   => "Vendor commission status changed from **\"{$from}\"** to **\"{$to}\"**.",
                'note_type' => 'status_change',
                'body_type' => 'alert',
            ]);
        }
    }

    return $new_status;
}
