<?php
if(!isset($seg)) exit;

$body = "
<p>Olá, <strong>{$params['first_name']}</strong>,</p>
<p>Sua assinatura do plano <strong>{$params['plan_name']}</strong> expirou e seu acesso foi encerrado.</p>
<p>Para reativar quando quiser, é só acessar:</p>
<div class='align-itens-center'>
    <a class='btn' href='{$params['url']}'>Reativar assinatura</a>
</div>
<p>Esperamos você de volta!</p>";

$email_data = [
    'to' => $payload['to'],
    'subject'   => '⌛ Sua assinatura expirou',
    'body'      => $body,
    'signature' => [
        'humanized' => false,
    ],
];
