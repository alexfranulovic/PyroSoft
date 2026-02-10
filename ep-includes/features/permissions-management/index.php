<?php
if(!isset($seg)) exit;

global $pages_to_choose;
$pages_to_choose = [];
foreach (get_pages([ 'page_area' => true ]) as $page)
{
    if ($page['status_id'] != 1 ) continue;
    $pages_to_choose[] = [ 'value' => $page['id'], 'display' => $page['title'] ];
}


/**
 * Generate a level management form for a content management system.
 *
 * @param string $type_form - The type of form, either 'insert' for creating a new record or 'update' for editing an existing one.
 * @param int $counter - An optional counter used for form fields (default is 1).
 *
 * @return int - The updated counter value.
 */
function manage_permissions_form(string $type_form = 'insert', int $counter = 1)
{
    global
            $info,
            $page,
            $pages_to_choose;

    $id = id_by_get();

    $role = get_result("SELECT * FROM tb_user_roles WHERE id = '{$id}'");

    $type_form = $id
        ? 'update'
        : 'insert';

    if (count($role) == 0) {
        $type_form = 'insert';
    }

    if ($type_form == 'update')
    {
        $panel['hooks_out'][] = [
            'title' => 'Duplicar',
            'url'  => rest_api_route_url("duplicate-record?id={$id}&table=tb_user_roles&foreign_key=role_id"),
            'attr'  => 'data-controller: (duplicate);',
            'color' => 'outline-info',
            'pre_icon' => 'fas fa-copy',
        ];
    }

    if ($type_form == 'update')
    {
        $panel['hooks_out'][] = [
            'title' => 'Apagar',
            'url'  => rest_api_route_url("delete-record?id={$id}&table=tb_user_roles&foreign_key=role_id"),
            'attr'  => 'data-controller: (delete);',
            'color' => 'outline-danger',
            'pre_icon' => 'fas fa-trash',
        ];
    }

    ?>

    <section class="row user-role-management">

    <section class="col-12">
    <div class="card box-fields">
        <form method="GET" action="" class="card-body row content-center">
            <?= input(
            'selection_type',
                'update',
                [
                    'size' => 'col-md-8',
                    'type' => 'search',
                    'label' => 'Selecione o nível para editar',
                    'name' => 'id',
                    'Query' => "SELECT id as value, name as display FROM tb_user_roles",
                    'Value' => $id,
                ]
            ).
            input(
            'submit_button',
            $type_form,
            [
                'size' => 'col',
                'class' => 'btn btn-outline-nd btn-block',
                'Value' => 'Selecionar'
            ])?>

            <div class="col-12">
                <a href="<?= get_url_page($page['id'], 'full') ?>">ou criar um novo nível.</a>
            </div>

        </form>
    </div>
    </section>

    <?= crud_panel( $panel ?? [] ) ?>

    <form class="col-md col-lg col-xl main-form" method="POST" data-send-ctrl-s data-send-without-reload action="<?= rest_api_route_url("manage-user-role?mode={$type_form}") ?>">

        <div class="card box-fields">
        <div class="card-body" id='container-card'>
        <div class="row">

            <?php
            echo input(
            'basic',
            $type_form,
            [
                'size' => 'col-md-6 col-lg-4',
                'label' => 'Nome',
                'name' => 'name',
                'Value' => ($type_form=='update') ? $role['name'] : '',
                'Required' => true
            ]) .
            input(
            'selection_type',
            $type_form,
            [
                'type' => 'search',
                'size' => 'col-md-6 col-lg-4',
                'label' => 'Página de redirecionamento',
                'name' => 'redirect_page_id',
                'Options' => $pages_to_choose,
                'Value' => ($type_form=='update') ? $role['redirect_page_id'] : '',
                'Required' => true
            ]) .
            input(
            'status_selector',
            $type_form,
            [
                'size' => 'col-md-6 col-lg-4',
                'function_proccess' => 'general_status',
                'name' => "status_id",
                'Value' => ($type_form=='update') ? $role['status_id'] : '',
                'Required' => true
            ]) .
            input(
            'selection_type',
            $type_form,
            [
                'type' => 'search',
                'size' => 'col-md-6 col-lg-4',
                'label' => 'Tipo',
                'name' => 'type',
                'Options' => [
                    [ 'value' => 'role', 'display' => 'Função' ],
                    [ 'value' => 'signature', 'display' => 'Assinatura' ],
                ],
                'Value' => ($type_form=='update') ? $role['type'] : 'role',
                'Required' => true
            ]) .
            input(
            'selection_type',
            $type_form,
            [
                'type' => 'checkbox',
                'size' => 'col-md-6 col-lg-4',
                'name' => 'lowest',
                'Options' => [
                    [ 'value' => '1', 'display' => 'Esse é o nível mais básico' ],
                ],
                'Value' => ($type_form=='update' AND $id == lowest_role_user()) ? 1 : 0,
                'Required' => true
            ]) .
            input(
            'submit_button',
            $type_form,
            [
                'size' => 'col-12',
                'class' => 'btn btn-st',
                'Value' => ($type_form == 'update') ? 'Editar' : 'Cadastrar'
            ]);
            ?>

        </div>
        </div>

        </div>

        <?php
        if ($type_form == 'update') echo input('hidden', $type_form, [ 'name' => 'id', 'Value' => $id ]);
        ?>
    </form>
    </section>

    <?php
    return $counter;
    unset($_SESSION['FormData']);
}


