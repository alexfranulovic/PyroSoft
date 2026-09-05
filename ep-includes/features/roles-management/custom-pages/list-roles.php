<?php
if (!isset($seg)) exit;

feature('listings');

include_once AREAS_PATH .'/admin/include/head.php';
include_once AREAS_PATH .'/admin/include/menu.php';

pageBaseTop();
?>

<!--main-container-part-->
<main class="row m-0" role="main">
<?php
// Toda a montagem manual (get_roles() + array de linhas + head fixo) foi
// substituída pela custom-listing 'roles' — ver
// features/roles-management/custom-listings/roles.php. Dados, busca,
// paginação e reordenação (orderable) agora são resolvidos sob demanda
// via get-custom-listing / order-record.
echo custom_listing_table('feature/roles-management', 'roles');
?>
</main>

<?php include_once AREAS_PATH .'/admin/include/script_libs.php'; ?>
