<?php
if (!isset($seg)) exit;

include_once AREAS_PATH .'/admin/include/head.php';
include_once AREAS_PATH .'/admin/include/menu.php';

pageBaseTop();

feature('menu-management');

$counter = 1;
?>

<main class="row m-0" role="main">
<div class="col-12 module pt-0">
  <?php $counter = manage_menu_form('update', $counter); ?>
</div>
</main>

<?php
include_once AREAS_PATH .'/admin/include/script_libs.php';
echo menu_js_call_inputs($counter);
?>
