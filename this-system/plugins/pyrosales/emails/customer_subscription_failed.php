<?php
if(!isset($seg)) exit;

$body = "
<p>Olá, <strong>{$params['first_name']}</strong>,</p>
<p>Não conseguimos processar o pagamento da renovação da sua assinatura do plano <strong>{$params['plan_name']}</strong>, no valor de <strong>{$params['amount']}</strong>.</p>
<p>Para evitar a interrupção do seu acesso, atualize sua forma de pagamento:</p>
<div class='align-itens-center'>
    <a class='btn' href='{$params['url']}'>Atualizar pagamento</a>
</div>
<p>Qualquer dúvida, estamos por aqui.</p>";

$email_data = [
    'to' => $payload['to'],
    'subject'   => '❌ Não conseguimos renovar sua assinatura',
    'body'      => $body,
    'signature' => [
        'humanized' => false,
    ],
];
