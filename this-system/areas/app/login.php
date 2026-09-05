<?php
if (!isset($seg)) exit;

include ("include/head.php");
include ("include/menu.php");

$login_settings = $config['login_settings'];

/**
 * Gate de validacao facial (plugin advanced-login): quando um fluxo de login
 * exige rosto, o usuario e redirecionado para /login?facial_gate=<token>.
 * O desafio so e renderizavel se o plugin facial-input expor facial_verify_prompt().
 */
$facial_gate = $_GET['facial_gate'] ?? null;
if (!empty($facial_gate))
{
    if (function_exists('facial_verify_prompt') && function_exists('token_validate'))
    {
        $gate_row = token_validate([
            'token'   => (string) $facial_gate,
            'type'    => 'facial_gate',
            'consume' => false,
        ]);

        if (!empty($gate_row)) {
            $facial_challenge_html = facial_verify_prompt([
                'user_id'    => (int) $gate_row['user_id'],
                'gate_token' => (string) $facial_gate,
            ]);
        }
    }

    if (empty($facial_challenge_html)) {
        $_SESSION['msg'] = alert_message('ER_INVALID_PERMISSION', 'alert');
    }
}

// Set the 'forgot password' form.
if (isset($_GET['forgot-password']))
{
  $form = 'find_account';
  if (isset($_GET['key']))
  {
    login_verify_token($_GET['key']);
    $form = 'new_password';
  }
}

// SSO por e-mail (magic link) - so quando ligado no painel.
elseif (isset($_GET['email-sso']) && !empty($login_settings['email_sso']))
{
  $form = 'email_sso';
}

// If the system is blocked, set this form.
elseif ($config['block_system'] == 1 && !is_dev()) {
  $form = 'block_system';
}

$login_fields = login_form_management( $form ?? 'login' );
?>

<section>

    <?php if (!empty($login_fields['aside'])): ?>
    <aside class="left-box">
    <div class="branding">
      <a href="<?= site_url() ?>" title="Nós somos: <?= $info['name'] ?>">
        <img class="logotype-light" src="<?= file_url('images/brand', false, 'x-logotype-black.webp') ?>" alt="Nós somos: <?= $info['name'] ?>" loading="lazy"width='173'>
      </a>
      <?= !empty($info['slogan']) ? "<p>{$info['slogan']}</p>" : ''?>
    </div>

    <div class="highlight">
      <p class="leading-highlight"><?= $login_fields['aside']['title'] ?? null ?></p>
      <p class="leading"><?= $login_fields['aside']['description'] ?? null ?></p>
    </div>
    </aside>
    <?php endif; ?>

    <main class="right-box">
    <div class="row justify-content-end">

      <div class="branding">
        <a href="<?= site_url() ?>" title="Nós somos: <?= $info['name'] ?>">
          <img class="logotype-light" src="<?= file_url('images/brand', false, 'x-logotype-st.webp') ?>" alt="Nós somos: <?= $info['name'] ?>" loading="lazy" height="80">
        </a>
        <?= !empty($info['slogan']) ? "<p>{$info['slogan']}</p>" : ''?>
      </div>

      <div class="header">
        <h1><?= $login_fields['main']['title'] ?? null ?></h1>
        <p class="description"><?= $login_fields['main']['description'] ?? null ?></p>
      </div>


      <?php if (!empty($facial_challenge_html)): ?>
      <div class="col-12 facial-gate-challenge"><?= $facial_challenge_html ?></div>
      <?php else: ?>
      <form class="form-row" data-send-without-reload data-form-delay="500" action="<?= $login_fields['main']['form']['action'] ?>" method="post">
        <?= $login_fields['main']['form']['fields'] ?>
      </form>
      <?php endif; ?>

      <?= $login_fields['main']['footer'] ?? null ?>

      <?php
      $social_media = [
        'class' => 'col-12',
        'align' => 'center',
        'subtitle' => "Nos siga nas redes sociais",
        'content' => $info['social_media'],
      ];
      // echo !isset($_GET['forgot-password']) ? block('social_media', $social_media) : null;
      ?>

    </div><!--.row-->
    </main>

</section>

<?php
// echo login_modal();
?>

<?php include "include/footer.php"; ?>


<div class="toast-container" id="return-notification"><?= write_msg_return() ?></div>
