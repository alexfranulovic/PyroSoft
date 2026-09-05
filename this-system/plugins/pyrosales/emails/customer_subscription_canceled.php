<?php
if(!isset($seg)) exit;

$body = "
<p>Olá, <strong>{$params['first_name']}</strong>,</p>
<p>Confirmamos o cancelamento da sua assinatura do plano <strong>{$params['plan_name']}</strong>. Você continuará com acesso até o fim do período já pago.</p>
<p>Mudou de ideia? É só acessar:</p>
<div class='align-itens-center'>
    <a class='btn' href='{$params['url']}'>Reativar assinatura</a>
</div>
<p>Sentiremos sua falta!</p>";

$email_data = [
    'to' => $payload['to'],
    'subject'   => '🚫 Sua assinatura foi cancelada',
    'body'      => $body,
    'signature' => [
        'humanized' => false,
    ],
];
