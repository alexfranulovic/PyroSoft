<?php
if(!isset($seg)) exit;

/**
 * Defines the "aside" metadata used by authentication-related forms.
 *
 * Each entry configures the visual and textual context displayed alongside
 * a specific login flow (e.g., login, register, maintenance, password reset).
 *
 * Structure:
 * - `svg`         Identifier of the illustration asset to be rendered.
 * - `title`       Primary heading shown to the user.
 * - `description` (optional) Supporting text providing guidance or context.
 *
 * These values are consumed by UI builders such as `login_modal()` and
 * other form renderers that support an "aside" section.
 *
 * Notes:
 * - This configuration is purely presentational; it contains no business logic.
 * - Text values are assumed to be trusted and localized upstream if needed.
 * - Missing optional keys (like `description`) are handled gracefully by renderers.
 */
$login_forms['register'] = [
    'main' => [
    ],
    'aside' => [
        'svg' => 'login-rafiki',
        'title' => 'Create your account and enjoy the best of ' . $info['name'],
    ]
];

$login_forms['login'] = [
    'main' => [
        'title' => 'Log in to ' . $info['name'],
        'description' => 'Use an account or enter your details to access the platform.',
    ],
    'aside' => [
        'svg' => 'login-rafiki',
        'title' => 'Easier way to build a Product',
        'description' => 'Security, data, performance, and much more — you’ll find it all right here.',
    ]
];

$login_forms['block_system'] = [
    'main' => [
    ],
    'aside' => [
        'svg' => 'maintenance-amico',
        'title' => 'Oops... Something went wrong...',
        'description' => 'The site is under maintenance. Please come back later!',
    ]
];

$login_forms['find_account'] = [
    'main' => [
    ],
    'aside' => [
        'svg' => 'forgot-password-bro',
        'title' => 'Find your account',
    ]
];

$login_forms['new_password'] = [
    'main' => [
        'title' => 'Enter your new password',
        'description' => 'Write down this password somewhere safe. Don’t forget it again, okay?',
    ],
    'aside' => [
        'svg' => 'webinar-pana',
    ]
];

$login_forms['email_sso'] = [
    'main' => [
        'title' => 'Log in with email',
        'description' => 'We’ll send a login link to your email. No password required.',
    ],
    'aside' => [
        'svg' => 'login-rafiki',
        'title' => 'Quick access, no password needed',
    ]
];


/**
 * Builds and returns the login form configuration(s) used by the authentication pages.
 *
 * This function assembles the HTML fields for the login form (username/email + password),
 * optionally appending extra controls such as:
 * - redirect persistence (`redirect_to` hidden input)
 * - "keep me logged in" switch (`save_login`)
 * - reCAPTCHA widget (when enabled in settings)
 *
 * It then registers two form variants into the global `$login_forms` array:
 * - `login`: the standard login page
 * - `block_system`: a restricted login page (e.g., only developers can login)
 *
 * Finally, it merges the computed forms with the result of `login_form()` (which may add/override
 * configurations) and returns the requested form by `$key`, defaulting to `login` when missing.
 *
 * Side effects:
 * - Mutates the global `$login_forms` array.
 *
 * Security notes:
 * - The `redirect_to` value is read from `$_GET`. This function only places it into a hidden field;
 *   it does NOT validate or sanitize the URL. Validation/allow-listing should be enforced server-side
 *   in the login endpoint (`user-login`) to prevent open redirect vulnerabilities.
 *
 * Dependencies:
 * - `input()` to build field markup.
 * - `block()` for the footer "OR" separator.
 * - `rest_api_route_url()` to generate the form action endpoint.
 * - `login_form()` to provide additional form definitions or overrides.
 *
 * @param string $key The form key to return (e.g., 'login', 'block_system'). Defaults to 'login'.
 *
 * @return array Returns a form configuration array for the given key. If the key does not exist,
 *               returns the 'login' configuration.
 */
