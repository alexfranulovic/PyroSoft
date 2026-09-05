<?php
if (!isset($seg)) exit;

include_once AREAS_PATH .'/admin/include/head.php';
include_once AREAS_PATH .'/admin/include/menu.php';

pageBaseTop();

feature('listings');

load_subscription_form();
?>

<main class="row m-0" role="main">
<section class="col-12 module">
  <?php
  manage_subscriptions_form('update');
  ?>
</section>
</main>

<?php include_once AREAS_PATH .'/admin/include/script_libs.php'; ?>
