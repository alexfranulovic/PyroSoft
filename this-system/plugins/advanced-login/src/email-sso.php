<?php
if (!isset($seg)) exit;

/**
 * Advanced login - SSO por e-mail (magic link).
 *
 * Usa o sistema de tokens do CMS (tb_tokens, type = 'email_sso').
 * O usuario so e criado no CONSUME (clique no link), evitando lixo de
 * contas para e-mails digitados errado. Respostas do request sao sempre
 * genericas (anti-enumeracao).
 *
 * Rota: GET|POST /rest-api/advlogin-email-sso
 *   ?step=request  -> advlogin_email_sso_request()  (body: email, redirect_to)
 *   ?step=consume&key=... -> advlogin_email_sso_consume()
 */

/**
 * Cooldown (em segundos) entre pedidos de magic link, por sessao.
 * Existe para impedir clique repetido no botao (nao e o rate-limit de
 * seguranca por e-mail em advlogin_email_sso_send(), que continua valendo).
 */
if (!defined('ADVLOGIN_EMAIL_SSO_COOLDOWN_SECONDS')) define('ADVLOGIN_EMAIL_SSO_COOLDOWN_SECONDS', 30);

/**
 * Segundos restantes do cooldown atual (0 = pode pedir de novo).
 */
function advlogin_email_sso_cooldown_remaining(): int
{
    $until     = (int) ($_SESSION['advlogin_email_sso_cooldown_until'] ?? 0);
    $remaining = $until - time();

    return $remaining > 0 ? $remaining : 0;
}

/**
 * Inicia (ou reinicia) o cooldown na sessao do visitante atual.
 */
function advlogin_email_sso_start_cooldown(): void
{
    $_SESSION['advlogin_email_sso_cooldown_until'] = time() + ADVLOGIN_EMAIL_SSO_COOLDOWN_SECONDS;
}

/**
 * Gera e enfileira um magic link para o e-mail informado.
 * Reutilizado pelo fluxo social quando o provedor nao confirma o e-mail.
 *
 * @return bool true se o envio foi disparado (ou suprimido por rate-limit)
 */
function advlogin_email_sso_send(string $email, ?string $redirect_to = null, array $opts = []): bool
{
    $email = trim($email);
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    // tb_tokens.resource_id e int unsigned -> usamos crc32 do e-mail como chave
    // de rate-limit (colisao so causaria um atraso de 60s num e-mail alheio).
    $resource_id = crc32(strtolower($email));

    // rate-limit: 1 envio / 60s por e-mail
    $recent = get_result("
        SELECT id FROM tb_tokens
        WHERE type = 'email_sso'
          AND resource_id = " . (int) $resource_id . "
          AND created_at > (NOW() - INTERVAL 60 SECOND)
        LIMIT 1
    ");
    if (!empty($recent['id'])) {
        return true;
    }

    $existing = get_result("SELECT id, first_name FROM tb_users WHERE email = '" . db_escape($email) . "' LIMIT 1");
    $user_id  = !empty($existing['id']) ? (int) $existing['id'] : null;
    $first    = $opts['first_name'] ?? ($existing['first_name'] ?? '');

    $ttl = 900; // 15 min

    $tk = token_create([
        'type'        => 'email_sso',
        'user_id'     => $user_id,
        'resource_id' => $resource_id,
        'ttl_seconds' => $ttl,
        'mode'        => 'hex',
        'length'      => 48,
        'overwrite'   => $user_id !== null,
        'meta'        => [
            'email'       => $email,
            'first_name'  => $first,
            'ip'          => $_SERVER['REMOTE_ADDR'] ?? null,
            'redirect_to' => advlogin_allowed_redirect($redirect_to),
        ],
    ]);

    if (empty($tk['token'])) {
        return false;
    }

    $link = rest_api_route_url('advlogin-email-sso') . '?step=consume&key=' . urlencode($tk['token']);

    queue_message([
        'template' => 'plugin/advanced-login/login-magic-link',
        'provider' => 'brevo',
        'to' => [[
            'name'  => $first,
            'email' => $email,
        ]],
        'template_params' => [
            'first_name' => $first,
            'url'        => $link,
            'minutes'    => (int) round($ttl / 60),
        ],
    ]);

    return true;
}

/**
 * Botao "Entrar com e-mail" injetado na tela de login.
 *
 * Mesma logica dos botoes de login social: so aparece quando ligado no
 * painel (login_settings[email_sso]). Aponta para /login?email-sso, que
 * renderiza o formulario do magic link.
 */
function advlogin_email_sso_button(): string
{
    $url = pg . '/login?email-sso';

    return "
    <a href='" . htmlspecialchars($url) . "' class='btn from btn-social btn-email-sso'>
      <i class='fa-regular fa-envelope'></i>
      <span>SSO: Entrar sem senha</span>
    </a>";
}

/**
 * Handler do ?step=request.
 */
function advlogin_email_sso_request(): array
{
    $email       = trim((string) ($_POST['email'] ?? ''));
    $redirect_to = $_POST['redirect_to'] ?? ($_POST['redirect_uri'] ?? null);

    $remaining = advlogin_email_sso_cooldown_remaining();
    if ($remaining > 0) {
        return [
            'code'     => 'error',
            'cooldown' => $remaining,
            'detail'   => [
                'type' => 'toast',
                'msg'  => alert_message('ER_SSO_COOLDOWN', 'toast'),
            ],
        ];
    }

    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        advlogin_email_sso_send($email, is_string($redirect_to) ? $redirect_to : null);
        advlogin_email_sso_start_cooldown();
    }

    return [
        'code'     => 'success',
        'cooldown' => advlogin_email_sso_cooldown_remaining(),
        'detail'   => [
            'type' => 'toast',
            'msg'  => alert_message('SC_SSO', 'toast'),
        ],
    ];
}

/**
 * Handler do ?step=consume&key=... - valida o token e conclui o login.
 */
function advlogin_email_sso_consume(): void
{
    $key = (string) ($_GET['key'] ?? '');

    $row = $key !== '' ? token_validate([
        'token'   => $key,
        'type'    => 'email_sso',
        'consume' => true,
    ]) : null;

    if (empty($row)) {
        header('Location: ' . site_url('/login?sso_error=1'));
        $_SESSION['msg'] = alert_message('ER_SSO', 'toast');
        exit;
    }

    $meta    = advlogin_token_meta($row);
    $created = false;

    $user_id = !empty($row['user_id'])
        ? (int) $row['user_id']
        : advlogin_find_or_create_user([
            'email'      => $meta['email'] ?? '',
            'first_name' => $meta['first_name'] ?? '',
            'origin'     => 'email_sso',
        ], $created);

    if ($user_id <= 0) {
        header('Location: ' . site_url('/login?sso_error=1'));
        $_SESSION['msg'] = alert_message('ER_SSO', 'toast');
        exit;
    }

    $res = advlogin_finalize_login($user_id, [
        'remember'    => false,
        'redirect_to' => $meta['redirect_to'] ?? null,
        'is_new'      => $created,
    ]);

    if (($res['code'] ?? '') === 'challenge') {
        header('Location: ' . site_url('/login?facial_gate=' . urlencode($res['gate_token'] ?? '')));
        exit;
    }

    header('Location: ' . ($res['redirect'] ?? site_url('/')));
    exit;
}
