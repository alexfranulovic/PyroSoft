<?php
if (!isset($seg)) exit;

/**
 * Advanced login - helpers compartilhados pelos 3 fluxos
 * (magic link, login social, manter conectado).
 */

/**
 * ID do usuario logado na sessao atual (0 se anonimo).
 */
function advlogin_current_user_id(): int
{
    return (int) ($_SESSION['current_user']['id'] ?? 0);
}

/**
 * Sanitiza um destino de redirect pos-login.
 *
 * Aceita apenas caminho relativo interno ("/algo"); qualquer coisa que
 * cheire a URL absoluta / protocol-relative / backslash e descartada
 * (anti open-redirect). Retorna URL absoluta do site ou "" (deixa o
 * user_login decidir pelo papel).
 */
function advlogin_allowed_redirect(?string $target): string
{
    $target = trim((string) $target);

    if ($target === '')                      return '';
    if ($target[0] !== '/')                  return '';
    if (strncmp($target, '//', 2) === 0)     return '';
    if (strpos($target, '\\') !== false)     return '';
    if (strpos($target, ':') !== false)      return '';

    return site_url($target);
}

/**
 * Busca usuario por e-mail; se nao existir, cria e atribui o papel padrao.
 *
 * @param array $profile  ['email','first_name','last_name','name','origin', ...]
 * @param bool  $created   (saida) true quando um usuario novo foi criado agora
 * @return int user_id (0 em falha)
 */
function advlogin_find_or_create_user(array $profile, bool &$created = false): int
{
    $created = false;
    $email   = trim((string) ($profile['email'] ?? ''));

    if ($email !== '')
    {
        $row = get_result("SELECT id FROM tb_users WHERE email = '" . db_escape($email) . "' LIMIT 1");
        if (!empty($row['id'])) {
            return (int) $row['id'];
        }
    }

    $first = $profile['first_name'] ?? ($profile['name'] ?? '');

    insert('tb_users', [
        'first_name' => $first,
        'last_name'  => $profile['last_name'] ?? '',
        'email'      => $email !== '' ? $email : null,
        'status_id'  => 1,
    ]);

    $user_id = (int) inserted_id();

    if ($user_id > 0) {
        $created = true;

        // roles = [] => edit_user_role_assignments aplica lowest_role_user() sozinho
        edit_user_role_assignments($user_id, []);

        if (function_exists('app_log')) {
            app_log('info', 'advanced-login: usuario criado', [
                'user_id' => $user_id,
                'origin'  => $profile['origin'] ?? 'unknown',
            ]);
        }
    }

    return $user_id;
}

/**
 * O login deve exigir validacao facial depois de autenticar?
 *
 * Regra (conforme especificado): opcao ligada no CRUD + plugin facial-input
 * ativo + as funcoes invocadoras existem. Se qualquer condicao falhar,
 * retorna false e o login segue normal.
 */
function login_requires_facial(): bool
{
    global $config;

    if (empty($config['login_settings']['facial_after_login'])) {
        return false;
    }

    $activated = $config['activated_plugins'] ?? [];
    if (!is_array($activated) || !in_array('facial-input', $activated, true)) {
        return false;
    }

    return function_exists('facial_verify_prompt') && function_exists('facial_verify_check');
}

/**
 * Destino configurado para redirecionar quem ACABOU de se cadastrar.
 *
 * Reaproveita login_settings[signup_page][slug] - o mesmo campo usado pelo
 * link "Crie sua conta hoje" em login-settings.php. "" = sem override (usa
 * o fluxo normal: redirect_to pedido -> pagina do papel).
 */
function advlogin_signup_redirect(): string
{
    global $config;

    $slug = trim((string) ($config['login_settings']['signup_page']['slug'] ?? ''));
    if ($slug === '') {
        return '';
    }

    $url = get_url_page($slug, 'full');

    return !empty($url) ? $url : '';
}

/**
 * Ponto unico de conclusao de login para os 3 fluxos.
 *
 * @param int   $user_id
 * @param array $ctx ['remember'=>bool,'redirect_to'=>?string,'is_new'=>bool,'facial_proof'=>mixed,'skip_facial'=>bool]
 * @return array resposta padrao do CMS: ['code','detail',('redirect')] ou ['code'=>'challenge', ...]
 */
