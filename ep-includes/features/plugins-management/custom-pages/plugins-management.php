<?php
if (!isset($seg)) exit;

include_once AREAS_PATH .'/admin/include/head.php';
include_once AREAS_PATH .'/admin/include/menu.php';

pageBaseTop();

$permission = load_permission('manage-plugins', 'custom');
?>

<!--main-container-part-->
<main class="row m-0" role="main">
<?php
foreach (list_plugins() as $data)
{
    $row = $table_actions = [];
    $row[] = $data['name'];
    $row[] = $data['description'];
    $row[] = $data['version'];
    $row[] = $data['author'];
    $row[] = $data['activated'] ? 'Yes' : 'No';

    if (!$permission) {
        $row[] = '-';
    }

    else
    {
        if ($data['activated'])
        {
            $table_actions[] = [
                'type' => 'button',
                'pre_icon' => 'fas fa-toggle-off',
                'title' => 'Disable',
                'color' => 'btn-info',
                'attr' => "plugin-name:({$data['slug']});plugin-action:(disable);",
            ];

            $row[] = table_actions($table_actions, []);
        }

        else
        {
            $table_actions[] = [
                'type' => 'button',
                'pre_icon' => 'fas fa-toggle-on',
                'title' => 'Enable',
                'color' => 'btn-info',
                'attr' => "plugin-name:({$data['slug']});plugin-action:(enable);",
            ];

            $table_actions[] = [
                'type' => 'button',
                'pre_icon' => 'fas fa-x',
                'title' => 'Uninstall',
                'color' => 'btn-outline-info',
                'attr' => "plugin-name:({$data['slug']});plugin-action:(uninstall);",
            ];

            $row[] = table_actions($table_actions, []);
        }
    }

    $body[] = $row;
}

echo table([
    'head' => [
        'Name',
        'Description',
        'Version',
        'Author',
        'Activated',
        'Ações'
    ],
    'body' => $body ?? [],
]);

?>
</main>

<?php
add_asset('footer', "<script src='". feature_path('/plugins-management/assets/scripts/plugins-management.js', 'url') ."' defer></script>");
include_once AREAS_PATH .'/admin/include/script_libs.php';
?>
