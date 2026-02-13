<?php
if(!isset($seg)) exit;

/** Load Plugin APIs **/
load_plugins('api');

/** This system APIs **/
load_this_system_functions('api');

/** Load Features APIs **/
feature('all', 'api');



/**
 * Register a REST API route for user login.
 *
 * This code registers a REST API route named 'user-login' for handling user login requests.
 */
register_rest_route('log-it', [
  'methods' => 'POST',
  'callback' => function()
  {
    try
    {
      $raw = file_get_contents('php://input');
      $data = json_decode($raw, true);

      if (!$data) {
        throw new Exception("Invalid JSON in JS log.");
      }

      app_log(
        $data['level'] ?? 'info',
        "[JS] " . ($data['message'] ?? 'No message'),
        [
          'context_js' => $data['context'] ?? [],
          'js_url'     => $data['url'] ?? null,
          'user_agent' => $data['userAgent'] ?? null,
          'client_time'=> $data['time'] ?? null,
          'origin'     => 'javascript'
        ]
      );

      return json_encode(['code' => 'success']);
    }

    catch (Throwable $e) {
      app_log('error', 'Fail to proccess endpoint "log-it"', [
        'exception' => $e
      ]);
      return json_encode(['code' => 'error', 'msg' => $e->getMessage()]);
    }
  },
  'permission_callback' => '__return_true',
]);


/**
 * Register a REST API route for user login.
 *
 * This code registers a REST API route named 'user-login' for handling user login requests.
 */
register_rest_route('user-login', [
  'methods' => 'POST',
  'callback' => function()
  {
    if (!empty($_POST['user']) && !empty($_POST['password']))
    {
      return user_login([
        'user' => $_POST['user'],
        'password' => $_POST['password'],
        'redirect_uri' => $_POST['redirect_to'] ?? 'role_page',
      ]);
    }

  },
  'permission_callback' => '__return_true',
]);


/**
 * Registers a REST route for user logout.
 *
 * This code registers a REST API route named 'user-logout' for handling user logout requests.
 */
register_rest_route('user-logout', [
  'methods' => 'GET',
  'callback' => function()
  {
    logout();

    $redirect = site_url('/login');
    header("Location: {$redirect}");
    exit;
  },
  'permission_callback' => '__return_true',
]);


register_rest_route('form-processor', [
  'methods' => ['POST', 'GET'],
  'callback' => function()
  {
    return form_processor($_POST ?? []);
  },
  'permission_callback' => '__return_true',
]);



register_rest_route('forgot-password', [
  'methods' => ['POST', 'GET'],
  'callback' => function()
  {
    if (isset($_GET['find-account'])) {
      return login_forgot_password($_POST['email']);
    }

    elseif (isset($_GET['new-password'])) {
      return login_update_password($_POST);
    }
  },
  'permission_callback' => '__return_true',
]);


register_rest_route('switch-status-button', [
  'methods' => 'POST',
  'callback' => function()
  {
    global $current_user;

    if (!empty($_POST['id']) && !empty($_POST['type']) && !empty($_POST['mode']))
    {
      $id         = $_POST['id'];
      $type       = $_POST['type'];
      $table      = $_POST['mode'];

      $status = $type();

      // Define the $table
      if (!empty($id) && is_table($table))
      {
        $row = get_result("SELECT id, status_id FROM $table WHERE id = '$id' LIMIT 1");
        $status_id = $row['status_id'];

        $total = count($status);

        $status = ($status_id >= $total)
          ? 1
          : $status_id+1;

        $query = "UPDATE $table SET status_id='$status', updated_at=NOW() WHERE id = '$id'";
        query_it($query);

        if (affected_rows()) {
          $code = 'success';
          $msg  = "SC_STATUS";
        }

        else {
          $code = 'error';
          $msg = "ER_STATUS";
        }
      }

      else {
        $code = 'error';
        $msg = "ER_UNDEFINED_ERROR";
      }

    }

    return [
      'code' => $code,
      'detail' => [
        'type' => 'toast',
        'msg' => alert_message($msg, 'toast'),
      ],
      'button' => status_buttons($id, $status, $table, $type),
    ];

  },
  'permission_callback' => '__return_true',
]);

/**
 * Show tables with that foreign key and return them in modal to action.
 *
 * @return array with the input.
 */