function advlogin_finalize_login(int $user_id, array $ctx = []): array
{
    if ($user_id <= 0) {
        return [
            'code'   => 'error',
            'detail' => ['type' => 'toast', 'msg' => 'Usuario invalido.'],
        ];
    }

    $remember     = !empty($ctx['remember']);
    $redirect_to  = $ctx['redirect_to'] ?? null;
    $is_new       = !empty($ctx['is_new']);
    $facial_proof = $ctx['facial_proof'] ?? null;
    $skip_facial  = !empty($ctx['skip_facial']);

    /**
     * Gate facial (2 passos) quando ligado.
     */
    if (!$skip_facial && login_requires_facial())
    {
        if (empty($facial_proof))
        {
            $tk = token_create([
                'type'        => 'facial_gate',
                'user_id'     => $user_id,
                'ttl_seconds' => 300,
                'mode'        => 'hex',
                'length'      => 48,
                'overwrite'   => true,
                'meta'        => [
                    'remember'    => $remember,
                    'redirect_to' => $redirect_to,
                    'is_new'      => $is_new,
                ],
            ]);

            return [
                'code'       => 'challenge',
                'gate_token' => $tk['token'] ?? '',
                'detail'     => [
                    'type' => 'modal',
                    'code' => 'IF_FACIAL_VALIDATION_REQUIRED',
                    'msg'  => facial_verify_prompt([
                        'user_id'    => $user_id,
                        'gate_token' => $tk['token'] ?? '',
                    ]),
                ],
            ];
        }

        if (facial_verify_check(['user_id' => $user_id, 'proof' => $facial_proof]) !== true)
        {
            return [
                'code'   => 'error',
                'detail' => ['type' => 'toast', 'msg' => 'Nao foi possivel validar seu rosto. Tente novamente.'],
            ];
        }
    }

    /**
     * Manter conectado.
     */
    if ($remember && function_exists('advlogin_remember_issue')) {
        advlogin_remember_issue($user_id);
    }

    /**
     * Redirect: cadastro novo -> signup_redirect (se configurado);
     * caso contrario -> redirect_to pedido -> pagina do papel.
     */
    $signup_redirect = $is_new ? advlogin_signup_redirect() : '';
    $redirect_uri    = $signup_redirect !== '' ? $signup_redirect : advlogin_allowed_redirect($redirect_to);

    return user_login([
        'user'         => $user_id,
        'force'        => true,
        'redirect_uri' => $redirect_uri !== '' ? $redirect_uri : 'role_page',
        'return_type'  => 'redirect',
    ]);
}

/**
 * POST JSON/form via cURL -> array decodificado ([] em erro).
 */
function advlogin_http_post(string $url, array $data, array $headers = []): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_HTTPHEADER     => array_merge(['Content-Type: application/x-www-form-urlencoded'], $headers),
        CURLOPT_TIMEOUT        => 20,
    ]);

    $raw  = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false || $code >= 400) {
        return [];
    }

    $json = json_decode($raw, true);
    return is_array($json) ? $json : [];
}

/**
 * GET via cURL -> array decodificado ([] em erro).
 */
function advlogin_http_get(string $url, array $headers = []): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 20,
    ]);

    $raw  = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false || $code >= 400) {
        return [];
    }

    $json = json_decode($raw, true);
    return is_array($json) ? $json : [];
}

/**
 * Normaliza o campo meta de um token (pode vir como JSON string ou array).
 */
function advlogin_token_meta($row): array
{
    if (empty($row['meta'])) {
        return [];
    }

    if (is_array($row['meta'])) {
        return $row['meta'];
    }

    $decoded = json_decode((string) $row['meta'], true);
    return is_array($decoded) ? $decoded : [];
}

/**
 * Limpeza periodica (chamada pelo cron do plugin).
 */
function advlogin_cleanup_cron()
{
    query_it("DELETE FROM tb_user_remember_tokens WHERE expires_at < NOW()");

    if (function_exists('token_cleanup_expired')) {
        token_cleanup_expired();
    }

    return true;
}
