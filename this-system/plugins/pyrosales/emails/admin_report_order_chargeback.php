<?php
if(!isset($seg)) exit;

$body = "
<p>Olá, <strong>{$params['first_name']}</strong>,</p>
<p>O pedido de <strong>{$params['customer_name']}</strong>, no valor de <strong>{$params['amount']}</strong>, recebeu uma contestação (chargeback) junto à operadora de pagamento.</p>
<p>Para mais detalhes, é só acessar:</p>
<div class='align-itens-center'>
    <a class='btn' href='{$params['url']}'>Ver pedido</a>
</div>
<p>Atenciosamente,</p>";

$email_data = [
    'to' => $payload['to'],
    'subject'   => '⚠️ Chargeback recebido',
    'body'      => $body,
    'signature' => [
        'humanized' => false,
    ],
];