register_rest_route('crud-data-controller', [
  'methods' => 'POST',
  'callback' => function()
  {
    global $tables;

    if (!empty($_POST['controller']))
    {
      $controller = $_POST['controller'];

      if ($controller == 'duplicate' OR $controller == 'delete')
      {
        $form = '';
        if (!empty($_POST['foreign_key']))
        {
          $Foreign_key = $_POST['foreign_key'];
          $child_tables = (object) select_foreign_key( $Foreign_key );

          $count = 0;
          foreach($child_tables as $table)
          {
            $Options[] = [
              'value' => $table,
              'display' => (!empty($tables[$table]) ? $tables[$table] : $table),
            ];
            $count++;
          }

          $input = ($count > 0)
            ? input(
              'selection_type',
              'insert',
              [
                'size' => 'col-12',
                'type' => 'checkbox',
                'label' => 'Agir com registros relacionados na(s) tabela(s):',
                'name' => 'tables_to_action[]',
                'Options' => $Options,
              ])
            : null;

          $form = "<form class='row'>$input</form>";
        }

        $footer = "<button type='button' class='btn btn-success' data-bs-dismiss='modal'>Cancelar</button>
        <button type='submit' class='btn btn-danger text-white'>Confirmar</button>";

        if ($controller == 'duplicate')
        {
          $modal = [
            'variation' => 'modal_default',
            'form' => [
              'active' => true,
              'attributes' => "action: (".($_POST['action'] ?? '')."); method: (POST); data-controller-form:();",
            ],
            'attributes' => 'data-modal:(); data-duplicate-modal:();',
            'id' => 'data-controller',
            'title' => 'DUPLICAR REGISTRO',
            'close_button' => true,
            'body' => 'Tem certeza de que deseja **DUPLICAR** o registro selecionado?'. $form,
            'footer' => $footer
          ];
        }

        else {
          $modal = [
            'variation' => 'modal_default',
            'form' => [
              'active' => true,
              'attributes' => "action: (".($_POST['action'] ?? '')."); method: (POST); data-controller-form:();",
            ],
            'attributes' => 'data-modal:(); data-delete-modal:();',
            'id' => 'data-controller',
            'title' => 'EXCLUIR REGISTRO',
            'close_button' => true,
            'body' => 'Tem certeza de que deseja **EXCLUIR** o registro selecionado?<br><br>Essa ação é **IRREVERSSÍVEL**! É recomendável que faça backup antes.'. $form,
            'footer' => $footer
          ];
        }
      }

      elseif ($controller == 'truncate')
      {
        $modal = [
          'variation' => 'modal_default',
          'attributes' => 'data-modal:(); data-truncate-modal:();',
          'id' => 'data-controller',
          'title' => 'LIMPAR TODOS OS REGISTROS',
          'close_button' => true,
          'body' => 'Tem certeza de que deseja **LIMPAR TODOS** os registros?<br><br>Essa ação é **IRREVERSSÍVEL**! É recomendável que faça backup antes.',
        ];
      }
    }

    return [
      'code' => 'success',
      'detail' => [
        'type' => 'modal',
        'msg' => block('modal', $modal),
      ]
    ];
  },
  'permission_callback' => '__return_true',
]);


/**
 * Register a REST API route for duplicate record.
 *
 * This code registers a REST API route named 'duplicate-record'.
 */
