<?php
if (!isset($seg)) exit;

include_once 'include/head.php';
include_once 'include/menu.php';

pageBaseTop();

$content = "icon(fas fa-warning) &nbsp; This is the admin`s demo page.";

// echo "<div class='toast-container'>". alert_message("IF_UNLOADED_FORM", 'toast').'</div>';

echo block('alert',
[
    'body' => $content,
    'variation' => 'alert-3',
    'close_button' => false,
    'color' => 'warning'
]);
?>

<section class="row mb-3">

    <?php
    $params = [
        'title' => icon('fas fa-sack-dollar') . ' Current MRR',
        'obs' => 'This is the total revenue you are currently generating from active subscriptions',
        'data' => 76590.66,
        'last_data' => 75000,
        'description' => 'Base anterior:',
        'small' => 'Since last week',
        'formatter' => 'BRL',
    ];

    echo block('insight_card', $params);
    echo block('insight_card', $params);
    ?>

</section><!--row -->

<section class="row">

    <div class="col-lg-6 ">
        <?= crud_piece( ['piece_id' => 'main-summary-list-users'] ) ?>
    </div><!--Messages -->

    <div class="col-lg-6 ">
        <?= crud_piece( ['piece_id' => 'main-summary-list-users'] ) ?>
    </div><!--Messages -->

</section><!--row-->

<?php include_once 'include/script_libs.php'; ?>