function update_permissions(array $data, bool $debug = false)
{
    $error      = false;
    $valid_data = $data;

    $msg_type   = 'toast';

    $mode      = $data['mode'] ?? null;
    $slug      = $data['slug'] ?? null;
    $parent_id = $data['parent_id'] ?? null;

    $type = !empty($data['type'])
        ? $data['type'] . "_id"
        : null;

    if ($type == 'permission_id')
    {
        // Se não veio parent_id, tentamos reaproveitar por slug (se informado)
        if (empty($parent_id))
        {
            // Tentar localizar permissão já existente com mesmo slug e action_trigger = 'custom'
            if (!empty($slug)) {
                $existing = get_col(
                    "SELECT id
                       FROM tb_user_role_permissions
                      WHERE slug = '{$slug}'
                        AND action_trigger = 'custom'
                      LIMIT 1"
                );

                if (!empty($existing)) {
                    $parent_id = (int)$existing;
                }
            }

            // Se ainda não há parent_id, é de fato uma nova permissão
            if (empty($parent_id))
            {
                $args_insert = [
                    'name'            => $valid_data['name'] ?? null,
                    'slug'            => $valid_data['slug'] ?? null,
                    'permission_type' => $valid_data['permission_type'] ?? 'only_these',
                    'action_trigger'  => 'custom',
                ];

                // Lights, camera & action.
                insert('tb_user_role_permissions', $args_insert, true, $debug);

                $parent_id = inserted_id();
            }
            else
            {
                // Já existe permissão com esse slug: apenas atualiza campos básicos se vierem
                $args_update = [
                    'data'  => [],
                    'where' => where_equal_id($parent_id),
                ];

                if (array_key_exists('name', $valid_data)) {
                    $args_update['data']['name'] = $valid_data['name'];
                }
                if (array_key_exists('permission_type', $valid_data)) {
                    $args_update['data']['permission_type'] = $valid_data['permission_type'];
                }

                if (!empty($args_update['data'])) {
                    update('tb_user_role_permissions', $args_update, true, $debug);
                }
            }
        }
        else
        {
            // Fluxo original quando parent_id já é conhecido
            $args = [
                'data'  => [],
                'where' => where_equal_id($parent_id),
            ];

            $args['data']['name']            = $valid_data['name'] ?? null;
            $args['data']['permission_type'] = $valid_data['permission_type'] ?? null;

            // Lights, camera & action.
            update('tb_user_role_permissions', $args, true, $debug);
        }
    }

    $allowed        = $data['allowed'] ?? [];
    $action_trigger = $data['action_trigger'] ?? [0];
    $action_trigger = is_array($action_trigger) ? $action_trigger : [ $action_trigger ];

    $action_trigger_sql = (count($action_trigger) === 1)
        ? " AND action_trigger = '{$action_trigger[0]}'"
        : '';

    $user_roles = !empty($data['user_roles'])
        ? [ [ "id" => $data['user_roles'] ] ]
        : get_results("SELECT id FROM tb_user_roles");

    if (!empty($user_roles))
    {
        if ($mode != 'recreate') {
            query_it("DELETE FROM tb_user_role_permissions WHERE {$type} = '{$parent_id}' $action_trigger_sql", true, $debug);
        }

        foreach ($user_roles as $role)
        {
            foreach ($action_trigger as $trigger)
            {
                if (($parent_id == 0) && ($type == 'permission_id')) continue;

                $args = [
                    'allowed'        => in_array($role['id'], $allowed),
                    'action_trigger' => $trigger,
                    'role_id'        => $role['id'],
                ];

                $args[$type] = $parent_id;

                // Lights, camera & action.
                insert('tb_user_role_permissions', $args, true, $debug);
            }
        }
    }

    /*
     * Verify if inserted/updated correctaly.
     */
    $msg_code = !$error
        ? "SC_TO_MANAGE_PERMISSION"
        : "ER_TO_MANAGE_PERMISSION";

    $return = [
        'code'   => !$error ? 'success' : 'error',
        'detail' => [
            'type' => $msg_type,
            'msg'  => alert_message($msg_code, $msg_type),
        ],
    ];

    return $return;
}