register_rest_route('duplicate-record', [
  'methods' => 'POST',
  'callback' => function()
  {
    $id               = $_GET['id'] ?? null;
    $permission_id    = $_GET['permission_id'] ?? null;
    $tables_to_action = $_POST['tables_to_action'] ?? null;

    $debug = (is_dev() && isset($_GET['debug'])) ? true : false;

    if (isset($_GET['crud']))
    {
      $crud_id = (int) $_GET['crud'];

      // If permission is denied, short-circuit with system-standard JSON
      if (!load_permission($crud_id, 'duplicate')) {
        return invalid_permission_response();
      }

      // With permission granted, resolve CRUD data
      $crud           = get_result("SELECT table_crud, foreign_key, pages_list FROM tb_cruds WHERE id = {$crud_id}");
      $pages_list     = $crud['pages_list'];
      $table          = $crud['table_crud'];
      $foreign_key    = $crud['foreign_key'] ?? null;
      $referer        = $_SERVER['HTTP_REFERER'] ?? null;

      $redirect = !empty($pages_list->list_pg)
        ? get_url_page($pages_list->list_pg, 'full')
        : $referer;
    }

    // Optional path: explicit permission id (when not using CRUD id)
    elseif (!empty($permission_id))
    {
      if (!load_permission($permission_id, 'custom')) {
        return invalid_permission_response();
      }

      // Then resolve table/foreign key from query
      $table       = $_GET['table']        ?? null;
      $foreign_key = $_GET['foreign_key']  ?? null;
      $redirect    = $_SERVER['HTTP_REFERER'] ?? null;
    }

    // No CRUD nor explicit permission id: treat as forbidden
    else {
      return invalid_permission_response();;
    }

    // Execute duplication
    $return = duplicate_record([
      'table'            => $table,
      'id'               => $id,
      'foreign_key'      => $foreign_key,
      'tables_to_action' => $tables_to_action,
      'debug'            => $debug,
    ]);

    $msgKey = ($return ? 'SC' : 'ER') . '_TO_DUPLICATE';

    $res = [
      'code'   => $return ? 'success' : 'error',
      'detail' => [
        'type' => 'toast',
        'msg'  => alert_message($msgKey, 'toast'),
      ],
    ];

    if (!empty($_GET['redirect']) && $_GET['redirect'] == true && isset($redirect)) {
      $res['redirect'] = $redirect;
    }

    return $res;
  },
]);



/**
 * Register a REST API route for truncate table.
 *
 * This code registers a REST API route named 'truncate-table'.
 */
register_rest_route('truncate-record', [
  'methods' => 'POST',
  'callback' => function()
  {
    $id               = $_GET['id'] ?? null;
    $permission_id    = $_GET['permission_id'] ?? null;
    $tables_to_action = $_POST['tables_to_action'] ?? null;

    $debug = (is_dev() && isset($_GET['debug'])) ? true : false;

    if (isset($_GET['crud']))
    {
      $crud_id = (int) $_GET['crud'];

      // If permission is denied, short-circuit with system-standard JSON
      if (!load_permission($crud_id, 'truncate')) {
        return invalid_permission_response();
      }

      // With permission granted, resolve CRUD data
      $crud           = get_result("SELECT table_crud, foreign_key, pages_list FROM tb_cruds WHERE id = {$crud_id}");
      $pages_list     = $crud['pages_list'];
      $table          = $crud['table_crud'];
      $foreign_key    = $crud['foreign_key'] ?? null;
      $referer        = $_SERVER['HTTP_REFERER'] ?? null;

      $redirect = !empty($pages_list->list_pg)
        ? get_url_page($pages_list->list_pg, 'full')
        : $referer;
    }

    // Optional path: explicit permission id (when not using CRUD id)
    elseif (!empty($permission_id))
    {
      if (!load_permission($permission_id, 'custom')) {
        return invalid_permission_response();
      }

      // Then resolve table/foreign key from query
      $table       = $_GET['table']        ?? null;
      $foreign_key = $_GET['foreign_key']  ?? null;
      $redirect    = $_SERVER['HTTP_REFERER'] ?? null;
    }

    // No CRUD nor explicit permission id: treat as forbidden
    else {
      return invalid_permission_response();;
    }

    // $return = truncate_table($table);
    $return = false;

    $msgKey = ($return ? 'SC' : 'ER') . '_TO_TRUNCATE';

    $res = [
      'code'   => $return ? 'success' : 'error',
      'detail' => [
        'type' => 'toast',
        'msg'  => alert_message($msgKey, 'toast'),
      ],
    ];

    if (!empty($_GET['redirect']) && $_GET['redirect'] == true && isset($redirect)) {
      $res['redirect'] = $redirect;
    }

    return $res;
  },
]);

/**
 * Register a REST API route for delete record.
 *
 * This code registers a REST API route named 'delete-record'.
 */
