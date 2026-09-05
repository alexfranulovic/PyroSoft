<?php
if (!isset($seg)) exit;

include_once AREAS_PATH .'/admin/include/head.php';
include_once AREAS_PATH .'/admin/include/menu.php';

pageBaseTop();

feature('listings');
?>

<main class="row m-0" role="main">
<section class="col-12 module">
  <?php
  echo render_plans_segment_tables();
  ?>
</section>
</main>

<?php include_once AREAS_PATH .'/admin/include/script_libs.php'; ?>
