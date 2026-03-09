<?php
if (!isset($seg)) exit;

include_once AREAS_PATH .'/admin/include/head.php';
include_once AREAS_PATH .'/admin/include/menu.php';

$id = id_by_get();

pageBaseTop();
?>

<!--main-container-part-->
<main class="row m-0" role="main">
<?php
$manager = 'order-manager';


if (load_permission($manager))
$hooks_out[] = [
    'title' => 'Cadastrar',
    'url'  => get_url_page($manager, 'full'),
    'color' => 'outline-success',
    'pre_icon' => 'fas fa-plus',
];

$order = get_order($id);
dump($order);

?>
</main>

<?php include_once AREAS_PATH .'/admin/include/script_libs.php'; ?>