register_rest_route('delete-record', [
  'methods' => ['POST', 'GET'],
  'callback' => function()
  {
    $id               = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $permission_id    = $_GET['permission_id'] ?? null;
    $tables_to_action = $_POST['tables_to_action'] ?? null;

    // Optional: delete by custom column (when no ID or even junto com ID, mas quem manda é a função)
    $where_field      = $_GET['where_field'] ?? null;
    $where_value      = $_GET['where_value'] ?? null;

    $debug = (is_dev() && isset($_GET['debug'])) ? true : false;

    // --- Path 1: CRUD-based deletion (uses CRUD ID) --------------------------
    if (isset($_GET['crud']))
    {
      $crud_id = (int) $_GET['crud'];

      // If permission is denied, short-circuit with system-standard JSON
      if (!load_permission($crud_id, 'delete')) {
        return invalid_permission_response();
      }

      // With permission granted, resolve CRUD data
      $crud       = get_result("SELECT table_crud, foreign_key, pages_list FROM tb_cruds WHERE id = {$crud_id}");
      $pages_list = $crud['pages_list'];
      $table      = $crud['table_crud'];
      $foreign_key= $crud['foreign_key'] ?? null;
      $referer    = $_SERVER['HTTP_REFERER'] ?? null;

      $redirect = !empty($pages_list->list_pg)
        ? get_url_page($pages_list->list_pg, 'full')
        : $referer;
    }

    // --- Path 2: explicit permission id + table/foreign_key ------------------
    elseif (!empty($permission_id))
    {
      if (!load_permission($permission_id, 'custom')) {
        return invalid_permission_response();
      }

      // Then resolve table/foreign key from query
      $table       = $_GET['table']       ?? null;
      $foreign_key = $_GET['foreign_key'] ?? null;
      $redirect    = $_SERVER['HTTP_REFERER'] ?? null;
    }

    // --- No CRUD nor explicit permission id: forbidden -----------------------
    else {
      return invalid_permission_response();
    }

    if (empty($table)) {
      return [
        'code'   => 'error',
        'detail' => [
          'type' => 'toast',
          'msg'  => alert_message('ER_TO_DELETE', 'toast'),
        ],
      ];
    }

    // Build params for delete_record(), now supporting where_field/where_value
    $deleteParams = [
      'table'            => $table,
      'id'               => $id,
      'foreign_key'      => $foreign_key,
      'tables_to_action' => $tables_to_action,
      'debug'            => $debug,
    ];

    // Only pass custom where when both pieces are present
    if ($where_field !== null && $where_value !== null) {
      $deleteParams['where_field'] = $where_field;
      $deleteParams['where_value'] = $where_value;
    }

    // Execute deletion (by id or by where_field/where_value, conforme delete_record)
    $return = delete_record($deleteParams);

    $msgKey = ($return ? 'SC' : 'ER') . '_TO_DELETE';

    $res = [
      'code'   => $return ? 'success' : 'error',
      'detail' => [
        'type' => 'toast',
        'msg'  => alert_message($msgKey, 'toast'),
      ],
    ];

    if (!empty($_GET['redirect']) && $_GET['redirect'] == true && isset($redirect)) {
      $res['redirect'] = $redirect;
    }

    return $res;
  },
]);



