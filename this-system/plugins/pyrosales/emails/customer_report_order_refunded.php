<?php
if(!isset($seg)) exit;

$body = "
<p>Olá, <strong>{$params['first_name']}</strong>,</p>
<p>O valor de <strong>{$params['amount']}</strong> referente ao seu pedido foi estornado. O prazo para o valor aparecer no seu extrato pode variar de acordo com a sua forma de pagamento.</p>
<p>Para mais detalhes, é só acessar:</p>
<div class='align-itens-center'>
    <a class='btn' href='{$params['url']}'>Ver pedido</a>
</div>
<p>Qualquer dúvida, estamos por aqui.</p>";

$email_data = [
    'to' => $payload['to'],
    'subject'   => '🔁 Seu estorno foi processado',
    'body'      => $body,
    'signature' => [
        'humanized' => false,
    ],
];
