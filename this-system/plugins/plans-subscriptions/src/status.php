<?php
if (!isset($seg)) exit;

global $all_status;
global $subscription_status;

$subscription_status =
[
    [
        'id'    => 1,
        'slug'  => 'pending',
        'title'  => 'Pendente',
        'color' => 'subtle-warning',
    ],
    [
        'id'    => 2,
        'slug'  => 'active',
        'title'  => 'Ativa',
        'color' => 'subtle-success',
    ],
    [
        'id'    => 3,
        'slug'  => 'trialing',
        'title'  => 'Período gratis',
        'color' => 'subtle-success',
    ],
    [
        'id'    => 4,
        'slug'  => 'past_due',
        'title'  => 'Pagamento pendente',
        'color' => 'subtle-warning',
    ],
    [
        'id'    => 5,
        'slug'  => 'canceled',
        'title'  => 'Cancelada',
        'color' => 'subtle-danger',
    ],
    [
        'id'    => 6,
        'slug'  => 'expired',
        'title'  => 'Expirada',
        'color' => 'subtle-danger',
    ],
    [
        'id'    => 7,
        'slug'  => 'paused',
        'title'  => 'Pausada',
        'color' => 'subtle-info',
    ],
];

$all_status[] = [
    'function' => 'subscription_status',
    'name' => 'Subscription'
];
