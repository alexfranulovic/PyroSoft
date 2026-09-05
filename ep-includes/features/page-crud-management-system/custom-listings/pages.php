<?php
if (!isset($seg)) exit;

/**
 * feature/page-crud-management/custom-listings/pages.php
 *
 * The exact listing your original pages.php hand-built, translated into
 * this format — same columns, same formatting functions, same action
 * buttons. Loaded on demand by load_custom_listing('feature/page-crud-management', 'pages').
 */

$pages_controller = 'redirect=true&table=tb_pages&permission_id=manage-pages&foreign_key=page_id&id=';

return [
    'crud_panel' => [
        'show_panel' => true,
        'show_name'  => false,
    ],
    'table'       => 'tb_pages',
    'get_data_by' => 'table',
    'fields'      => [
        ['name' => 'id', 'label' => 'ID'],
        [
            'name'          => 'title',
            'label'         => 'Título',
            'function_view' => fn($value, $row) =>
                "<a href='" . get_url_page($row['slug'], 'full') . "' target='_blank'>{$value} "
                . icon('fas fa-arrow-up-right-from-square') . "</a>",
        ],
        ['name' => 'page_area', 'label' => 'Área'],
        ['name' => 'is_public', 'label' => 'Pública', 'function_view' => 'yes_or_no'],
        ['name' => 'access_count', 'label' => 'Acessos'],
        [
            'name'          => 'status_id',
            'label'         => 'Status',
            'function_view' => fn($value, $row) => status_buttons($row['id'], $value, 'tb_pages'),
        ],
        ['name' => 'page_type', 'label' => 'Escalão', 'function_view' => 'degree_page'],
    ],
    'actions' => [
        'order'     => ['permission' => false, 'url' => $pages_controller],
        'duplicate' => ['permission' => true, 'url' => $pages_controller],
        'edit'      => ['permission' => load_permission('page-manager'), 'url' => get_url_page('page-manager', 'full')],
        'delete'    => ['permission' => true, 'url' => $pages_controller],
    ],
    'insert_button' => [
        'permission' => load_permission('page-manager'),
        'title'      => 'Cadastrar',
        'url'        => get_url_page('page-manager', 'full'),
    ],
];
