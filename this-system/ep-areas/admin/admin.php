<?php
if (!isset($seg)) exit;

include_once 'include/head.php';
include_once 'include/menu.php';

pageBaseTop();

$content = "icon(fas fa-warning) &nbsp; This is the admin`s demo page.";

echo alert_message("IF_UNLOADED_FORM", 'toast');

echo block('alert',
[
    'body' => $content,
    'variation' => 'alert-3',
    'close_button' => false,
    'color' => 'info'
]);
?>

<section class="row mb-3">

    <?php
    $params = [
        'title' => icon('fas fa-sack-dollar') . ' Current MRR',
        'obs' => 'This is the total revenue you are currently generating from active subscriptions',
        'number' => 76590,
        'last_number' => 75000,
        'description' => 'Base anterior:',
        'small' => 'Since last week',
        'formatter' => 'BRL',
    ];

    echo insight_card($params);
    echo insight_card($params);
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