function create_permissions(array $params = [], bool $debug = false)
{
    global $crud_action_triggers;

    $user_roles = $params['user_roles'] ?? null;
    $recreate   = $params['recreate'] ?? false;

    $customs = get_results("SELECT id, name, slug, permission_type FROM tb_user_role_permissions WHERE action_trigger = 'custom'");
        // var_dump($customs);

    if ($recreate) {
        query_it('TRUNCATE tb_user_role_permissions', true, $debug);
    }

    $pages = get_results("SELECT id, permission_type FROM tb_pages");
        // var_dump($pages);
    foreach ($pages as $permission_details)
    {
        $args = [
            'allowed' => ($permission_details['permission_type'] == 'only_these') ? [1] : [],
            'type' => 'page',
            'parent_id' => $permission_details['id'],
            'mode' => 'recreate',
        ];

        if (!$recreate AND !is_null($user_roles)) {
            $args['user_roles'] = $user_roles;
        }

        update_permissions($args, $debug);
    }


    $cruds = get_results("SELECT id, permission_type FROM tb_cruds WHERE type_crud = 'master'");
        // var_dump($cruds);
    foreach ($cruds as $permission_details)
    {
        $args = [
            'allowed' => ($permission_details['permission_type'] == 'only_these') ? [1] : [],
            'action_trigger' => array_keys($crud_action_triggers),
            'type' => 'crud',
            'parent_id' => $permission_details['id'],
            'mode' => 'recreate',
        ];

        if (!$recreate AND !is_null($user_roles)) {
            $args['user_roles'] = $user_roles;
        }

        update_permissions($args, $debug);
    }

    foreach ($customs as $permission_details)
    {
        $args = [
            'allowed' => ($permission_details['permission_type'] == 'only_these') ? [1] : [],
            'name' => $permission_details['name'],
            'slug' => $permission_details['slug'],
            'permission_type' => $permission_details['permission_type'],
            'type' => 'permission',
            'mode' => 'recreate',
        ];

        if (!$recreate) {
            $args['parent_id'] = $permission_details['id'];
        }

        if (!$recreate AND !is_null($user_roles)) {
            $args['user_roles'] = $user_roles;
        }

        update_permissions($args, $debug);
    }
}


