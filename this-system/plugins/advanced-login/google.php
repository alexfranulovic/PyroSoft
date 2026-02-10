<?php
if (!isset($seg)) exit;

/**
 * Google Social Login plugin
 * - Injects a Google login button in the login page
 * - Handles /auth/google and /auth/google/callback
 */

global $login_social;
$login_social['google'] = 'Google';
$login_social['facebook'] = 'Facebook';
$login_social['linkedin'] = 'LinkedIn';


// If you already have BASE_URL constant/global, use it
$baseUrl = defined('BASE_URL') ? BASE_URL : ( (isset($GLOBALS['info']['base_url']) ? $GLOBALS['info']['base_url'] : '') );

// ---- Routes ----
$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/';
$path = trim($path, '/');

if ($path === 'auth/google') {
  google_oauth_redirect($baseUrl);
  exit;
}

if ($path === 'auth/google/callback') {
  google_oauth_callback($baseUrl);
  exit;
}

// ---- Inject button only on login page ----
if (is_login_page_request()) {
  google_inject_button_once($baseUrl);
}


// =========================
// Helpers
// =========================

function is_login_page_request(): bool
{
  $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/';
  $path = trim($path, '/');

  // Adjust if your login route is different
  return str_ends_with($path, 'login');
}

// function google_inject_button_once(string $baseUrl): void
// {
//   // Use output buffering injection
//   static $started = false;
//   if ($started) return;
//   $started = true;

//   ob_start(function ($html) use ($baseUrl) {
//     $authUrl = rtrim($baseUrl, '/') . '/auth/google';

//     $btn = '
//     <a href="'.htmlspecialchars($authUrl).'" class="btn from">
//       <i class="fa-brands fa-google"></i>
//       <span>Entrar com Google</span>
//     </a>
//     ';

//     // Inject at anchor (recommended)
//     if (strpos($html, '<!--google-login-anchor-->') !== false) {
//       return str_replace('<!--google-login-anchor-->', '<!--google-login-anchor-->'.$btn, $html);
//     }

//     // Fallback: inject before first form
//     $pos = stripos($html, '<form');
//     if ($pos !== false) {
//       return substr($html, 0, $pos) . $btn . substr($html, $pos);
//     }

//     return $html;
//   });
// }

function google_inject_button_once(string $baseUrl)
{
    $authUrl = rtrim($baseUrl, '/') . '/auth/google';

    $html = '
    <!--google-login-anchor-->
    <a href="'.htmlspecialchars($authUrl).'" class="btn from">
      <i class="fa-brands fa-google"></i>
      <span>Entrar com Google</span>
    </a>
    <!--google-login-anchor-->
    ';

    return $html;
}

function facebook_inject_button_once(string $baseUrl)
{
    $authUrl = rtrim($baseUrl, '/') . '/auth/facebook';

    $html = '
    <!--facebook-login-anchor-->
    <a href="'.htmlspecialchars($authUrl).'" class="btn from">
      <i class="fa-brands fa-facebook"></i>
      <span>Entrar com Facebook</span>
    </a>
    <!--facebook-login-anchor-->
    ';

    return $html;
}

/**
 * Starts OAuth flow
 */
function google_oauth_redirect(string $baseUrl): void
{
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();

  $redirectUri = rtrim($baseUrl, '/') . '/auth/google/callback';
  $state = bin2hex(random_bytes(16));

  $_SESSION['google_oauth_state'] = $state;

  $params = [
    'client_id'     => env('GOOGLE_CLIENT_ID'),
    'redirect_uri'  => $redirectUri,
    'response_type' => 'code',
    'scope'         => 'openid email profile',
    'state'         => $state,
    'prompt'        => 'select_account'
  ];

  $url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
  header('Location: ' . $url);
  exit;
}

/**
 * Handles callback, exchanges code, fetches user info, logs user in
 */
function google_oauth_callback(string $baseUrl): void
{
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();

  $state = $_GET['state'] ?? '';
  $code  = $_GET['code'] ?? '';

  if (!$code || !$state || empty($_SESSION['google_oauth_state']) || !hash_equals($_SESSION['google_oauth_state'], $state)) {
    google_oauth_fail(rtrim($baseUrl, '/') . '/login', 'Falha de validação do login com Google.');
  }

  unset($_SESSION['google_oauth_state']);

  $redirectUri = rtrim($baseUrl, '/') . '/auth/google/callback';

  // Exchange code for tokens
  $token = google_http_post('https://oauth2.googleapis.com/token', [
    'client_id'     => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'code'          => $code,
    'grant_type'    => 'authorization_code',
    'redirect_uri'  => $redirectUri,
  ]);

  if (empty($token['access_token'])) {
    google_oauth_fail(rtrim($baseUrl, '/') . '/login', 'Não foi possível concluir o login com Google.');
  }

  // Fetch user info
  $user = google_http_get('https://www.googleapis.com/oauth2/v3/userinfo', [
    'Authorization: Bearer ' . $token['access_token']
  ]);

  $email = $user['email'] ?? '';
  if (!$email) {
    google_oauth_fail(rtrim($baseUrl, '/') . '/login', 'Google não retornou seu e-mail.');
  }

  // Find local user by email (adjust to your CMS functions)
  // NOTE: your project has get_user($id) in user-functions.php; here we need by email.
  $localUser = google_find_user_by_email($email);

  if (!$localUser) {
    // Option A: block login (recommended first)
    google_oauth_fail(rtrim($baseUrl, '/') . '/login', 'Seu e-mail ainda não está cadastrado. Crie sua conta e tente novamente.');

    // Option B (if you want auto-signup): create user here (requires knowing tb_users columns).
  }

  // Set session like your CMS expects (match what your login flow uses)
  $_SESSION['current_user'] = (object)[
    'id'       => (int)($localUser->id ?? 0),
    'user_id'  => (int)($localUser->id ?? 0),
    'email'    => $email,
    'role_id'  => (int)($localUser->role_id ?? 0),
  ];

  // Redirect after login
  header('Location: ' . rtrim($baseUrl, '/') . '/admin');
  exit;
}

function google_oauth_fail(string $redirectTo, string $msg): void
{
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  $_SESSION['Alert'] = $msg;
  header('Location: ' . $redirectTo);
  exit;
}

function google_http_post(string $url, array $data): array
{
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query($data),
    CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_TIMEOUT        => 20
  ]);

  $raw = curl_exec($ch);
  $err = curl_error($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($raw === false || $code >= 400) return [];

  $json = json_decode($raw, true);
  return is_array($json) ? $json : [];
}

function google_http_get(string $url, array $headers = []): array
{
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_TIMEOUT        => 20
  ]);

  $raw = curl_exec($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($raw === false || $code >= 400) return [];

  $json = json_decode($raw, true);
  return is_array($json) ? $json : [];
}

/**
 * Find user by email in tb_users.
 * Adjust column names if needed (email/user_email).
 */
function google_find_user_by_email(string $email)
{
  $emailSafe = addslashes($email);

  // IMPORTANT: adjust table/column names to your real schema
  $sql = "SELECT * FROM tb_users WHERE email = '{$emailSafe}' LIMIT 1";

  $row = query_it($sql);

  // query_it sometimes returns stdClass or array depending on your core;
  // handle both safely.
  if (is_array($row) && isset($row[0])) return (object)$row[0];
  if (is_object($row)) return $row;

  return null;
}
