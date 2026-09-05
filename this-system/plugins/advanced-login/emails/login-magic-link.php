<?php
if(!isset($seg)) exit;

/**
 * E-mail do magic link (SSO por e-mail) - plugin advanced-login.
 * Recebe $params (template_params): first_name, url, minutes.
 */
$first_name = trim((string) ($params['first_name'] ?? ''));
$first_name   = $first_name !== '' ? " <strong>{$first_name}</strong>" : '';

$body = "
<p>Prezado(a),{$first_name},</p>
<p>Recebemos um pedido de acesso a sua conta. Clique no botão abaixo para entrar:</p>
<div class='align-itens-center'>
    <a class='btn' href='{$params['url']}'>Entrar agora</a>
</div>
<p class='text-center'>Ou cole este endereço no seu navegador:<br><span>{$params['url']}</span></p>
<p>Este link expira em {$params['minutes']} minutos e só pode ser usado uma vez.</p>
<p>Se você não solicitou este acesso, nenhuma ação é necessária - basta ignorar este e-mail.</p>";

$email_data = [
    'to'      => $payload['to'] ?? null,
    'subject' => '🔑 Seu link de acesso',
    'body'    => $body,
    'signature' => [
        'humanized' => false,
    ],
];
