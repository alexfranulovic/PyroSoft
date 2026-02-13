<?php
if (!isset($seg)) exit;

$page = [ 'title' => 'Error 404', 'slug' => 'error404', 'seo' => [] ];

include_once 'include/head.php';
include_once 'include/menu.php';

pageBaseTop();
?>

<main class="error404" role="main">
<div class="container">
  <div class="row justify-content-center">  
    <div class="col-12">
      <h1 class="display-1 text-center">404</h1>
      <h3 class="text-center">Page not found.</h3>
      <p class="text-center">The page you were looking for could not be found. :(</p>
      <p class="text-center"><a class="btn btn-nd ?>" title="Go back" href="<?= pg ?>/admin/administrativo">Go back</a></p>
    </div>
  </div>
</div>
</main>

<?php include_once 'include/script_libs.php'; ?>
