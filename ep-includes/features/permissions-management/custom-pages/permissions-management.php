<?php
if (!isset($seg)) exit;

feature('permissions-management');

$Table       = 'tb_user_role_permissions';
$foreign_key = 'permission_id';


include_once AREAS_PATH .'/admin/include/head.php';
include_once AREAS_PATH .'/admin/include/menu.php';

pageBaseTop();


$permission = load_permission('manage-permissions', 'custom');

$delete = [
    'permission' => $permission,
    'url' => "redirect=true&table={$Table}&foreign_key={$foreign_key}&permission_id=manage-permissions&id=",
];
$edit = [
    'permission' => $permission,
    'attr' => 'manage-custom-permission:(update);'
];

?>

<!--main-container-part-->
<main class="row m-0" role="main">
<?php
foreach (get_permissions() as $data)
{
    $row = $table_actions =[];
    $row[] = $data['type'];
    $row[] = $data['name'];
    $row[] = $data['slug'];

    if ($data['type'] == 'Custom')
    {
        $table_actions['delete'] = $delete;
        $table_actions['edit'] = $edit;
        $table_actions['edit']['attr'].= "permission-type:({$data['type']});";
        $row[] = build_table_actions($table_actions, $data['id']);
    }

    else {
        $table_actions['edit'] = $edit;
        $table_actions['edit']['attr'].= "permission-type:({$data['type']});";
        $row[] = build_table_actions($table_actions, $data['id']);
    }

    $body[] = $row;
}

if ($permission)
{
    $hooks_out[] = [
        'attr' => 'manage-custom-permission:(insert);',
        'title' => 'Cadastrar',
        'color' => 'outline-success',
        'pre_icon' => 'fas fa-plus',
    ];
}

echo table([
    'data_table' => true,
    'crud_panel' => [
        'show_panel' => true,
        'hooks_out' => $hooks_out ?? null,
    ],
    'head' => [ 'Tipo', 'Nome', 'Slug', 'Ações' ],
    'body' => $body ?? [],
]);

?>
</main>

<?php
add_asset('footer', "<script src='". feature_path('/permissions-management/assets/scripts/permissions-management.js', 'url') ."' defer></script>");
include_once AREAS_PATH .'/admin/include/script_libs.php';
?>
