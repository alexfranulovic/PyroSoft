<?php
if (!isset($seg)) exit;

/**
 * Advanced login - "manter conectado" (cookie persistente).
 *
 * Padrao selector/validator:
 *   cookie 'advlogin_remember' = "<selector>:<validator>"
 *   tb_user_remember_tokens guarda selector (lookup) + sha256(validator).
 * A cada uso o validator e rotacionado. Selector valido + validator errado
 * => provavel roubo de cookie: todas as sessoes persistentes do usuario
 * sao revogadas.
 */

define('ADVLOGIN_REMEMBER_COOKIE', 'advlogin_remember');
if (!defined('ADVLOGIN_REMEMBER_DAYS'))  define('ADVLOGIN_REMEMBER_DAYS', 30);

/**
 * Janela de validade (dias). .env tem prioridade sobre o CRUD.
 */
function advlogin_remember_days(): int
{
    global $config;

    $days = (int) (ADVLOGIN_REMEMBER_DAYS ?: 30);

    return $days > 0 ? $days : 30;
}

function advlogin_remember_set_cookie(string $value, int $expires): void
{
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? null) == 443);

    setcookie(ADVLOGIN_REMEMBER_COOKIE, $value, [
        'expires'  => $expires,
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    $_COOKIE[ADVLOGIN_REMEMBER_COOKIE] = $value;
}

function advlogin_remember_clear_cookie(): void
{
    setcookie(ADVLOGIN_REMEMBER_COOKIE, '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    unset($_COOKIE[ADVLOGIN_REMEMBER_COOKIE]);
}

/**
 * Emite um novo token persistente + cookie para o usuario.
 * Chamado por advlogin_finalize_login() quando o usuario pediu "manter conectado".
 */
function advlogin_remember_issue(int $user_id): void
{
    if ($user_id <= 0) {
        return;
    }

    $selector  = bin2hex(random_bytes(9));   // 18 chars (cabe em varchar(24))
    $validator = bin2hex(random_bytes(32));  // 64 chars
    $expires   = time() + advlogin_remember_days() * 86400;

    insert('tb_user_remember_tokens', [
        'user_id'        => $user_id,
        'selector'       => $selector,
        'validator_hash' => hash('sha256', $validator),
        'expires_at'     => date('Y-m-d H:i:s', $expires),
        'created_at'     => date('Y-m-d H:i:s'),
        'user_agent'     => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        'ip'             => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);

    advlogin_remember_set_cookie($selector . ':' . $validator, $expires);
}

/**
 * Tenta autenticar via cookie persistente. Chamado no index.php do plugin,
 * antes do roteamento / controle de acesso.
 */
function advlogin_remember_try_autologin(): void
{
    if (is_user_logged_in() || empty($_COOKIE[ADVLOGIN_REMEMBER_COOKIE])) {
        return;
    }

    $parts = explode(':', (string) $_COOKIE[ADVLOGIN_REMEMBER_COOKIE], 2);
    if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
        advlogin_remember_clear_cookie();
        return;
    }

    [$selector, $validator] = $parts;

    $row = get_result("
        SELECT * FROM tb_user_remember_tokens
        WHERE selector = '" . db_escape($selector) . "'
        LIMIT 1
    ");

    if (empty($row)) {
        advlogin_remember_clear_cookie();
        return;
    }

    // expirado
    if (strtotime($row['expires_at']) < time()) {
        query_it("DELETE FROM tb_user_remember_tokens WHERE id = " . (int) $row['id']);
        advlogin_remember_clear_cookie();
        return;
    }

    // selector ok + validator errado => provavel roubo: revoga tudo do usuario
    if (!hash_equals((string) $row['validator_hash'], hash('sha256', $validator))) {
        query_it("DELETE FROM tb_user_remember_tokens WHERE user_id = " . (int) $row['user_id']);
        advlogin_remember_clear_cookie();

        if (function_exists('app_log')) {
            app_log('warning', 'advanced-login: remember-me validator mismatch (possivel roubo de cookie)', [
                'user_id'  => (int) $row['user_id'],
                'selector' => $selector,
                'ip'       => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
        }
        return;
    }

    // Se o login exige validacao facial, nao concede sessao silenciosa:
    // o usuario tem que passar pelo fluxo completo de login.
    if (login_requires_facial()) {
        return;
    }

    // rotaciona o validator
    $new_validator = bin2hex(random_bytes(32));

    update('tb_user_remember_tokens', [
        'data' => [
            'validator_hash' => hash('sha256', $new_validator),
            'last_used_at'   => date('Y-m-d H:i:s'),
        ],
        'where' => [
            ['field' => 'id', 'operator' => '=', 'value' => (int) $row['id']],
        ],
    ], false);

    advlogin_remember_set_cookie($selector . ':' . $new_validator, strtotime($row['expires_at']));

    user_login([
        'user'        => (int) $row['user_id'],
        'force'       => true,
        'return_type' => 'boolean',
    ]);
}

/**
 * Revoga o dispositivo atual (default) ou todos os dispositivos do usuario.
 * Rota: POST /rest-api/advlogin-remember-forget  (body: all=1 opcional)
 */
function advlogin_remember_forget(bool $all = false): array
{
    $uid = advlogin_current_user_id();

    if ($all && $uid > 0) {
        query_it("DELETE FROM tb_user_remember_tokens WHERE user_id = " . (int) $uid);
    } elseif (!empty($_COOKIE[ADVLOGIN_REMEMBER_COOKIE])) {
        $selector = explode(':', (string) $_COOKIE[ADVLOGIN_REMEMBER_COOKIE], 2)[0];
        if ($selector !== '') {
            query_it("DELETE FROM tb_user_remember_tokens WHERE selector = '" . db_escape($selector) . "'");
        }
    }

    advlogin_remember_clear_cookie();

    return [
        'code'   => 'success',
        'detail' => [
            'type' => 'toast',
            'msg'  => $all ? 'Sessoes encerradas em todos os dispositivos.' : 'Dispositivo desconectado.',
        ],
    ];
}

/**
 * Logout que tambem limpa o cookie persistente.
 * Rota: GET /rest-api/advlogin-logout
 */
function advlogin_logout(): void
{
    advlogin_remember_forget(false);

    if (function_exists('logout')) {
        logout();
    }

    header('Location: ' . site_url('/login'));
    exit;
}
