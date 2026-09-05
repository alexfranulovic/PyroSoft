<?php
if(!isset($seg)) exit;

$body = "
<p>Olá, <strong>{$params['first_name']}</strong>,</p>
<p>Sua assinatura do plano <strong>{$params['plan_name']}</strong> será renovada automaticamente em <strong>{$params['renewal_date']}</strong>, no valor de <strong>{$params['amount']}</strong>.</p>
<p>Para revisar os detalhes, é só acessar:</p>
<div class='align-itens-center'>
    <a class='btn' href='{$params['url']}'>Ver assinatura</a>
</div>
<p>Atenciosamente,</p>";

$email_data = [
    'to' => $payload['to'],
    'subject'   => '🔔 Sua assinatura será renovada em breve',
    'body'      => $body,
    'signature' => [
        'humanized' => false,
    ],
];
