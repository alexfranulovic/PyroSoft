<?php
if(!isset($seg)) exit;

/*
 * Register REST route to display the permission form modal
 */
register_rest_route('form-manage-custom-permission', [
  'methods' => ['POST', 'GET'],
  'need_login' => true,
  'callback' => function()
  {
    global $crud_action_triggers;

    // Capture form data
    $id = $_POST['id'] ?? 0;
    $mode = $_POST['mode'] ?? 'insert';
    $type = strtolower($_POST['type'] ?? 'Custom');

    // If editing, fetch current permission data
    if ($mode == 'update')
    {
      $data = get_permissions($id, $type);
      $slug = $data['slug'] ?? '';
    }

    // Start building the form HTML
    $form = '<div class="row">';

    // Field: Name
    $form .= input('basic', $mode, [
      'size' => 'col-md-6',
      'name' => 'name',
      'label' => 'Nome',
      'Value' => $data['name'] ?? '',
      'Required' => true,
      'disabled' => ($mode == 'update' && $type != 'custom'),
    ]);

    // Field: Slug
    $form .= input('basic', $mode, [
      'size' => 'col-md-6',
      'name' => 'slug',
      'label' => 'Slug',
      'Value' => $slug ?? '',
      'Required' => ($mode == 'insert'),
      'disabled' => ($mode == 'update'),
    ]);

    // Hidden fields for form context
    $form .= input('hidden', $mode, ['name' => 'mode', 'Value' => $mode]);
    $form .= input('hidden', $mode, ['name' => 'type', 'Value' => $type]);

    // Extra hidden field for edit mode
    if ($mode == 'update')
    {
      $form .= input('hidden', $mode, ['name' => 'id', 'Value' => $data['id'] ?? '']);

      // Display the permission loading code if not a CRUD type
      if ($type != 'crud') {
        $form .= "
        <fieldset>
          <h5>Código da permissão:</h5>
          <code>load_permission('{$slug}', '{$type}');</code>
        </fieldset>";
      }
    }

    // Field: Permission Type
    $form .= input('selection_type', $mode, [
        'type' => 'radio',
        'size' => 'col-12',
        'name' => 'permission_type',
        'Options' => [
            [ 'value' => 'only_these', 'display' => 'Liberado apenas para esses' ],
            [ 'value' => 'except_these', 'display' => 'Exclua apenas esses' ],
        ],
        'Value' => ($mode=='update') ? ($data['permission_type'] ?? 'only_these')  : 'only_these',
      ]
    );


    // If type is 'custom', show roles selector
    if ($type == 'custom')
    {
      $form .= input('selection_type', $mode, [
        'type' => 'checkbox',
        'size' => 'col-12',
        'label' => 'Liberar para:',
        'name' => 'allowed[]',
        'variation' => 'inline',
        'Query' => 'SELECT id as value, name as display FROM tb_user_roles',
        'Value' => get_results("
          SELECT role_id as value FROM tb_user_role_permissions
          WHERE permission_id = '{$id}' AND allowed = 1"),
      ]);
    }

    // If type is 'page', show role access
    if ($type == 'page')
    {
      $form .= input('selection_type', $mode, [
        'type' => 'checkbox',
        'size' => 'col-12',
        'label' => 'Liberar para:',
        'name' => 'allowed[]',
        'variation' => 'inline',
        'Query' => 'SELECT id as value, name as display FROM tb_user_roles',
        'Value' => get_results("
          SELECT role_id as value FROM tb_user_role_permissions
          WHERE page_id = '{$id}' AND allowed = 1"),
      ]);
    }

    // If type is 'crud', loop through triggers and render per-action permissions
    if ($type == 'crud')
    {
      foreach ($crud_action_triggers as $name => $trigger)
      {
        $form.= hr();
        $form.= input('selection_type', $mode, [
          'type' => 'checkbox',
          'size' => 'col-12',
          'label' => "Liberar \"{$trigger}\" para:",
          'name' => "allowed[{$name}][]",
          'variation' => 'inline',
          'Query' => 'SELECT id as value, name as display FROM tb_user_roles',
          'Value' => get_results("
            SELECT role_id as value FROM tb_user_role_permissions
            WHERE crud_id = '{$id}' AND allowed = 1 AND action_trigger = '{$name}'"),
        ]);

        $form.= "<strong>Código da permissão:</strong>
                <code>load_permission('{$slug}', '{$name}');</code>";
      }
    }

    $form .= '</div>'; // Close form row


    // Define action for the form
    $action = ($mode == 'update') ? "manage-custom-permission" : "manage-custom-permission";


    // Define footer buttons
    $footer = "
    <button type='button' class='btn btn-link' data-bs-dismiss='modal'>Cancelar</button>
    <button type='submit' class='btn btn-success text-white'>Confirmar</button>";


    // Build modal structure
    $modal =
    [
      'id' => 'data-controller',
      'form' => [
        'active' => true,
        'attributes' => "action: (" . rest_api_route_url($action) . "); method: (POST); form-manage-custom-permission:(); data-send-without-reload:();",
      ],
      'title' => ($mode == 'update') ? 'Editar permissão' : 'Cadastrar permissão',
      'close_button' => true,
      'body' => $form,
      'footer' => $footer
    ];

    $res =  [
      'success' => true,
      'detail' => [
        'type' => 'modal',
        'msg' => block('modal', $modal),
      ]
    ];

    // Return modal as API response
    return $res;
  },
  'permission_callback' => '__return_true', // Allow any logged in user
]);


/*
 * Register REST route to save submitted permissions
 */
register_rest_route('manage-custom-permission', [
  'methods' => ['POST', 'GET'],
  'need_login' => true,
  'callback' => function()
  {
    global $seg, $crud_action_triggers;

    $id = $_POST['id'] ?? null;

    // Load required feature module
    feature('permissions-management');

    $type = $_POST['type'] ?? '';

    // Type tables
    $table = [
      'page' => 'tb_pages',
      'crud' => 'tb_cruds',
      'custom' => 'tb_user_role_permissions',
    ];

    // Setup args based on type
    if ($type == 'custom')
    {
      $args = [
        'allowed' => $_POST['allowed'] ?? [],
        'permission_type' => $_POST['permission_type'] ?? 'only_these',
        'name' => $_POST['name'] ?? '',
        'slug' => $_POST['slug'] ?? '',
        'type' => 'permission'
      ];
    }

    elseif ($type == 'page')
    {
      $args = [
        'allowed' => $_POST['allowed'] ?? [],
        'type' => 'page'
      ];
    }

    // Include parent ID if editing
    if (!empty($id)) {
      $args['parent_id'] = $id;
    }

    // Execute permission update
    $res = update_permissions($args ?? [], false);

    // Handle CRUD-specific permissions
    if ($type == 'crud')
    {
      foreach ($crud_action_triggers as $name => $trigger)
      {
        $args = [
          'allowed' => $_POST['allowed'][$name] ?? [],
          'action_trigger' => $name,
          'type' => 'crud'
        ];

        if (!empty($id)) {
          $args['parent_id'] = $id;
        }

        $res = update_permissions($args ?? [], false);
      }
    }

    // Update the permission type according the type of permission
    if (($type == 'crud') OR ($type == 'page')) {
      query_it("UPDATE {$table[$type]} SET permission_type = '{$_POST['permission_type']}' WHERE id = '{$id}'");
    }

    if ($type == 'custom') {
      $res['redirect'] = get_url_page('gerenciar-permissoes', 'full');
    }

    return $res;
  },
  'permission_callback' => '__return_true', // Allow any logged in user
]);
