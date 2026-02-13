<nav class="navbar">

  <span class="blank-space"></span>

  <button class="btn btn-offcanvas" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-expanded="false" aria-label="Toggle navigation">
    <?= icon('fas fa-bars') ?>
  </button>

  <div class="tools">

    <?php if (!empty($area['allow_change_color_mode']) && $area['allow_change_color_mode']): ?>
    <div class='dropdown theme-color-mode'>
      <button class='btn dropdown-toggle' type='button' aria-expanded='false' data-bs-toggle='dropdown' aria-label='Toggle theme (auto)'>
        <?= icon( $area_color_icon[$storage_theme_color]) ?>
      </button>
      <ul class='dropdown-menu dropdown-menu-end'>
        <button type="button" class="dropdown-item" data-bs-theme-value="light" aria-pressed="true"><?= icon('fas fa-sun') ?> Claro</button>
        <button type="button" class="dropdown-item" data-bs-theme-value="dark" aria-pressed="false"><?= icon('fas fa-moon') ?> Escuro</button>
        <button type="button" class="dropdown-item" data-bs-theme-value="auto" aria-pressed="false"><?= icon('fas fa-circle-half-stroke') ?> Sistema</button>
      </ul>
    </div>
    <div class="vr"></div>
    <?php endif; ?>


    <?php if (is_user_logged_in()) : ?>
    <div class="dropdown user-dropdown-info">

      <?php
      $name        = explode(' ', $current_user['first_name']);
      $placeholder = "preview_img.jpg";
      $path        = pg ."/uploads/images/users/";

      $user_image  = !empty($current_user['imagem'])
        ? "{$current_user['id']}/{$current_user['imagem']}"
        : $placeholder;
      ?>

      <a href="#" class="info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
        <img
            src="<?= $path . $user_image ?>"
            alt="Nós somos: <?= $name[0] ?>"
            loading="lazy"
            width="32"
            height="32"
            onerror="this.src='<?= $path . $placeholder ?>'"
        >
        <span><?= $name[0] ?></span>
      </a>

      <ul class="dropdown-menu dropdown-menu-end">
        <!-- <a class="dropdown-item" href="<?= pg .'/admin/visualizar-usuario?id='.$current_user['id'] ?>" title="Ver seu perfil"><?= icon('fas fa-user') ?> Perfil</a> -->
        <a class="dropdown-item" href="<?= logout_url() ?>" title="Sair"><?= icon('fas fa-sign-out-alt') ?> Sair</a>
      </ul>

    </div>
    <?php endif; ?>

  </div>

</nav>
