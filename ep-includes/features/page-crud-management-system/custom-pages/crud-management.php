<?php
if (!isset($seg)) exit;

include_once AREAS_PATH .'/admin/include/head.php';
include_once AREAS_PATH .'/admin/include/menu.php';

pageBaseTop();

feature('page-crud-management-system');

$counter = 1;
?>

<main class="row m-0" role="main">
<div class="col-12 module pt-0">
  <?php $counter = manage_crud_form('update', $counter); ?>
</div>
</main>

<?php
include_once AREAS_PATH .'/admin/include/script_libs.php';
echo js_call_inputs('crud', $counter);
?>
