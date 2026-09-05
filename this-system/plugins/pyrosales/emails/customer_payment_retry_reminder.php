<?php
if(!isset($seg)) exit;

$body = "
<p>Olá, <strong>{$params['first_name']}</strong>,</p>
<p>Notamos que o pagamento do seu pedido no valor de <strong>{$params['amount']}</strong> ainda não foi concluído.</p>
<p>Para finalizar agora, é só acessar:</p>
<div class='align-itens-center'>
    <a class='btn' href='{$params['url']}'>Concluir pagamento</a>
</div>
<p>Qualquer dúvida, estamos por aqui.</p>";

$email_data = [
    'to' => $payload['to'],
    'subject'   => '💳 Ainda dá tempo de concluir seu pagamento',
    'body'      => $body,
    'signature' => [
        'humanized' => false,
    ],
];
