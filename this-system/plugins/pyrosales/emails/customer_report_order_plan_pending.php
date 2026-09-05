<?php
if(!isset($seg)) exit;

$body = "
<p>Olá, <strong>{$params['first_name']}</strong>,</p>
<p>Seu pedido no valor de <strong>{$params['amount']}</strong> foi criado e está aguardando a confirmação do pagamento via <strong>{$params['payment_method']}</strong>.</p>
<p>Para finalizar, é só acessar:</p>
<div class='align-itens-center'>
    <a class='btn' href='{$params['url']}'>Concluir pagamento</a>
</div>
<p>Qualquer dúvida, estamos por aqui.</p>";

$email_data = [
    'to' => $payload['to'],
    'subject'   => '⏳ Estamos aguardando seu pagamento',
    'body'      => $body,
    'signature' => [
        'humanized' => false,
    ],
];
