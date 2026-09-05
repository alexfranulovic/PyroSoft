<?php
if (!isset($seg)) exit;

/**
 * "Histórico de pagamentos" -- customer-facing page wrapping
 * custom-listings/payment-history.php, which does the actual
 * $current_user['id']-scoped querying. Front-end template flavor (same as
 * custom-pages/payment-methods.php), not the admin AREAS_PATH flavor used
 * by custom-pages/list-orders.php -- this belongs to the customer's own
 * account area, not the admin panel.
 */

// add_asset('head', "<link rel='stylesheet' href='". plugin_path('/pyrosales/assets/styles/checkout.css', 'url') ."'>");
add_asset('head', "<link rel='stylesheet' href='". plugin_path('/pyrosales/assets/styles/payment-methods.css', 'url') ."'>");

feature('listings');

include_once AREAS_PATH .'/app/include/head.php';
include_once AREAS_PATH .'/app/include/menu.php';
?>

<main class="pt-0 container payment-history-page" role="main">

<div class="page-header">
    <h1>Histórico de pagamentos</h1>
</div>

<?php echo custom_listing_table('plugin/pyrosales', 'payment-history'); ?>

</main>

<?php include_once AREAS_PATH .'/app/include/footer.php'; ?>
