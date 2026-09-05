<?php
if (!isset($seg)) exit;

/**
 * Advanced login - motor generico de login social (OAuth 2.0 + PKCE).
 *
 * Cada provedor e um "driver" em $GLOBALS['advlogin_providers'][<p>]
 * (ver src/providers/google.php). Hoje so o Google esta implementado;
 * o motor abaixo e agnostico e serve qualquer driver com o mesmo shape.
 *
 * Rotas (registradas em loop no api.php, uma dupla por provedor):
 *   GET /rest-api/advlogin-social-<p>            -> advlogin_social_redirect()
 *   GET /rest-api/advlogin-social-<p>-callback   -> advlogin_social_callback()
 */

/**
 * Escopos a solicitar ao provedor.
 *
 * Contrato primario: variavel global $GLOBALS['social_login_scopes'][<p>]
 * (default minimo = nome + e-mail). Campo opcional no CRUD
 * login_settings[login_social][<p>][scopes] e mesclado por cima.
 */
function advlogin_social_scopes(string $provider, array $cfg): array
{
    global $config;

    $scopes = $GLOBALS['social_login_scopes'][$provider] ?? ($cfg['default_scopes'] ?? []);
    $scopes = is_array($scopes) ? $scopes : [];

    $extra = $config['login_settings']['login_social'][$provider]['scopes'] ?? '';
    if (is_string($extra) && trim($extra) !== '') {
        foreach (preg_split('/[\s,]+/', trim($extra)) as $s) {
            if ($s !== '') {
                $scopes[] = $s;
            }
        }
    }

    return array_values(array_unique(array_filter($scopes)));
}

/**
 * Passo 1: monta a URL de autorizacao e redireciona o browser ao provedor.
 */
function advlogin_social_redirect(string $provider): void
{
    $cfg = $GLOBALS['advlogin_providers'][$provider] ?? null;

    if (!$cfg) {
        header('Location: ' . site_url('/login?social_error=provider'));
        exit;
    }

    $client_id = env($cfg['client_id_env']);
    if ($client_id === '') {
        header('Location: ' . site_url('/login?social_error=config'));
        exit;
    }

    $meta = [
        'redirect_to'  => advlogin_allowed_redirect($_GET['redirect_to'] ?? null),
        'link_user_id' => is_user_logged_in() ? advlogin_current_user_id() : null,
    ];

    $params = [
        'client_id'     => $client_id,
        'redirect_uri'  => rest_api_route_url("advlogin-social-{$provider}-callback"),
        'response_type' => 'code',
        'scope'         => implode($cfg['scope_separator'] ?? ' ', advlogin_social_scopes($provider, $cfg)),
    ];

    // PKCE
    if (!empty($cfg['pkce'])) {
        $verifier  = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        $meta['pkce_verifier']           = $verifier;
        $params['code_challenge']        = $challenge;
        $params['code_challenge_method'] = 'S256';
    }

    // state = token do CMS (guarda PKCE verifier + redirect_to + link_user_id)
    $tk = token_create([
        'type'        => "oauth_state:{$provider}",
        'user_id'     => $meta['link_user_id'] ?: null,
        'ttl_seconds' => 600,
        'mode'        => 'hex',
        'length'      => 48,
        'meta'        => $meta,
    ]);

    $params['state'] = $tk['token'] ?? '';

    if (!empty($cfg['authorize_extra']) && is_array($cfg['authorize_extra'])) {
        $params += $cfg['authorize_extra'];
    }

    header('Location: ' . $cfg['authorize_url'] . '?' . http_build_query($params));
    exit;
}

/**
 * Passo 2: callback do provedor - troca code, busca perfil, resolve usuario, loga.
 */
