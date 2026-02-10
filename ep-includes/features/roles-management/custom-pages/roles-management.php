<?php
if (!isset($seg)) exit;

include_once AREAS_PATH .'/admin/include/head.php';
include_once AREAS_PATH .'/admin/include/menu.php';

pageBaseTop();

feature('roles-management');

$counter = 1;
?>

<main class="row m-0" role="main">
<section class="col-12 module">
  <?php $counter = manage_roles_form('update', $counter); ?>
</section>
</main>

<?php include_once AREAS_PATH .'/admin/include/script_libs.php'; ?>