register_rest_route('get-crud-list', [
  'methods' => 'POST',
  'callback' => function ()
  {
      global $current_user;

      $piece_id = $_POST['crud_id'] ?? null;
      $draw = intval($_POST['draw'] ?? 0);

      if (empty($piece_id)) {
          return [
              'draw' => $draw,
              'recordsTotal' => 0,
              'recordsFiltered' => 0,
              'data' => [],
              'error' => 'CRUD ID not provided.'
          ];
      }

      $crud = get_crud_piece($piece_id);
      if (empty($crud)) {
          return [
              'draw' => $draw,
              'recordsTotal' => 0,
              'recordsFiltered' => 0,
              'data' => [],
              'error' => 'CRUD not found.'
          ];
      }

      $table_crud    = $crud['table_crud'];
      $foreign_key   = $crud['foreign_key'];
      $crud_id       = $crud['crud_id'] ?? '';
      $crud_panel    = $crud['crud_panel'];
      $list_settings = $crud['list_settings'];
      $custom_urls    = $crud['custom_urls'];
      $pages_list    = $crud['pages_list'];
      $type_crud      = $crud['type_crud'];

      // Prepare permissions
      $permissions = prepare_crud_permissions($crud_id, $pages_list);

      // Prepare hooks
      $hooks = prepare_crud_hooks([
          'type_crud'      => $type_crud,
          'crud_panel'    => $crud_panel,
          'permission'    => $permissions,
          'list_settings' => $list_settings,
          'custom_urls'    => $custom_urls,
          'crud_id'       => $crud_id,
          'foreign_key'   => $foreign_key,
          'pages_list'    => $pages_list
      ]);

      extract($hooks['buttons']);

      // Get filters from DataTables request
      $filters = [
          'start'  => $_POST['start'] ?? 0,
          'length' => $_POST['length'] ?? 10,
          'order'  => $_POST['order'] ?? [],
          'search' => $_POST['search'] ?? [],
      ];

      // Get columns configured in the CRUD
      $columns = get_results("SELECT * FROM tb_cruds_fields WHERE crud_id = '{$piece_id}'");

      $columns_array     = [];
      $searchable_fields = ['id'];

      $columns_array[] = [
        'name' => 'id'
      ];


      /**
       *
       * Columns treatment
       *
       */
      foreach ($columns as $col)
      {
        $settings = (array)($col['settings'] ?? []);
        $col_name = $col['name'] ?? ($settings['name'] ?? '');

        if ($col_name)
        {
          $col_name = explode('[', $col_name);
          $col_name = $col_name[0];

          $columns_array[] = [
            'name' => $col_name
          ];

          // Add field to searchable fields
          $searchable_fields[] = $col_name;
        }
      }
      // print_r($searchable_fields);


      // Build ORDER BY
      $order_by = [];
      if (!empty($filters['order']) && !empty($columns_array))
      {
          foreach ($filters['order'] as $order) {
              $col_index = intval($order['column']);
              $dir       = ($order['dir'] === 'desc') ? 'DESC' : 'ASC';

              if (isset($columns_array[$col_index]['name'])) {
                  $order_by[] = [
                      'field' => $columns_array[$col_index]['name'],
                      'way'   => $dir
                  ];
              }
          }
      }

      // Get the table cols
      $available_columns = show_columns($table_crud);

      // Build WHERE conditions if search term exists
      $where = [];
      if (!empty($filters['search']['value']) && !empty($searchable_fields))
      {
          $search_value  = $filters['search']['value'];
          $search_clauses = [];

          foreach ($searchable_fields as $field)
          {
            if (!in_array($field, $available_columns)) continue;

            $search_clauses[] = [
              'field'    => $field,
              'operator' => 'LIKE',
              'value'    => "%{$search_value}%"
            ];
          }

          $where = $search_clauses;
      }

      // Get total filtered count
      $total_filtered = count_results_by_array([
        'table' => $table_crud,
        'where' => $where,
        'where_logic' => 'OR'
      ]);


      // Get total records (without filter)
      $total_records = count_results("SELECT * FROM {$table_crud}");


      // Build list
      $list = prepare_crud_list([
          'crud'             => $crud,
          'piece_id'         => $piece_id,
          'list_settings'    => $list_settings,
          'table_crud'       => $table_crud,
          'permissions'      => $permissions,
          'custom_urls'      => $custom_urls,
          'list_page'        => $hooks['buttons'],
          'force_data'       => true,
          'filters'          => $filters,
          'where'            => $where,
          'order_by'         => $order_by,
      ]);

      return [
          'draw'            => $draw,
          'recordsTotal'    => $total_records,
          'recordsFiltered' => $total_filtered,
          'head'            => $list['head'] ?? [],
          'data'            => $list['body'] ?? [],
      ];
  },
  'permission_callback' => '__return_true',
]);