/**
 * Delete permissions bindings and, optionally, the custom permission definition itself.
 *
 * Expected $data structure:
 * - type          (string)  'page' | 'crud' | 'permission' (default: 'permission')
 * - parent_id     (int)     Page/CRUD/Permission ID (preferred)
 * - slug          (string)  Used only when type = 'permission' and parent_id is not provided
 * - user_roles    (mixed)   Single role id, array of ids, or array [['id' => 1], ['id' => 2]]
 * - action_trigger(mixed)   Single trigger or array of triggers
 *
 * Behavior:
 * - For type = 'page' or 'crud': deletes rows in tb_user_role_permissions filtered by
 *   {page_id|crud_id}, and optionally by role_id / action_trigger.
 * - For type = 'permission':
 *   1) deletes tb_user_role_permissions where permission_id = {parent_id},
 *      optionally filtered by role_id / action_trigger;
 *   2) if no user_roles/action_trigger filter OR if you still want,
 *      also deletes the base custom permission row (id = parent_id, action_trigger = 'custom').
 *
 * @param array $data
 * @param bool  $debug
 * @return array
 */
function delete_permission(array $data, bool $debug = false)
{
    $error    = false;
    $msg_type = 'toast';

    $type      = $data['type']      ?? 'permission'; // 'page', 'crud', 'permission'
    $parent_id = $data['parent_id'] ?? null;
    $slug      = $data['slug']      ?? null;

    // Resolve parent_id for custom permission using slug if not provided
    if ($type === 'permission' && empty($parent_id) && !empty($slug)) {
        $slug_safe = addslashes($slug);

        $parent_id = (int)get_col(
            "SELECT id
               FROM tb_user_role_permissions
              WHERE slug = '{$slug_safe}'
                AND action_trigger = 'custom'
              LIMIT 1"
        );
    }

    // Build where clause for deletion of bindings
    $whereParts = [];

    // Column by type
    if ($type === 'page' || $type === 'crud' || $type === 'permission') {
        if (!empty($parent_id)) {
            $column      = $type . '_id'; // page_id | crud_id | permission_id
            $whereParts[] = "{$column} = '{$parent_id}'";
        }
    }

    // Optional filter: action_trigger
    $action_trigger = $data['action_trigger'] ?? null;
    if (!empty($action_trigger)) {
        $triggers = is_array($action_trigger) ? $action_trigger : [ $action_trigger ];
        $triggers = array_map('addslashes', $triggers);

        if (!empty($triggers)) {
            $whereParts[] = "action_trigger IN ('" . implode("','", $triggers) . "')";
        }
    }

    // Optional filter: user_roles
    $user_roles = $data['user_roles'] ?? null;
    if (!empty($user_roles)) {
        // Can come as: 1, [1,2,3] or [ ['id' => 1], ['id' => 2] ]
        if (!is_array($user_roles)) {
            $user_roles = [ $user_roles ];
        }

        // Normalize to a flat array of numeric IDs
        $roles_ids = [];

        foreach ($user_roles as $role) {
            if (is_array($role) && isset($role['id'])) {
                $roles_ids[] = (int)$role['id'];
            } else {
                $roles_ids[] = (int)$role;
            }
        }

        $roles_ids = array_filter($roles_ids, static function($id) {
            return $id > 0;
        });

        if (!empty($roles_ids)) {
            $whereParts[] = "role_id IN (" . implode(',', $roles_ids) . ")";
        }
    }

    // If we still can't determine a valid WHERE, it's unsafe to proceed
    if (empty($whereParts)) {
        $error = true;
    } else {
        $where_sql = implode(' AND ', $whereParts);

        // 1) Delete bindings (page/crud/permission-role relations)
        query_it("DELETE FROM tb_user_role_permissions WHERE {$where_sql}", true, $debug);

        // 2) If we're dealing with a custom permission, also delete its "definition" row
        //    (only when we have a valid parent_id)
        if ($type === 'permission' && !empty($parent_id)) {
            // If you quiser ser mais conservador, pode colocar essa deleção atrás de um
            // flag no $data (ex: 'delete_definition' => true)
            query_it(
                "DELETE FROM tb_user_role_permissions
                  WHERE id = '{$parent_id}'
                    AND action_trigger = 'custom'
                  LIMIT 1",
                true,
                $debug
            );
        }
    }

    $msg_code = !$error
        ? 'SC_TO_DELETE'
        : 'ER_TO_DELETE';

    return [
        'code'   => !$error ? 'success' : 'error',
        'detail' => [
            'type' => $msg_type,
            'msg'  => alert_message($msg_code, $msg_type),
        ],
    ];
}
