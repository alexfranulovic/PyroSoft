<?php
global $config, $page;
flush();
?>
<head>

    <link rel="shortcut icon" type="image/png" href="<?= $info['favicon'] ?>"/>
    <title><?= $page['title'] ?? $info['title'] ?></title>
    <meta charset="<?= $config['charset'] ?? '' ?>">
    <meta name="rating" content="<?= $config['rating'] ?? '' ?>">
    <meta name="viewport" content="<?= $config['viewport'] ?? '' ?>">

    <!-- URI's -->
    <link rel="canonical" href="<?= (!empty($page['seo']['canonical']) ?: canonical) ?>">
    <meta rel="home" href="<?= base_url ?>" title="<?= $info['title'] ?>">
    <meta rel="index" href="<?= base_url ?>" title="<?= $info['title'] ?>">
    <meta property="site:BASE_URL" content="<?= base_url ?>">
    <meta property="site:REST_API_BASE_ROUTE" content="<?= REST_API_BASE_ROUTE ?>">
    <meta property="site:max_upload_size" content="<?= $max_upload ?>">

    <?= generate_SEO_meta() ?>

    <link rel="stylesheet" href="<?= base_url ?>/dist/styles/libs.css">
    <link rel="stylesheet" href="<?= base_url ?>/dist/styles/app.css">
    <!--  <link rel="manifest" href="<?= base_url ?>/euphoric-pwa/manifest.json"> -->

    <?= head() ?>
</head>
