<?php
if(!isset($seg)) exit;

/**
 * Register a REST API route for manage a crud.
 *
 * This code registers a REST API route named 'manage-crud-system'.
 */
register_rest_route('add-field-model', [
  'methods' => ['POST', 'GET'],
  'callback' => function()
  {
    global $seg;

    feature('page-crud-management-system');

    return add_field_model($_POST ?? []);
  },
  'permission_callback' => '__return_true',
]);


/**
 * Register a REST API route for manage a crud.
 *
 * This code registers a REST API route named 'manage-crud-system'.
 */
register_rest_route('manage-crud-system', [
  'methods' => ['POST', 'GET'],
  'need_login' => true,
  'callback' => function()
  {
    global $seg;

    feature('page-crud-management-system');

    return manage_crud_system($_POST, $_GET['mode']);
  },
  'permission_callback' => '__return_true',
]);


/**
 * Register a REST API route for manage a page.
 *
 * This code registers a REST API route named 'manage-page-system'.
 */
register_rest_route('manage-page-system', [
  'methods' => ['POST', 'GET'],
  'need_login' => true,
  'callback' => function()
  {
    global $seg;

    feature('page-crud-management-system');

    return manage_page_system($_POST, $_GET['mode'], false);
  },
  'permission_callback' => '__return_true',
]);


/**
 * Register a REST API route for manage a page.
 *
 * This code registers a REST API route named 'load-crud-piece'.
 */
register_rest_route('load-crud-piece', [
  'methods' => ['POST', 'GET'],
  'need_login' => true,
  'callback' => function()
  {
    global $seg;

    $piece_id = $_POST['piece_id'] ?? '';
    $mode = $_POST['mode'] ?? 'see';

    $crud_id = get_col("SELECT crud_id FROM tb_cruds WHERE id = '{$piece_id}'");
    $crud_id = $_POST['crud_id'] ?? '';
    $res = [];

    $permission = load_permission('manage-cruds', 'custom');

    if ($permission)
    {
      feature('page-crud-management-system');
      $tables_to_action = select_foreign_key('crud_id');
      $type = 'toast';

      if ($mode == 'see') {
        $type = 'modal';
        $msg = get_crud_piece_to_edit($piece_id);
      }

      elseif ($mode == 'delete')
      {
        $verifyer = delete_record([
          'table'            => 'tb_cruds',
          'id'               => $piece_id,
          'foreign_key'      => 'crud_id',
          'tables_to_action' => $tables_to_action,
          'debug'            => false,
        ]);
        $msg = alert_message('SC_TO_DELETE', $type);
      }

      elseif ($mode == 'duplicate')
      {
        $verifyer = duplicate_record([
          'table'            => 'tb_cruds',
          'id'               => $piece_id,
          'foreign_key'      => 'crud_id',
          'tables_to_action' => $tables_to_action,
          'debug'            => false,
        ]);
        $msg = alert_message('SC_TO_DUPLICATE', $type);
      }

      elseif ($mode == 'insert')
      {
        $msg = alert_message('SC_TO_DUPLICATE', $type);
        $verifyer = manage_crud_system([
          'piece_name' => $_POST['piece_name'] ?? '',
          'type_crud' => $_POST['type_crud'] ?? '',
          'slug' => strtolower(sanitize_string($_POST['piece_name'] ?? '')),
          'crud_id' => $crud_id,
          'status_id' => 4,
        ], 'insert');
      }

      $res['crud_piece_actions'] = load_crud_piece_actions($crud_id);
    }

    else {
      $type = 'toast';
      $msg = alert_message('ER_INVALID_PERMISSION', $type);
    }

    $res+= [
      'code' => 'success',
      'detail' => [
        'type' => $type,
        'msg' => $msg,
      ],
    ];

    return $res;
  },
  'permission_callback' => '__return_true',
]);
