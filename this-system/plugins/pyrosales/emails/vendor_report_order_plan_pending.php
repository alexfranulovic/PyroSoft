<?php
if(!isset($seg)) exit;

$body = "
<p>Olá, <strong>{$params['first_name']}</strong>,</p>
<p>O cliente <strong>{$params['customer_name']}</strong> fez um pedido no valor de <strong>{$params['amount']}</strong> e está aguardando a confirmação do pagamento. Assim que for aprovado, sua comissão será liberada.</p>
<p>Para mais detalhes, é só acessar:</p>
<div class='align-itens-center'>
    <a class='btn' href='{$params['url']}'>Ver pedido</a>
</div>
<p>Atenciosamente,</p>";

$email_data = [
    'to' => $payload['to'],
    'subject'   => '⏳ Pedido aguardando pagamento',
    'body'      => $body,
    'signature' => [
        'humanized' => false,
    ],
];
