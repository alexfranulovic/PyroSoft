<?php
if (!isset($seg)) exit;


$manager     = 'role-management';
$permission  = 'role-management';
$table       = 'tb_user_roles';
$foreign_key = 'role_id';
$controller = "redirect=true&table={$table}&permission_id={$permission}&foreign_key={$foreign_key}&id=";

return [
    'title'       => 'Níveis de Acesso',
    'table'       => $table,
    'get_data_by' => 'table',
    'orderable'   => true,

    'fields' => [
        [ 'name' => 'name', 'label' => 'Nome' ],
        [ 'name' => 'id',   'label' => 'ID'   ],
    ],

    'actions' => [
        'duplicate' => [ 'permission' => true, 'url' => $controller ],
        'edit'      => [ 'permission' => load_permission($manager), 'url' => get_url_page($manager, 'full') ],
        'delete'    => [ 'permission' => true, 'url' => $controller ],
    ],

    'insert_button' => [
        'permission' => load_permission($manager),
        'title'      => 'Cadastrar',
        'url'        => get_url_page($manager, 'full'),
        'color'      => 'outline-success',
        'pre_icon'   => 'fas fa-plus',
    ],

    'crud_panel' => [
        'show_panel' => true,
    ],
];
