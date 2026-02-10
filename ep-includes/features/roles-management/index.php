<?php
if(!isset($seg)) exit;

feature('permissions-management');

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
function manage_roles_form(string $type_form = 'insert', int $counter = 1)
{
    global
            $info,
            $routes_to_choose,
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
            'basic',
            $type_form,
            [
                'size' => 'col-md-6 col-lg-4',
                'label' => 'Slug',
                'name' => 'slug',
                'Value' => ($type_form=='update') ? $role['slug'] : '',
                'Alert' => 'Alterar o slug de um role já existente, pode quebrar fluxos já feitos.',
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
                'Value' => ($type_form=='update') ? $role['type'] : '',
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


/**
 * Manage nível data in a content management system.
 *
 * @param array $data - An array containing nível data.
 * @param string $mode - The mode of operation, either 'insert' or 'update'.
 * @param bool $debug - A flag for enabling debugging (default is false).
 *
 * @return array - An array containing status information.
 */
function manage_user_role_system(array $data, string $mode, bool $debug = false)
{
    $error        = false;
    $valid_data   = $data;

    $msg_type = 'toast';

    /*
     * Define the verifyer function.
     */
    if     ($mode == 'insert') $verifyer = 'inserted_id';
    elseif ($mode == 'update') $verifyer = 'affected_rows';
    else                       $error    = true;


    // Verify If there's an error
    if ($error) {
        $_SESSION['FormData'] = $Data;
    }

    // Else do the routine
    else
    {
        $args = [
            'name' => $valid_data['name'] ?? '',
            'slug' => $valid_data['slug'] ?? '',
            'redirect_page_id' => $valid_data['redirect_page_id'] ?? '',
            'status_id' => $valid_data['status_id'] ?? '',
            'type' => $valid_data['type'] ?? '',
        ];

        if ($mode == 'insert') {
            $args['created_at'] = 'NOW()';
        }
        else if ($mode == 'update')
        {
            $args['updated_at'] = 'NOW()';
            $args['data']  = $args;
            $args['where'] = where_equal_id($valid_data['id']);
        }

        // Lights, camera & action.
        $mode('tb_user_roles', $args, $debug);

        /*
         * Verify if inserted/updated correctaly.
         */
        if ($verifyer())
        {
            unset($_SESSION['FormData']);

            $role_id = ($mode == 'insert') ? inserted_id() : $valid_data['id'];

            if ($mode == 'insert') {
                create_permissions([ 'user_roles' => $role_id ]);
            }

            if (!empty($valid_data['lowest']) AND $valid_data['lowest']) {
                update_option('lowest_role', $role_id);
            }

            $msg = alert_message("SC_TO_". strtoupper($mode), $msg_type);
        }

        else {
            $_SESSION['FormData'] = $data;
            $msg = alert_message("ER_TO_". strtoupper($mode), $msg_type);
        }
    }

    $res = [
        'code' => !$error ? 'success' : 'error',
        'detail' => [
            'type' => $msg_type,
            'msg' => $msg ?? '',
        ],
    ];

    if ($mode == 'insert') {
        $res['redirect'] = get_url_page('gerenciar-niveis-acesso', 'full'). "?id={$role_id}";
    }

    return $res;
}
