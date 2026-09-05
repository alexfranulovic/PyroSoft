<?php
if (!isset($seg)) exit;

include_once AREAS_PATH .'/admin/include/head.php';
include_once AREAS_PATH .'/admin/include/menu.php';

pageBaseTop();

feature('listings');
?>
<!--main-container-part-->
<main class="row m-0" role="main">
<?php
echo custom_listing_table('feature/page-crud-management-system', 'pages');
?>
</main>
<?php include_once AREAS_PATH .'/admin/include/script_libs.php'; ?>
