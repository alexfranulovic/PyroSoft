<?php
if (!isset($seg)) exit;

$Table       = 'tb_cron_events';

include_once 'include/head.php';
include_once 'include/menu.php';

pageBaseTop();


$permission = load_permission('manage-schedules-events', 'custom');

$delete = [
    'permission' => $permission,
    'url' => "redirect=true&permission_id=manage-schedules-events&table={$Table}&id=",
];
?>

<!--main-container-part-->
<main class="row m-0" role="main">
<?php
foreach (all_cron_available() as $data)
{
    $row = $table_actions = [];
    $row[] = $data['slug'];
    $row[] = $data['mode'] ?? 'system';
    $row[] = $data['hook'];
    $row[] = get_next_execution_info($data['timestamp']);
    $row[] = get_recurrence_key($data['recurrence'] ?? 0);

    $table_actions['delete'] = $delete;
    $row[] = build_table_actions($table_actions, $data['id']);

    $body[] = $row;
}

// if ($permission)
// {
//     $hooks_out[] = [
//         'attr' => 'manage-custom-permission:(insert);',
//         'title' => 'Cadastrar',
//         'color' => 'outline-success',
//         'pre_icon' => 'fas fa-plus',
//     ];
// }

echo table([
    'data_table' => true,
    'crud_panel' => [ 'hooks_out' => $hooks_out ?? null ],
    'head' => [ 'Slug', 'Mode', 'Hook', 'Próxima execução', 'Recorrência', 'Ações' ],
    'body' => $body ?? [],
]);

?>
</main>

<?php
include_once 'include/script_libs.php';
?>
