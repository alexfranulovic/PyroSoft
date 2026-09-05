<?php
if(!isset($seg)) exit;

$body = "
<p>Olá, <strong>{$params['first_name']}</strong>,</p>
<p>Sua assinatura do plano <strong>{$params['plan_name']}</strong> foi renovada com sucesso, no valor de <strong>{$params['amount']}</strong>. A próxima renovação está prevista para <strong>{$params['next_renewal_date']}</strong>.</p>
<p>Para ver os detalhes, é só acessar:</p>
<div class='align-itens-center'>
    <a class='btn' href='{$params['url']}'>Ver assinatura</a>
</div>
<p>Obrigado pela confiança!</p>";

$email_data = [
    'to' => $payload['to'],
    'subject'   => '🔁 Sua assinatura foi renovada',
    'body'      => $body,
    'signature' => [
        'humanized' => false,
    ],
];
