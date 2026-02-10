<?php
flush();
?>
<head>
    <link rel="icon" type="image/png" href="<?= $info['favicon'] ?>"/>
    <title><?= $page['title'].' | '.$info['title'] ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"> 
    <link rel="canonical" href="<?= canonical ?>">
    <meta name="robots" content="<?= $page['robot'] ?? '' ?>" />
    <meta name="keywords" content="<?= $page['keywords'] ?? '' ?>">
    <meta name="description" content="<?= $page['description'] ?? '' ?>">
    <meta name="author" content="<?= $page['author'] ?? '' ?>">
    <meta property="site:BASE_URL" content="<?= base_url ?>">
    <meta property="site:REST_API_BASE_ROUTE" content="<?= REST_API_BASE_ROUTE ?>">
    <meta property="site:max_upload_size" content="<?= $max_upload ?>">

    <link rel="stylesheet" href="<?= base_url ?>/dist/styles/libs.css">
    <link rel="stylesheet" href="<?= base_url ?>/dist/styles/admin.css">

    <?= head() ?>
</head>
