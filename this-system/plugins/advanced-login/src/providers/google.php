<?php
if (!isset($seg)) exit;

/**
 * Driver de login social - Google.
 *
 * Este e o unico provedor implementado por enquanto. Para adicionar outro
 * (facebook, linkedin, github), crie src/providers/<p>.php seguindo este
 * mesmo shape em $GLOBALS['advlogin_providers'][<p>], defina a funcao
 * <p>_inject_button_once() e pronto - api.php registra as rotas em loop.
 */

$GLOBALS['advlogin_providers']['google'] = [
    'label'             => 'Google',
    'icon'             => 'fa-brands fa-google',
    'authorize_url'    => 'https://accounts.google.com/o/oauth2/v2/auth',
    'token_url'        => 'https://oauth2.googleapis.com/token',
    'userinfo_url'     => 'https://www.googleapis.com/oauth2/v3/userinfo',
    'client_id_env'    => 'GOOGLE_CLIENT_ID',
    'client_secret_env'=> 'GOOGLE_CLIENT_SECRET',
    'default_scopes'   => ['openid', 'email', 'profile'],
    'scope_separator'  => ' ',
    'pkce'             => true,
    'authorize_extra'  => [
        'access_type' => 'online',
        'prompt'      => 'select_account',
    ],

    /**
     * Mapeia o payload cru do userinfo para o formato interno do plugin.
     */
    'map_profile' => function (array $r): array {
        return [
            'provider_user_id' => (string) ($r['sub'] ?? ''),
            'email'            => (string) ($r['email'] ?? ''),
            'email_verified'   => !empty($r['email_verified']),
            'first_name'       => (string) ($r['given_name'] ?? ''),
            'last_name'        => (string) ($r['family_name'] ?? ''),
            'name'             => (string) ($r['name'] ?? ''),
            'avatar'           => (string) ($r['picture'] ?? ''),
        ];
    },
];

/**
 * Botao injetado na tela de login.
 *
 * Assinatura compativel com login_form_management() em
 * this-system/login-settings.php, que chama "{$provider}_inject_button_once"('df').
 */
function google_inject_button_once($context = null): string
{
    $url = rest_api_route_url('advlogin-social-google');

    return "
    <a href='" . htmlspecialchars($url) . "' class='btn from btn-social btn-social-google'>
      <i class='fa-brands fa-google'></i>
      <span>Entrar com Google</span>
    </a>";
}