register_rest_route('ajax-upload-temp', [
  'methods' => 'POST',
  'callback' => function()
  {
    set_time_limit(0);

    // 1) Entrada: field opcional + arquivo (multipart) OU base64
    $field_id = $_POST['field'] ?? null;
    // $field_id = 445454;
    $file     = $_FILES['file'] ?? [];

    // --- HÍBRIDO: se não veio multipart, tenta base64 ---
    if (empty($file['name'])) {
      // tenta pegar base64 via POST form-encoded
      $b64 = $_POST['file_base64'] ?? null;

      if ($b64 === null) {
        // tenta corpo JSON
        $raw = file_get_contents('php://input');
        if ($raw) {
          $json = json_decode($raw, true);
          if (json_last_error() === JSON_ERROR_NONE) {
            // aceita várias chaves usuais
            $b64 = $json['file_base64'] ?? $json['base64'] ?? $json['data'] ?? null;
          }
        }
      }

      if ($b64) {
        // aceita "data:audio/webm;base64,AAAA..." ou só o payload base64
        $mimeFromDataUrl = null;
        if (preg_match('/^data:(.*?);base64,/', $b64, $m)) {
          $mimeFromDataUrl = trim(strtolower($m[1]));
          $b64 = preg_replace('/^data:.*;base64,/', '', $b64);
        }

        $bin = base64_decode($b64, true);
        if ($bin === false) {
          return ['code' => 'error', 'msg' => 'Base64 inválido'];
        }

        // detecta MIME real
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeDetected = $finfo->buffer($bin) ?: $mimeFromDataUrl ?: 'application/octet-stream';

        // mapeia extensão a partir do MIME (bem simples; ajuste se quiser mais casos)
        $extMap = [
          'audio/webm' => 'webm',
          'audio/ogg'  => 'ogg',
          'audio/mpeg' => 'mp3',
          'audio/wav'  => 'wav',
          'image/png'  => 'png',
          'image/jpeg' => 'jpg',
          'image/webp' => 'webp',
          'application/pdf' => 'pdf',
          'video/mp4'  => 'mp4',
          'video/webm' => 'webm',
        ];
        $ext = $extMap[$mimeDetected] ?? 'bin';

        // cria arquivo temporário e simula $_FILES
        $tmp = tempnam(sys_get_temp_dir(), 'upl_');
        file_put_contents($tmp, $bin);

        $file = [
          'name'     => 'upload-'.uniqid().'.'.$ext,
          'type'     => $mimeDetected,
          'tmp_name' => $tmp,
          'size'     => strlen($bin),
          'error'    => 0,
        ];
      }
    }

    // 2) Se mesmo assim não temos arquivo, erro
    if (empty($file['name'])) {
      return [
        'code' => 'error',
        'msg'  => 'O arquivo não foi recebido por nosso sistema',
      ];
    }

    // 3) Carrega parâmetros do field (se existir)
    $field = [];
    if (!empty($field_id)) {
      $field = get_result("SELECT * FROM tb_cruds_fields WHERE id = '{$field_id}'");
      $field = normalize_fields([$field]);
      $field = $field[0] ?? [];
    }

    // 4) Decide o $type (images/audios/videos/archives)
    if (!empty($field)) {
      $type = $field['type'] ?? 'archives';
    } else {
      $mime = strtolower($file['type'] ?? '');
      if (strpos($mime, 'image/') === 0)      { $type = 'images'; }
      elseif (strpos($mime, 'audio/') === 0)  { $type = 'audios'; }
      elseif (strpos($mime, 'video/') === 0)  { $type = 'videos'; }
      else                                    { $type = 'archives'; }
    }

    // 5) Executa upload usando tuas funções
    $folder = "uploads/temp/";
    $upload_to_s3 = $field['upload_to_s3'] ?? false;

    $params = [
      'files'        => $file,
      'folder'       => $folder,
      'size'         => $field['image_size']   ?? null,
      'allowed_exts' => $field['allowed_exts'] ?? null,
      'force_webp'   => $field['force_webp'] ?? null,
      'final_name'   => $field['final_name'] ?? null,
      'upload_to_s3' => $upload_to_s3,
      'visibility'   => $field['visibility'] ?? DEFAULT_FILES_VISIBILITY,
      'is_temp'      => true,
      'debug'        => false,
    ];

    $filename = '';
    $filename = media_upload_temp($params);


    // Define URLs preview.
    if ($upload_to_s3)
    {
      feature('aws-s3');

      $url = s3_presigned_url($filename);
      // $filename = basename($filename);
    }

    else
    {
      $url = !empty($filename)
        ? $folder . $filename
        : null;
      $url = site_url('/'. $url);
    }


    // 6) Retorno padronizado
    return [
      'code' => 'success',
      'file' => basename($filename),
      'final_expected'=> build_final_filename(basename($filename), (string)($field['final_name'] ?? '')),
      'url'  => $url,
      'type' => $type
    ];
  },
]);


register_rest_route('get-crud-piece', [
  'methods' => 'POST',
  'callback' => function ()
  {
    $piece_id = $_POST['piece_id'] ?? null;
    $register_id = $_POST['register_id'] ?? null;

    $params = [
      'piece_id' => $piece_id,
      'register_id' => $register_id,
      'only_form' => true,
    ];
    $crud = crud_piece($params);

    $modal =
    [
      'id' => 'crud-piece-modal',
      'size' => 'lg',
      'attributes' => 'data-modal:();',
      'title' => "#{$register_id}",
      'close_button' => true,
      'body' => $crud,
    ];

    return [
      'code' => 'success',
      'detail' => [
        'type' => 'modal',
        'msg' => block('modal', $modal),
      ]
    ];

    return $crud;
  },
  'permission_callback' => '__return_true',
]);
