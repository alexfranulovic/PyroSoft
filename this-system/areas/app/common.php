<?php
if (!isset($seg)) exit;

include "include/head.php";
include "include/menu.php";
// echo block('breadcrumbs');
?>

<main class="row m-0" role="main">    
<?= page_content() ?>
</main><!--main-->

<?php include "include/footer.php"; ?>