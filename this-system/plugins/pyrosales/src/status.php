<?php
if (!isset($seg)) exit;

global $all_status;
global $order_status;
global $payment_status;

global $order_status;

$order_status =
[
    [
        'id'    => 1,
        'name'  => icon('fas fa-hourglass-half') . " Aguardando pagamento",
        'slug'  => 'pending',
        'color' => 'subtle-warning',
    ],
    [
        'id'    => 2,
        'name'  => icon('fas fa-spinner').' Processando',
        'slug'  => 'processing',
        'color' => 'subtle-success',
    ],
    [
        'id'    => 3,
        'name'  => icon('fas fa-check-circle') . " Pago",
        'slug'  => 'paid',
        'color' => 'subtle-success',
    ],
    [
        'id'    => 4,
        'name'  => icon('fas fa-ban') . " Cancelado",
        'slug'  => 'cancelled',
        'color' => 'subtle-danger',
    ],
    [
        'id'    => 5,
        'name'  => icon('fas fa-times-circle') . " Falhou",
        'slug'  => 'failed',
        'color' => 'subtle-danger',
    ],
    [
        'id'    => 6,
        'name'  => icon('fas fa-undo') . " Reembolsado",
        'slug'  => 'refunded',
        'color' => 'subtle-primary',
    ],
    [
        'id'    => 7,
        'name'  => icon('fas fa-exclamation-triangle') . " Chargeback",
        'slug'  => 'chargeback',
        'color' => 'subtle-danger',
    ],
    [
        'id'    => 8,
        'name'  => icon('fas fa-shield-alt') . " Em análise antifraude",
        'slug'  => 'fraud_review',
        'color' => 'subtle-warning',
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
function order_status(bool $for_selects = false)
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
                'display' => $stats['name'],
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
        'slug'  => 'refunded',
        'color' => 'subtle-info',
    ],
    [
        'id'    => 5,
        'name'  => 'Reembolso Parcial',
        'slug'  => 'partial_refund',
        'color' => 'subtle-info',
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

