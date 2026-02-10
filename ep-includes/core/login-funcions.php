<?php
if(!isset($seg)) exit;

$login_settings = $config['login_settings'];


/**
 * Verifies if the provided token is valid.
 *
 * @param string $token The token to be verified.
 * @return void Sets an error message in the session if the token is invalid.
 */
function login_verify_token(string $token = '')
{
    $error = false;

    if (!empty($token))
    {
        $row = token_validate([
            'token'   => $token,
            'type'    => 'password_recovery',
            'consume' => false,
        ]);

        if (empty($row) || (isset($row['status']) && $row['status'] !== 'available')) {
            $error = true;
        }
    }
    else {
        $error = true;
    }

    $_SESSION['msg'] = $error ? alert_message('ER_PASSWORD_LINK_INVALID', 'alert') : null;

    return;
}


/**
 * Updates the user's password if all validations pass.
 *
 * @param array $args An associative array containing:
 *                    - 'password': The new password.
 *                    - 'repeat_password': The repeated password for confirmation.
 *                    - 'key': The token key for password recovery.
 * @return array The result of the operation including status and messages.
 */
function login_update_password(array $args = [])
{
    global $login_settings;

    $msg      = '';
    $error    = false;
    $verifyer = false;
    $tokenRow = null;

    if (!empty($args))
    {
        $password = isset($args['password']['value']) ? (string) $args['password']['value'] : '';
        $repeat   = isset($args['password']['repeat']) ? (string) $args['password']['repeat'] : '';
        $tokenKey = isset($args['key']) ? (string) $args['key'] : '';

        // 1) valida token primeiro
        if ($tokenKey === '') {
            $error = true;
            $msg   = alert_message('ER_PASSWORD_LINK_INVALID', 'toast');
        } else {
            $tokenRow = token_validate([
                'token'   => $tokenKey,
                'type'    => 'password_recovery',
                'consume' => false, // só checa aqui, vamos consumir depois
            ]);

            if (empty($tokenRow) || empty($tokenRow['user_id'])) {
                $error = true;
                $msg   = alert_message('ER_PASSWORD_LINK_INVALID', 'toast');
            }
        }

        // 2) valida regras de senha
        if (!$error)
        {
            $minLength = $login_settings['password_must']['length']['min'];
            $maxLength = $login_settings['password_must']['length']['max'];
            $length    = strlen($password) >= $minLength && strlen($password) <= $maxLength;

            $hasNumber = !empty($login_settings['password_must']['has_number'])
                ? preg_match('/\d/', $password)
                : true;

            $hasUpper = !empty($login_settings['password_must']['has_upper'])
                ? preg_match('/[A-Z]/', $password)
                : true;

            $hasSpecialCharacters = !empty($login_settings['password_must']['has_special_characters'])
                ? preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)
                : true;

            if (!$length || !$hasNumber || !$hasUpper || !$hasSpecialCharacters)
            {
                $error = true;
                $msg   = alert_message('ER_INVALID_PASSWORD', 'toast');
            }

            if ($password !== $repeat)
            {
                $error = true;
                $msg   = alert_message('ER_INVALID_REPETITION_PASSWORD', 'toast');
            }
        }

        // 3) se tudo ok, atualiza senha e consome o token
        if (!$error && !empty($tokenRow) && !empty($tokenRow['user_id']))
        {
            $user_id = (int) $tokenRow['user_id'];

            $args_bd = [
                'data'  => [
                    'password' => password_encrypt($password),
                ],
                'where' => [
                    [
                        'field'    => 'id',
                        'operator' => '=',
                        'value'    => $user_id,
                    ],
                ],
            ];

            update('tb_users', $args_bd, false);
            $verifyer = affected_rows();

            $msg = alert_message('ER_NEW_PASSWORD', 'toast');
            if ($verifyer)
            {
                $msg = alert_message('SC_NEW_PASSWORD', 'toast');

                // agora sim, consome o token (marca como validated + consumed_at)
                token_validate([
                    'token'   => $tokenKey,
                    'type'    => 'password_recovery',
                    'user_id' => $user_id,
                    'consume' => true,
                ]);
            }
        }
    }

    $res = [
        'code'   => !$error ? 'success' : 'error',
        'detail' => [
            'type' => 'toast',
            'msg'  => $msg,
        ],
    ];

    if ($verifyer) {
        $res['redirect'] = pg . '/login';
    }

    return $res;
}

function password_encrypt(string $pwd)
{
    return password_hash($pwd, PASSWORD_ARGON2ID);
}

function password_decrypt(string $pwd, string $hash = null)
{
    // New moderno (bcrypt, argon2, etc)
    if (strpos($hash, '$') === 0) {
        return password_verify($pwd, $hash);
    } else {
        // Legacy MD5
        return md5($pwd) === $hash;
    }
}


/**
 * Authenticates a user with the provided login credentials.
 *
 * @param string $login The username, email, or ID of the user.
 * @param string $password The user's password (password_encrypt hashed).
 * @param string $return_type (optional) The return type, either 'redirect' (default) or 'boolean'.
 * @return void|bool If $return_type is 'redirect', it redirects the user upon successful login.
 *                    If $return_type is 'boolean', it returns true upon successful login, false otherwise.
 */
