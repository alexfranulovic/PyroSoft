<?php
if(!isset($seg)) exit;

$body = "
<p>Olá, <strong>{$params['first_name']}</strong>,</p>
<p>Recebemos a confirmação do seu pagamento no valor de <strong>{$params['amount']}</strong>. Seu pedido já está sendo processado.</p>
<p>Para ver os detalhes do seu pedido, é só acessar:</p>
<div class='align-itens-center'>
    <a class='btn' href='{$params['url']}'>Ver pedido</a>
</div>
<p>Obrigado pela confiança!</p>";

$email_data = [
    'to' => $payload['to'],
    'subject'   => '✅ Seu pagamento foi confirmado!',
    'body'      => $body,
    'signature' => [
        'humanized' => false,
    ],
];
