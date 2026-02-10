<?php
if (!isset($seg)) exit;

include ("include/head.php");
include ("include/menu.php");

$login_settings = $config['login_settings'];

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

// If the system is blocked, set this form.
elseif ($config['block_system'] == 1 && !is_dev()) {
  $form = 'block_system';
}

$login_fields = login_form_management( $form ?? 'login' );
?>

<section class="module login-management">
<div class="main-content row">

  <main class="col-md-10 col-lg-8 col-xl-6">
  <div class="card animate-top">
  <div class="card-body">
  <div class="row">

    <?php if (!empty($login_fields['aside'])): ?>
    <div class="col-lg left-box">
    <div>
      <?= svg($login_fields['aside']['svg'] ?? ''); ?>
      <h3><?= $login_fields['aside']['title'] ?? null ?></h3>
      <p><?= $login_fields['aside']['description'] ?? null ?></p>
    </div>
    </div>
    <?php endif; ?>

    <div class="col-lg right-box">
    <div class="row justify-content-end">

      <div class="col-12" id="return-notification"><?= write_msg_return() ?></div>

      <form class="form-row" data-send-without-reload data-form-delay="500" action="<?= $login_fields['main']['form']['action'] ?>" method="post">
        <?= $login_fields['main']['form']['fields'] ?>
      </form>

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
    </div>

  </div>
  </div>
  </div>
  </main>

</div>
</section>

<?php
// echo login_modal();
?>

<?php include "include/footer.php"; ?>
