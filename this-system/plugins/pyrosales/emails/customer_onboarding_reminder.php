<?php
if(!isset($seg)) exit;

$body = "
<p>Olá, <strong>{$params['first_name']}</strong>,</p>
<p>Notamos que seu cadastro ainda não foi concluído. Termine agora para aproveitar tudo o que preparamos para você.</p>
<p>Para concluir, é só acessar:</p>
<div class='align-itens-center'>
    <a class='btn' href='{$params['url']}'>Concluir cadastro</a>
</div>
<p>Qualquer dúvida, estamos por aqui.</p>";

$email_data = [
    'to' => $payload['to'],
    'subject'   => '⏰ Falta pouco para concluir seu cadastro',
    'body'      => $body,
    'signature' => [
        'humanized' => false,
    ],
];
