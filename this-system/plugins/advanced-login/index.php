<?php
if (!isset($seg)) exit;

/**
 * Advanced login - bootstrap.
 *
 * Carregado por load_plugins('index') em load.php, ANTES do roteamento e do
 * controle de acesso - por isso o auto-login do "manter conectado" mora aqui.
 */
require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/providers/google.php';   // 1 require por provedor implementado
require_once __DIR__ . '/src/email-sso.php';
require_once __DIR__ . '/src/social-login.php';
require_once __DIR__ . '/src/remember-me.php';
require_once __DIR__ . '/src/password-login.php';

$GLOBALS['alerts']+=
[
    'SC_SSO' => [
        'color' => 'success', 'close_button' => true,
        // 'title' => 'Sucesso!',
        'body' => 'Se o e-mail estiver cadastrado, enviamos um link de acesso.',
    ],
    'ER_SSO' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'Link de acesso inválido ou expirado, tente novamente.',
    ],
    'ER_SSO_COOLDOWN' => [
        'color' => 'danger', 'close_button' => true,
        'body' => 'Aguarde o tempo antes de pedir outro link.',
    ],
];

/**
 * Provedores sociais disponiveis para a tela de login
 * (consumido por login_form_management() em this-system/login-settings.php).
 */
global $login_social;
$login_social['google'] = 'Google';

/**
 * Escopos OAuth solicitados por provedor. Default minimo = nome + e-mail.
 * Qualquer codigo em this-system/ pode acrescentar escopos antes do redirect:
 *   $GLOBALS['social_login_scopes']['google'][] = 'https://www.googleapis.com/auth/...';
 */
$GLOBALS['social_login_scopes'] = $GLOBALS['social_login_scopes'] ?? [
    'google' => ['openid', 'email', 'profile'],
];

/**
 * Auto-login via cookie persistente (no-op se ja logado ou sem cookie).
 */
advlogin_remember_try_autologin();

/**
 * Best-effort: se a request e o logout nativo do core, limpa tambem o cookie
 * persistente (o core logout() nao conhece este plugin).
 */
$__advlogin_url = (string) ($_GET['url'] ?? '');
if ($__advlogin_url !== '' && preg_match('#(^|/)user-logout/?$#', $__advlogin_url)) {
    advlogin_remember_forget(false);
}
unset($__advlogin_url);
