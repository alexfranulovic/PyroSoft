<?php
if(!isset($seg)) exit;

$body = "
<p>Olá, <strong>{$params['first_name']}</strong>,</p>
<p>Uma venda no valor de <strong>{$params['amount']}</strong> foi confirmada e sua comissão de <strong>{$params['commission_amount']}</strong> já está garantida.</p>
<p>Cliente: <strong>{$params['customer_name']}</strong></p>
<p>Para mais detalhes, é só acessar:</p>
<div class='align-itens-center'>
    <a class='btn' href='{$params['url']}'>Ver pedido</a>
</div>
<p>Atenciosamente,</p>";

$email_data = [
    'to' => $payload['to'],
    'subject'   => '💰 Nova venda confirmada!',
    'body'      => $body,
    'signature' => [
        'humanized' => false,
    ],
];
