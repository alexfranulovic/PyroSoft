<?php
if (!isset($seg)) exit;

include_once 'include/head.php';
include_once "include/menu.php";

$user_id = id_by_get();

pageBaseTop();
?>

<main class="row m-0" role="main">
    <?php
    echo render_user_turbos_box($user_id);
    echo crud_piece( [ 'piece_id' => 'main-edit-user' ] );
    ?>
</main>

<?php include_once "include/script_libs.php"; ?>
