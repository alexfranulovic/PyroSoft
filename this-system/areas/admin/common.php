<?php
if (!isset($seg)) exit;

include_once 'include/head.php';
include_once "include/menu.php";

pageBaseTop();
?>

<main class="row m-0" role="main">
<?= page_content( ) ?> 
</main>

<?php include_once "include/script_libs.php"; ?>