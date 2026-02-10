<?php
if (!isset($seg)) exit;

$Table       = 'tb_pages';
$foreign_key = 'page_id';

$result = query_it("SELECT * FROM tb_pages");

include_once AREAS_PATH .'/admin/include/head.php';
include_once AREAS_PATH .'/admin/include/menu.php';

pageBaseTop();
?>

<!--main-container-part-->
<main class="row m-0" role="main">
<?php
$edit = 'page-manager';
$controller = "redirect=true&table=$Table&permission_id=manage-pages&foreign_key={$foreign_key}&id=";


$table_actions = [
    'order'       => [ 'permission' => false, 'url' => $controller ],
    'duplicate'   => [ 'permission' => true, 'url' => $controller ],
    'edit'        => [ 'permission' => load_permission($edit), 'url' => get_url_page($edit, 'full') ],
    'delete'      => [ 'permission' => true, 'url' => $controller ],
];

if (load_permission('page-manager'))
$hooks_out[] = [
    'title' => 'Cadastrar',
    'url'  => get_url_page('page-manager', 'full'),
    'color' => 'outline-success',
    'pre_icon' => 'fas fa-plus',
];

while ($data = mysqli_fetch_assoc($result->mysqli))
{
    $row = [];
    $row[] = $data['id'];
    $row[] = "<a href='". get_url_page($data['slug'], 'full') ."' target='_blank'>{$data['title']} ".icon('fas fa-arrow-up-right-from-square')."</a>";
    $row[] = $data['page_area'];
    $row[] = yes_or_no($data['is_public']);
    $row[] = $data['access_count'];
    $row[] = status_buttons($data['id'], $data['status_id'], 'tb_pages');
    $row[] = degree_page($data['page_type']);

    $row[] = build_table_actions($table_actions, $data['id']);

    $body[] = $row;
}

$table = [
    'data_table' => true,
    'crud_panel' => [
        'show_panel' => true,
        'hooks_out' => $hooks_out ?? [],
    ],
    'head' => [
        'ID', 'Título', 'Área', 'Pública', 'Acessos', 'Status', 'Escalão', 'Ações',
    ],
    'body' => $body ?? [],
];
echo table($table);

?>
</main>

<?php include_once AREAS_PATH .'/admin/include/script_libs.php'; ?>
