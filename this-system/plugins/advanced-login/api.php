<?php
if (!isset($seg)) exit;

/**
 * Advanced login - rotas REST.
 *
 * load_plugins('api') roda numa passada separada de load_plugins('index'),
 * mas no mesmo request: as funcoes e o mapa $GLOBALS['advlogin_providers']
 * ja foram carregados pelo index.php. Os require_once abaixo sao apenas
 * uma rede de seguranca idempotente.
 */
require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/providers/google.php';
require_once __DIR__ . '/src/email-sso.php';
require_once __DIR__ . '/src/social-login.php';
require_once __DIR__ . '/src/remember-me.php';
require_once __DIR__ . '/src/password-login.php';

/**
 * Login por senha (wrapper com "manter conectado" + gate facial).
 */
register_rest_route('advlogin-login', [
    'methods'             => 'POST',
    'permission_callback' => '__return_true',
    'callback'            => 'advlogin_password_login',
]);

/**
 * SSO por e-mail (magic link).
 *   ?step=request  -> pede o link
 *   ?step=consume  -> consome o link e loga
 */
register_rest_route('advlogin-email-sso', [
    'methods'             => ['GET', 'POST'],
    'permission_callback' => '__return_true',
    'callback'            => function () {
        $step = $_GET['step'] ?? 'request';

        if ($step === 'consume') {
            advlogin_email_sso_consume();
            return;
        }

        return advlogin_email_sso_request();
    },
]);

/**
 * Login social - uma dupla de rotas por provedor implementado (hoje: google).
 */
foreach (array_keys($GLOBALS['advlogin_providers'] ?? []) as $p) {

    register_rest_route("advlogin-social-{$p}", [
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => function () use ($p) {
            advlogin_social_redirect($p);
        },
    ]);

    register_rest_route("advlogin-social-{$p}-callback", [
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => function () use ($p) {
            advlogin_social_callback($p);
        },
    ]);
}

/**
 * Passo 2 do login quando o gate facial esta ativo.
 * Body: gate_token, facial_proof
 */
register_rest_route('advlogin-facial-gate', [
    'methods'             => 'POST',
    'permission_callback' => '__return_true',
    'callback'            => function () {
        $row = token_validate([
            'token'   => $_POST['gate_token'] ?? '',
            'type'    => 'facial_gate',
            'consume' => true,
        ]);

        if (empty($row)) {
            return [
                'code'   => 'error',
                'detail' => ['type' => 'toast', 'msg' => 'Sessao de validacao expirada. Faca login novamente.'],
            ];
        }

        $meta = advlogin_token_meta($row);

        return advlogin_finalize_login((int) $row['user_id'], [
            'remember'     => !empty($meta['remember']),
            'redirect_to'  => $meta['redirect_to'] ?? null,
            'is_new'       => !empty($meta['is_new']),
            'facial_proof' => $_POST['facial_proof'] ?? null,
        ]);
    },
]);

/**
 * Revoga "manter conectado" (dispositivo atual ou todos).
 * Body: all=1 opcional
 */
register_rest_route('advlogin-remember-forget', [
    'methods'             => 'POST',
    'need_login'          => true,
    'permission_callback' => '__return_true',
    'callback'            => function () {
        return advlogin_remember_forget(!empty($_POST['all']));
    },
]);

/**
 * Logout que tambem limpa o cookie persistente.
 */
register_rest_route('advlogin-logout', [
    'methods'             => 'GET',
    'permission_callback' => '__return_true',
    'callback'            => 'advlogin_logout',
]);