function login_form_management($key = 'login')
{
    global $info, $login_settings, $login_forms, $login_social, $config;

    $login_fields = $login_footer = '';

    /**
     * Quando o plugin advanced-login esta ativo, o form de login aponta para
     * o wrapper 'advlogin-login' (adiciona "manter conectado" + gate facial).
     * Caso contrario, usa a rota nativa 'user-login'.
     */
    $login_action = rest_api_route_url(
        in_array('advanced-login', $config['activated_plugins'] ?? [], true)
            ? 'advlogin-login'
            : 'user-login'
    );

    $alt_logins = '';

    if (!empty($login_settings['login_social']))
    {
        foreach ($login_settings['login_social'] as $from)
        {
            $function_caller = "{$from}_inject_button_once";
            if (function_exists($function_caller)) {
                $alt_logins.= $function_caller('df');
            }
        }
    }

    // Entrar com e-mail (magic link) - mesma logica de exibicao do login social,
    // ligado/desligado por login_settings[email_sso] no painel.
    if (!empty($login_settings['email_sso']) && function_exists('advlogin_email_sso_button'))
    {
        $alt_logins.= advlogin_email_sso_button();
    }

    if ($alt_logins !== '')
    {
        $login_fields.= "<div class='social-logins'>{$alt_logins}</div>" . block('division', [ 'title' => 'OU' ]);
    }

    /**
     *
     * Form login fields.
     *
     */
    $login_fields.=
    input(
      'basic',
      'insert',
      [
        'size' => 'col-12',
        'label' => 'E-mail ou usuário',
        'attributes' => 'autocomplete:(username);',
        'name' => 'user',
        'Required' => true,
      ]
    ) .
    input(
      'password',
      'insert',
      [
        'size' => 'col-12',
        'label' => 'Senha',
        'attributes' => 'autocomplete:(current-password);',
        'Required' => true,
      ]
    );

    // if ($login_settings['who_changes_password'] != 'only_admin')
    // {
    //     $login_fields.= "
    //     <div class='col-12 forgot-password'>
    //       <a href='". pg . "/login?forgot-password'>Esqueceu a senha?</a>
    //     </div>";
    // }


    /**
     * Set redirect_to if is necessary.
     */
    if (!empty($_GET['redirect_to']))
    {
        $login_fields.= input(
            'hidden',
            'insert',
            [
                'name' => 'redirect_to',
                'Value' => $_GET['redirect_to'] ?? null,
            ]
        );
    }

    if (!empty($login_settings['save_login']) && $login_settings['save_login'])
    $login_fields.= input(
      'selection_type',
      'insert',
      [
        'type' => 'switch',
        'size' => 'col-6',
        'name' => "save_login",
        'Options' => [[ 'value' => 'true', 'display' => 'Manter conectado' ]]
      ]
    );

    if ($login_settings['who_changes_password'] != 'only_admin')
    {
        $login_fields.= "
        <div class='col-6 forgot-password'>
          <a href='". pg . "/login?forgot-password'>Esqueci minha senha</a>
        </div>";
    }

    if (!empty($login_settings['recaptcha_login']) && $login_settings['recaptcha_login'])
    $login_fields.= input('g-recaptcha', 'insert', [ 'size' => 'col-12' ]);

    $login_fields.= input(
        'submit_button',
        'insert',
        [
            'size' => 'col-12',
            'class' => "btn btn-st",
            'Value' => 'Entrar'
        ]
    );




    /**
     *
     * Form login footer.
     *
     */
    if ($login_settings['signup_page']['active'] && !empty($login_settings['signup_page']['slug']))
    {
        $login_footer.= "
        <div class='col-12 register'>
          <a href='". pg ."/{$login_settings['signup_page']['slug']}'> Crie sua conta hoje</a>
        </div>";
    }


    // Create login page.
    $login_forms['login']['main']+= [
        'form' => [
            'action' => $login_action,
            'fields' => $login_fields,
        ],
        'footer' => $login_footer ?? '',
    ];


    /**
     *
     * Create login page (Only Dev's can login).
     *
     */
    $login_forms['block_system']['main']+= [
        'form' => [
            'action' => $login_action,
            'fields' => $login_fields,
        ],
        'footer' => '<div class="col-12 main-footer-login"><i>Apenas desenvolvedores podem fazer login.</i></div>',
    ];

    $res = array_merge($login_forms, login_form());

    return $res[$key] ?? $res['login'];
}


/**
 * Generates the forms for finding an account and setting a new password.
 *
 * @return array The forms for finding an account and setting a new password.
 */
