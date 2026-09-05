<?php
if(!isset($seg)) exit;

$body = "
<p>Prezado(a) <strong>{$params['first_name']}</strong>,</p>
<p>Para continuar o processo de recuperação de sua senha, clique no botão abaixo ou cole o endereço abaixo no seu navegador:</p>
<p>Seguindo o link abaixo você poderá alterar sua senha:</p>
<div class='align-itens-center'>
    <a class='btn' href='{$params['url']}'>Recuperar senha agora</a>
</div>
<p>Se você não solicitou essa alteração, nenhuma ação é necessária. Sua senha permanecerá a mesma até que você ative este código e recupere a senha.</p>
<p>Atenciosamente,</p>";

$email_data = [
    'to' => $payload['to'],
    'subject'   => 'Recuperação de senha',
    'body'      => $body,
    'signature' => [
        'humanized' => false,
    ],
];