function advlogin_social_callback(string $provider): void
{
    $fail = function (string $why) {
        header('Location: ' . site_url('/login?social_error=' . urlencode($why)));
        exit;
    };

    $cfg = $GLOBALS['advlogin_providers'][$provider] ?? null;
    if (!$cfg) {
        $fail('provider');
    }

    if (!empty($_GET['error'])) {
        $fail('denied');
    }

    $state = (string) ($_GET['state'] ?? '');
    $code  = (string) ($_GET['code'] ?? '');
    if ($state === '' || $code === '') {
        $fail('params');
    }

    $strow = token_validate([
        'token'   => $state,
        'type'    => "oauth_state:{$provider}",
        'consume' => true,
    ]);
    if (empty($strow)) {
        $fail('state');
    }

    $meta = advlogin_token_meta($strow);

    /**
     * 2.1 - troca code -> tokens
     */
    $token_params = [
        'client_id'     => env($cfg['client_id_env']),
        'client_secret' => env($cfg['client_secret_env']),
        'code'          => $code,
        'grant_type'    => 'authorization_code',
        'redirect_uri'  => rest_api_route_url("advlogin-social-{$provider}-callback"),
    ];

    if (!empty($meta['pkce_verifier'])) {
        $token_params['code_verifier'] = $meta['pkce_verifier'];
    }

    $token = advlogin_http_post($cfg['token_url'], $token_params);
    if (empty($token['access_token'])) {
        $fail('token');
    }

    /**
     * 2.2 - userinfo
     */
    $raw = advlogin_http_get($cfg['userinfo_url'], [
        'Authorization: Bearer ' . $token['access_token'],
    ]);
    if (empty($raw)) {
        $fail('userinfo');
    }

    $profile = ($cfg['map_profile'])($raw);
    if (empty($profile['provider_user_id'])) {
        $fail('profile');
    }

    $scopes_granted = $token['scope'] ?? implode(' ', advlogin_social_scopes($provider, $cfg));

    /**
     * 2.3 - resolve usuario (identidade existente / vinculo / e-mail verificado)
     */
    $created = false;
    $user_id = advlogin_social_resolve_user($provider, $profile, $meta, $created);

    if ($user_id <= 0) {
        // e-mail nao verificado pelo provedor e sem conta local:
        // NAO auto-vincula -> confirma posse via magic link.
        if (!empty($profile['email']) && function_exists('advlogin_email_sso_send')) {
            advlogin_email_sso_send($profile['email'], $meta['redirect_to'] ?? null, [
                'first_name' => $profile['first_name'] ?: $profile['name'],
            ]);
        }
        header('Location: ' . site_url('/login?confirm_email=1'));
        exit;
    }

    /**
     * 2.4 - grava/atualiza a identidade social
     */
    advlogin_social_upsert_identity($provider, $user_id, $profile, $token, (string) $scopes_granted);

    /**
     * 2.5 - conclui o login (pode cair no gate facial)
     */
    $res = advlogin_finalize_login($user_id, [
        'remember'    => false,
        'redirect_to' => $meta['redirect_to'] ?? null,
        'is_new'      => $created,
    ]);

    if (($res['code'] ?? '') === 'challenge') {
        $gate = $res['gate_token'] ?? '';
        header('Location: ' . site_url('/login?facial_gate=' . urlencode($gate)));
        exit;
    }

    header('Location: ' . ($res['redirect'] ?? site_url('/')));
    exit;
}

/**
 * Decide qual user_id corresponde ao perfil social recebido.
 *
 *  a) identidade ja vinculada a este provider_user_id
 *  b) fluxo de "vincular" disparado por usuario logado (meta.link_user_id)
 *  c) e-mail verificado pelo provedor  -> casa por e-mail (cria se preciso)
 *  d) e-mail NAO verificado e sem conta -> 0 (chamador cai no magic link)
 */
function advlogin_social_resolve_user(string $provider, array $profile, array $meta, bool &$created = false): int
{
    $created = false;

    $prov = db_escape($provider);
    $pid  = db_escape($profile['provider_user_id']);

    $row = get_result("
        SELECT user_id FROM tb_user_social_identities
        WHERE provider = '{$prov}' AND provider_user_id = '{$pid}'
        LIMIT 1
    ");
    if (!empty($row['user_id'])) {
        return (int) $row['user_id'];
    }

    if (!empty($meta['link_user_id'])) {
        return (int) $meta['link_user_id'];
    }

    if (!empty($profile['email']) && !empty($profile['email_verified'])) {
        return advlogin_find_or_create_user([
            'email'      => $profile['email'],
            'first_name' => $profile['first_name'],
            'last_name'  => $profile['last_name'],
            'name'       => $profile['name'],
            'origin'     => "social:{$provider}",
        ], $created);
    }

    return 0;
}

/**
 * Upsert em tb_user_social_identities. Nao guarda access/refresh token nesta
 * fase (autenticacao pura) - colunas ficam para uso futuro.
 */
function advlogin_social_upsert_identity(string $provider, int $user_id, array $profile, array $token, string $scopes): void
{
    $now  = date('Y-m-d H:i:s');
    $prov = db_escape($provider);
    $pid  = db_escape($profile['provider_user_id']);

    $existing = get_result("
        SELECT id FROM tb_user_social_identities
        WHERE provider = '{$prov}' AND provider_user_id = '{$pid}'
        LIMIT 1
    ");

    if (!empty($existing['id'])) {
        update('tb_user_social_identities', [
            'data' => [
                'user_id'        => $user_id,
                'email'          => $profile['email'] ?: null,
                'email_verified' => !empty($profile['email_verified']) ? 1 : 0,
                'name'           => $profile['name'] ?: null,
                'avatar_url'     => $profile['avatar'] ?: null,
                'scopes'         => $scopes,
                'raw_profile'    => json_encode($profile, JSON_UNESCAPED_UNICODE),
                'updated_at'     => $now,
                'last_login_at'  => $now,
            ],
            'where' => [
                ['field' => 'id', 'operator' => '=', 'value' => (int) $existing['id']],
            ],
        ], false);
        return;
    }

    insert('tb_user_social_identities', [
        'user_id'          => $user_id,
        'provider'         => $provider,
        'provider_user_id' => $profile['provider_user_id'],
        'email'            => $profile['email'] ?: null,
        'email_verified'   => !empty($profile['email_verified']) ? 1 : 0,
        'name'             => $profile['name'] ?: null,
        'avatar_url'       => $profile['avatar'] ?: null,
        'scopes'           => $scopes,
        'raw_profile'      => json_encode($profile, JSON_UNESCAPED_UNICODE),
        'created_at'       => $now,
        'updated_at'       => $now,
        'last_login_at'    => $now,
    ]);
}
