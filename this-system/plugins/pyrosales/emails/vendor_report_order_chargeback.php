<?php
if(!isset($seg)) exit;

$body = "
<p>Olá, <strong>{$params['first_name']}</strong>,</p>
<p>O pedido de <strong>{$params['customer_name']}</strong>, no valor de <strong>{$params['amount']}</strong>, sofreu uma contestação (chargeback) junto à operadora de pagamento. Isso pode impactar a comissão referente a este pedido.</p>
<p>Para mais detalhes, é só acessar:</p>
<div class='align-itens-center'>
    <a class='btn' href='{$params['url']}'>Ver pedido</a>
</div>
<p>Atenciosamente,</p>";

$email_data = [
    'to' => $payload['to'],
    'subject'   => '⚠️ Chargeback em um pedido seu',
    'body'      => $body,
    'signature' => [
        'humanized' => false,
    ],
];
