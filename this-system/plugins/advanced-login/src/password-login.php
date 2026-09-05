<?php
if (!isset($seg)) exit;

/**
 * Advanced login - wrapper do login por senha.
 *
 * Existe para que "manter conectado" e o gate facial tambem valham no login
 * tradicional, sem tocar em ep-includes. O form de login aponta para a rota
 * 'advlogin-login' quando o plugin esta ativo (ver this-system/login-settings.php).
 *
 * Diferente do core user_login(), aqui a senha e conferida ANTES de abrir a
 * sessao - assim o gate facial pode barrar o login antes de conceder acesso.
 */

function advlogin_truthy($v): bool
{
    if (is_bool($v)) return $v;
    $v = strtolower(trim((string) $v));
    return in_array($v, ['1', 'true', 'on', 'yes', 'sim'], true);
}

function advlogin_login_error(): array
{
    return [
        'code'   => 'error',
        'detail' => [
            'type' => 'toast',
            'msg'  => alert_message('ER_INVALID_LOGIN', 'toast'),
        ],
    ];
}

/**
 * Rota: POST /rest-api/advlogin-login
 * Body: user, password, save_login?, redirect_to?, facial_proof?
 */
function advlogin_password_login(): array
{
    global $config;

    $login    = trim((string) ($_POST['user'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $remember = advlogin_truthy($_POST['save_login'] ?? null);
    $redirect = $_POST['redirect_to'] ?? null;
    $facial   = $_POST['facial_proof'] ?? null;

    if ($login === '' || $password === '') {
        return advlogin_login_error();
    }

    $u = get_result("
        SELECT id, password
        FROM tb_users
        WHERE (id = '" . db_escape($login) . "'
            OR email = '" . db_escape($login) . "'
            OR login = '" . db_escape($login) . "')
        LIMIT 1
    ");

    if (empty($u) || empty($u['password']) || !password_decrypt($password, $u['password'])) {
        return advlogin_login_error();
    }

    // Bloqueio de sistema: apenas desenvolvedores logam
    if (($config['block_system'] ?? 0) == 1 && function_exists('is_dev') && !is_dev($u['id'])) {
        return [
            'code'   => 'error',
            'detail' => [
                'type' => 'toast',
                'msg'  => alert_message('ER_ONLY_DEV_ALLOWED_TO_LOGIN', 'toast'),
            ],
        ];
    }

    return advlogin_finalize_login((int) $u['id'], [
        'remember'     => $remember,
        'redirect_to'  => $redirect,
        'facial_proof' => $facial,
    ]);
}
