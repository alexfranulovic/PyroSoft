<?php
if (!isset($seg)) exit;

global $all_status;
global $subscription_status;

$subscription_status =
[
    [
        'id'    => 1,
        'slug'  => 'pending',
        'name'  => 'Pendente',
        'color' => 'subtle-warning',
    ],
    [
        'id'    => 2,
        'slug'  => 'active',
        'name'  => 'Ativa',
        'color' => 'subtle-success',
    ],
    [
        'id'    => 3,
        'slug'  => 'trialing',
        'name'  => 'Período gratis',
        'color' => 'subtle-success',
    ],
    [
        'id'    => 4,
        'slug'  => 'past_due',
        'name'  => 'Pagamento pendente',
        'color' => 'subtle-warning',
    ],
    [
        'id'    => 5,
        'slug'  => 'canceled',
        'name'  => 'Cancelada',
        'color' => 'subtle-danger',
    ],
    [
        'id'    => 6,
        'slug'  => 'expired',
        'name'  => 'Expirada',
        'color' => 'subtle-danger',
    ],
    [
        'id'    => 7,
        'slug'  => 'paused',
        'name'  => 'Pausada',
        'color' => 'subtle-info',
    ],
];

$all_status[] = [
    'function' => 'subscription_status',
    'name' => 'Subscription'
];

/**
 * Returns subscription status.
 *
 * @param bool $for_selects Indicates whether the output should be formatted for selects.
 * @return mixed|string|array The subscription status.
 */
function subscription_status(bool $for_selects = false, string $display = 'title')
{
    global $subscription_status;

    $res = $subscription_status;

    if ($for_selects == true)
    {
        $res = [];
        foreach($subscription_status as $stats)
        {
            $res[] = [
                'value' => $stats['id'],
                'display' => $stats[$display],
            ];
        }
    }

    return $res;
}
