<?php
if(!isset($seg)) exit;

$body = "
<p>Olá, <strong>{$params['first_name']}</strong>,</p>
<p>Infelizmente não conseguimos aprovar o pagamento do seu pedido no valor de <strong>{$params['amount']}</strong>.</p>
<p>Você pode tentar novamente com outra forma de pagamento:</p>
<div class='align-itens-center'>
    <a class='btn' href='{$params['url']}'>Tentar novamente</a>
</div>
<p>Qualquer dúvida, estamos por aqui.</p>";

$email_data = [
    'to' => $payload['to'],
    'subject'   => '❌ Não conseguimos aprovar seu pagamento',
    'body'      => $body,
    'signature' => [
        'humanized' => false,
    ],
];