function login_form()
{
    global $info, $login_settings, $login_forms;

    // Find accounts.
    $find_account_fields =
    input(
      'basic',
      'insert',
      [
        'size' => 'col-12',
        'type' => 'email',
        'label' => 'E-mail ou usuário',
        'Placeholder' => 'Digite o e-mail da conta',
        'name' => 'email',
        'Required' => true,
      ]
    ) .
    input(
        'submit_button',
        'insert',
        [
            'size' => 'col-12',
            'class' => "btn btn-st",
            'Value' => 'Recuperar'
        ]
    );


    $new_password_fields =
    input(
        'hidden',
        'insert',
        [
            'name' => 'key',
            'Value' => $_GET['key'] ?? null,
        ]
    ) .
    input(
      'password',
      'insert', [ 'size' => 'col-12', 'type' => 'new-password', 'Required' => true, ]
    ) . input(
        'submit_button',
        'insert',
        [
            'size' => 'col-12',
            'class' => "btn btn-st",
            'disabled' => true,
            'Value' => 'Editar senha'
        ]
    );


    $footer = "
    <div class='col-12 main-footer-login'>
        <i>Lembrou? <a href='". pg ."/login' title='Login'>Clique aqui</a> para fazer login.</i>
    </div>";


    // Create login page (Only Dev's can login).
    $login_forms['find_account']['main']+= [
        'form' => [
            'action' => rest_api_route_url('forgot-password?find-account'),
            'fields' => $find_account_fields,
        ],
        'footer' => $footer,
    ];

    // Create login page (Only Dev's can login).
    $login_forms['new_password']['main']+= [
        'form' => [
            'action' => rest_api_route_url('forgot-password?new-password'),
            'fields' => $new_password_fields,
        ],
        'footer' => $footer,
    ];

    // SSO por e-mail (magic link) - plugin advanced-login.
    // Cooldown: mesmo tempo (ADVLOGIN_EMAIL_SSO_COOLDOWN_SECONDS) e mesmo
    // estado (sessao) usados pelo backend em advlogin_email_sso_request().
    $sso_cooldown_seconds = defined('ADVLOGIN_EMAIL_SSO_COOLDOWN_SECONDS') ? ADVLOGIN_EMAIL_SSO_COOLDOWN_SECONDS : 30;
    $sso_cooldown_left    = function_exists('advlogin_email_sso_cooldown_remaining') ? advlogin_email_sso_cooldown_remaining() : 0;

    $email_sso_fields =
    input(
      'basic',
      'insert',
      [
        'size' => 'col-12',
        'type' => 'email',
        'label' => 'E-mail',
        'Placeholder' => 'voce@exemplo.com',
        'attributes' => 'autocomplete:(email);',
        'name' => 'email',
        'Required' => true,
      ]
    )
    . (!empty($_GET['redirect_to'])
        ? input('hidden', 'insert', ['name' => 'redirect_to', 'Value' => $_GET['redirect_to']])
        : '')
    . input(
        'submit_button',
        'insert',
        [
            'size' => 'col-12',
            'class' => "btn btn-st",
            'Value' => 'Enviar link de acesso',
            'disabled' => $sso_cooldown_left > 0,
        ]
    )
    . "
    <div class='col-12 advlogin-sso-cooldown'" . ($sso_cooldown_left > 0 ? '' : ' hidden') . ">
      <small>Aguarde <span class='advlogin-sso-cooldown-count'>{$sso_cooldown_left}</span>s para reenviar</small>
    </div>
    <script>
    (function () {
      var form = document.currentScript.closest('form');
      if (!form) return;

      var btn  = form.querySelector('[type=\"submit\"]');
      var wrap = form.querySelector('.advlogin-sso-cooldown');
      var out  = wrap ? wrap.querySelector('.advlogin-sso-cooldown-count') : null;
      if (!btn || !wrap || !out) return;

      var DEFAULT_SECONDS = {$sso_cooldown_seconds};
      var timer = null;

      function start(seconds) {
        clearInterval(timer);
        var remaining = seconds;

        function tick() {
          if (remaining <= 0) {
            clearInterval(timer);
            btn.disabled = false;
            wrap.hidden = true;
            return;
          }
          out.textContent = remaining;
          wrap.hidden = false;
          btn.disabled = true;
          remaining--;
        }

        tick();
        timer = setInterval(tick, 1000);
      }

      if ({$sso_cooldown_left} > 0) start({$sso_cooldown_left});

      form.addEventListener('submit', function () {
        start(DEFAULT_SECONDS);
      });
    })();
    </script>";

    $footer = "
    <div class='col-12 main-footer-login'>
        <a href='". pg ."/login' title='Login'>Tentar outra forma de login</a></i>
    </div>";

    $login_forms['email_sso']['main']+= [
        'form' => [
            'action' => rest_api_route_url('advlogin-email-sso?step=request'),
            'fields' => $email_sso_fields,
        ],
        'footer' => $footer,
    ];

    return $login_forms;
}


/**
 * Renders the HTML for the "Login" modal using the current login form configuration.
 *
 * This function fetches the standard login form definition via `login_form_management('login')`
 * and builds a modal body composed of:
 * - An optional "aside" section (title + description) if provided by the form config
 * - The login `<form>` markup with fields injected as raw HTML
 * - An optional footer area (e.g., register CTA, forgot password link)
 *
 * Finally, the content is wrapped with the `block('modal', ...)` helper which outputs the
 * complete modal markup.
 *
 * Security notes:
 * - `$fields`, `$title`, `$description`, `$footer`, and `$action` are inserted directly into HTML.
 *   This assumes the values returned by `login_form_management()` are trusted and already escaped
 *   where needed. If any of these can be user-controlled, output escaping must be enforced.
 *
 * @return string The rendered modal HTML.
 */
function login_modal()
{
    global $login_forms;

    $login_fields = login_form_management('login');

    $modal_body = '';

    // Text content
    $aside = $login_fields['aside'] ?? null;
    $title = empty($aside['title']) ? '' :
        "<span class='title'>{$aside['title']}</span>";

    $description = empty($aside['description']) ? '' :
        "<span class='description'>{$aside['description']}</span>";

    // Form content
    $action = $login_fields['main']['form']['action'] ?? '';
    $fields = $login_fields['main']['form']['fields'] ?? '';
    $footer = $login_fields['main']['footer'] ?? '';

    $modal_body = "
    <section class='header'>
        {$title}
        {$description}
    </section>

    <form class='form-row' data-send-without-reload data-form-delay='500' action='{$action}' method='post'>
        $fields
    </form>
    {$footer}";

    return block('modal', [
        'class' => 'modal-login',
        'variation' => 'modal_default',
        'attributes' => 'data-modal: (true);',
        'title' => icon('fas fa-user') .' Entre com sua conta',
        'close_button' => true,
        'id' => 'login-modal',
        'body' => $modal_body
    ]);
}
