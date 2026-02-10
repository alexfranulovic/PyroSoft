<?php
$navbar_format = $page['page_settings']['navbar']['format'] ?? ($config['branding']['navbar']['format'] ?? 'full');
$navbar_style  = $page['page_settings']['navbar']['style'] ?? ($config['branding']['navbar']['style'] ?? 'fixed');
?>

<body>

<?php if ($navbar_format == 'full') : ?>
<nav class="navbar full navbar-<?= $navbar_style ?>">

    <div>
        <a class="navbar-brand" href="<?= $config['main_page']['url'] ?>" title="<?= $info['name'] ?>">
            <img class="logotype-light" src="<?= file_url('images/brand', false, 'logotype-black.webp') ?>" alt="Nós somos: <?= $info['name'] ?>" loading="lazy" height="36">
            <img class="logotype-dark" src="<?= file_url('images/brand', false, 'logotype-white.webp') ?>" alt="Nós somos: <?= $info['name'] ?>" loading="lazy" height="36">
            <!--
            <span class="d-none d-lg-block d-xl-block align-middle" style="text-transform: none;"><?= $info['name'] ?></span>
            -->
        </a>
    </div>

    <div>
        <a class="#" href="<?= $config['main_page']['url'] ?>" title="<?= $info['name'] ?>">
            <img src="<?= pg ?>/uploads/images/brand/isotype-white.png" alt="Nós somos: <?= $info['name'] ?>" loading="lazy" width="24" height="24">
        </a>
    </div>

</nav>

<?php elseif ($navbar_format == 'medium') : ?>
<nav class="navbar medium navbar-<?= $navbar_style ?>">

    <a class="navbar-brand" href="<?= $config['main_page']['url'] ?>" title="<?= $info['name'] ?>">
        <img class="logotype-light" src="<?= file_url('images/brand', false, 'logotype-black.webp') ?>" alt="Nós somos: <?= $info['name'] ?>" loading="lazy" height="36">
        <img class="logotype-dark" src="<?= file_url('images/brand', false, 'logotype-white.webp') ?>" alt="Nós somos: <?= $info['name'] ?>" loading="lazy" height="36">
    </a>

</nav>
<?php endif; ?>
<!-- END nav -->
