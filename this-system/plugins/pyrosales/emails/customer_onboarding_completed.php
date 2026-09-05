<?php
if(!isset($seg)) exit;

$body = "
<p>Olá, <strong>{$params['first_name']}</strong>,</p>
<p>Seu cadastro foi concluído com sucesso! Agora você já pode aproveitar tudo por aqui.</p>
<p>Para acessar, é só clicar abaixo:</p>
<div class='align-itens-center'>
    <a class='btn' href='{$params['url']}'>Acessar minha conta</a>
</div>
<p>Qualquer dúvida, estamos por aqui.</p>";

$email_data = [
    'to' => $payload['to'],
    'subject'   => '✅ Cadastro concluído com sucesso!',
    'body'      => $body,
    'signature' => [
        'humanized' => false,
    ],
];
