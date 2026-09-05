<?php
if(!isset($seg)) exit;

$body = "
<p>Olá, <strong>{$params['first_name']}</strong>,</p>
<p>Que bom ter você por aqui! Sua conta já está pronta para uso.</p>
<p>Para começar, é só acessar:</p>
<div class='align-itens-center'>
    <a class='btn' href='{$params['url']}'>Acessar minha conta</a>
</div>
<p>Qualquer dúvida, estamos por aqui.</p>";

$email_data = [
    'to' => $payload['to'],
    'subject'   => '👋 Seja bem-vindo(a)!',
    'body'      => $body,
    'signature' => [
        'humanized' => false,
    ],
];