function user_login($args)
{
    global $config, $login_settings;

    $login        = trim($args['user'] ?? '');
    $password     = trim($args['password'] ?? '');
    $force        = $args['force'] ?? false;
    $redirect_uri = $args['redirect_uri'] ?? '';
    $return_type  = $args['return_type'] ?? 'redirect';

    $msg   = '';
    $error = false;
    $redirect = null;

    /**
     * 1. Fetch user by id, email or login
     */
    $current_user = get_result("
        SELECT *
        FROM tb_users
        WHERE (id='{$login}' OR email='{$login}' OR login='{$login}')
        LIMIT 1
    ");

    if (empty($current_user)) {
        $error = true;
        $msg   = alert_message('ER_INVALID_LOGIN', 'toast');
    }

    /**
     * 2. Validate password
     */
    elseif (!$force && !password_decrypt($password, $current_user['password'])) {
        $error = true;
        $msg   = alert_message('ER_INVALID_LOGIN', 'toast');
    }

    /**
     * 3. System block validation - Only developers can log in when block_system = 1
     */
    elseif ($config['block_system'] == 1 && !is_dev($current_user['id'])) {
        $error = true;
        $msg   = alert_message('ER_ONLY_DEV_ALLOWED_TO_LOGIN', 'toast');
    }

    /**
     * 4. If login is valid → create session
     */
    if (!$error)
    {
        // Prevent session fixation attacks
        session_regenerate_id(true);

        // Store only required data in session
        $_SESSION['current_user'] = [
            'id' => $current_user['id'],
        ];

        // Fetch user's highest role and its redirect page
        $role_user = highest_role_user($current_user['id']);
        $redirect  = get_url_page($role_user['redirect_page_id'], 'full');

        // If redirect_uri is provided, overwrite default redirect
        if (!empty($redirect_uri) && $redirect_uri !== 'role_page') {
            $redirect = $redirect_uri;
        }
    }

    /**
     * 6. Response structure
     */
    $res = [
        'code' => !$error ? 'success' : 'error',
        'detail' => [
            'type' => 'toast',
            'msg'  => $msg,
        ],
    ];

    if (!$error) {
        $res['status_id'] = $current_user['status_id'];
    }

    // Append redirect only when login succeeded
    if (!$error && $return_type === 'redirect' && isset($redirect)) {
        $res['redirect'] = $redirect;
    }

    return $res;
}



/**
 * Logs out the currently logged-in user.
 *
 * @return void|bool it logs the user out and returns true.
 */
function logout()
{
    session_destroy();
    return true;
}

/**
 * Generates the URL for logging out the currently logged-in user.
 *
 * @return string The URL for logging out the user.
 */
function logout_url()
{
    return rest_api_route_url("user-logout");
}


/**
 * Handles the forgot password process by sending an email with a reset link.
 *
 * @return array The result of the operation including status and messages.
 */
function login_forgot_password()
{
    global $login_settings;

    $error = false;
    $msg   = '';

    $email = isset($_POST['email']) ? trim((string) $_POST['email']) : '';

    $current_user = get_result("SELECT id, first_name, email FROM tb_users WHERE email = '" . addslashes($email) . "' LIMIT 1");

    if (isset($current_user['email']))
    {
        // TTL (em segundos) pode vir de config; se não vier, usa 1h
        $ttl_seconds = !empty($login_settings['password_recovery']['ttl_seconds'])
            ? (int) $login_settings['password_recovery']['ttl_seconds']
            : USER_PASSWORD_RECOVERY_TIME;

        // cria token para recuperação de senha
        $tokenData = token_create([
            'type'        => 'password_recovery',
            'user_id'     => $current_user['id'],
            'ttl_seconds' => $ttl_seconds,
            'mode'        => 'md5',
            'overwrite'   => true, // revoga tokens antigos desse user & type
        ]);


        if (!$tokenData || empty($tokenData['token']))
        {
            $error = true;
            $msg   = alert_message("ER_SEND_EMAIL", 'toast');
        }
        else
        {
            $token = $tokenData['token'];
            $url   = pg . "/login?forgot-password&key=" . urlencode($token);

            $message = "
            <p>Prezado(a) <strong>{$current_user['first_name']}</strong>,</p>
            <p>Para continuar o processo de recuperação de sua senha, clique no botão abaixo ou cole o endereço abaixo no seu navegador:</p>
            <p>Seguindo o link abaixo você poderá alterar sua senha:</p>
            <div class='align-itens-center'>
                <a class='btn' href='{$url}'>Recuperar senha agora</a>
            </div>
            <p>Se você não solicitou essa alteração, nenhuma ação é necessária. Sua senha permanecerá a mesma até que você ative este código e recupere a senha.</p>
            <p>Atenciosamente,</p>";

            $email_data = [
                'to' => [
                    [
                        'name'  => $current_user['first_name'],
                        'email' => $current_user['email'],
                    ],
                ],
                'subject'   => 'Recuperação de senha',
                'body'      => $message,
                'signature' => [
                    'humanized' => false,
                ],
            ];

            $msg = send_email($email_data)
                ? alert_message("SC_SEND_EMAIL", 'toast')
                : alert_message("ER_SEND_EMAIL", 'toast');
        }
    }
    else {
        $error = true;
        $msg   = alert_message("ER_MAINTENANCE_PAGE", 'toast');
    }

    return [
        'code'   => !$error ? 'success' : 'error',
        'detail' => [
            'type' => 'toast',
            'msg'  => $msg,
        ],
    ];
}
