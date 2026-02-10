<?php
if(!isset($seg)) exit;


/** Returns only non-empty tokens, e.g. 'tb_info[roles][]' → ['tb_info','roles'] */
function input_name_to_path(string $name): array {
    preg_match_all('/([^\[\]]+)/', $name, $m);
    return $m[1] ?? [];
}

/** Keeps bracket segments exactly as given (including empty '[]'). */
function format_input_name(string $name = ''): string {
    if (preg_match('/^\[.*\]$/', $name) || substr($name, 0, 2) === '[]') return $name;
    if (strpos($name, '[') === false && strpos($name, ']') === false) return "[$name]";
    $formatted = '';
    foreach (explode('[', $name) as $piece) {
        $piece = rtrim($piece, ']');
        $formatted .= ($piece !== '') ? "[$piece]" : '[]';
    }
    return $formatted;
}

/** Ensure table prefix appears only once, preserving trailing [] and all segments. */
function ensure_table_prefix(string $rawName, string $table): string {
    $brackets = format_input_name($rawName);  // e.g. "[roles][]" or "[tb_info][roles][]"
    $prefix   = "[$table]";
    if (strpos($brackets, $prefix) === 0) {
        // already prefixed: drop the first "[$table]" then add once
        $brackets = substr($brackets, strlen($prefix));
    }
    return $table . $brackets; // "tb_info[roles][]"
}

/** Resolve value following the name path; ignores empty segments like [] (array-of). */
function value_by_input_name(string $name, array $root, ?string $tableHint = null) {
    $parts = input_name_to_path($name); // empty '[]' are ignored by design here
    if (!$parts) return null;
    if ($tableHint && $parts[0] === $tableHint) array_shift($parts);
    $val = $root;
    foreach ($parts as $p) {
        if (is_array($val) && array_key_exists($p, $val)) $val = $val[$p];
        else return null;
    }
    return $val;
}

// --- helper para normalizar o conjunto de selecionados ---
function _selected_set($val): array {
    // Suporta: escalar, array lista, ou array associativo ['admin' => 1, 'dev' => 0]
    if (is_array($val)) {
        $isAssoc = array_keys($val) !== range(0, count($val) - 1);
        $out = [];
        if ($isAssoc) {
            foreach ($val as $k => $v) {
                if ($v || $v === '1') $out[] = (string)$k;
            }
        } else {
            foreach ($val as $v) $out[] = (string)$v;
        }
        return array_values(array_unique($out));
    }
    if ($val === null || $val === '') return [];
    return [(string)$val];
}


function force_final_table(string $table, string $related_to = 'table'): string
{
    $map = [
        'system_info'    => 'tb_info',
        'logged_in_user' => 'tb_users',
    ];

    // Se $table for um alias conhecido, retorna a tabela real; senão, devolve o próprio $table
    return isset($map[$related_to]) ? $map[$related_to] : $table;
}


function normalize_fields(array $cruds_fields = [], bool $iterate_options = false)
{
    $fields = [];
    foreach ($cruds_fields as $field)
    {
        // dump($field);
        if (empty($field['settings'])) continue;

        /**
         * Normal use.
         */
        $field = (array) $field['settings'] + $field;
        unset($field['settings']);


        /**
         * Iterate options that has name as a field.
         */
        if ($iterate_options && ($field['type_field'] == 'selection_type') && !empty($field['Options']))
        {
            $Options = $field['Options'];
            unset($field['Options']);

            $fields[] = $field;

            foreach ($Options as $option)
            {
                if (empty($option['name'])) continue;

                $option['is_option'] = true;

                $iteration = array_merge($field, $option);
                $fields[] = $iteration;
            }
        }

        else {
            $fields[] = $field;
        }
    }

    return $fields;
}


function get_crud_piece($crud_id = '')
{
    $crud_status_id = is_dev()
        ? ' p.status_id != 2'
        : ' p.status_id = 1';

    return get_result(
    "SELECT
    c.table_crud,
    c.foreign_key,
    c.pages_list,
    c.custom_urls,
    p.id,
    p.piece_name,
    p.type_crud,
    p.attributes,
    p.form_method,
    p.form_action,
    p.views_count,
    p.submits_count,
    p.related_to,
    p.form_settings,
    p.list_settings,
    p.crud_id,
    p.crud_panel,
    p.result_page,
    p.permission_type,
    p.login_required,
    p.status_id AS status_crud_id
    FROM tb_cruds AS p
    INNER JOIN tb_cruds AS c ON c.id = p.crud_id
    WHERE $crud_status_id
    AND (p.id = '{$crud_id}' OR p.slug = '{$crud_id}')");
}


/**
 * Extracts a base segment from an input name by side.
 *
 * Examples:
 *  extract_input_base_name('login_settings[password_must][has_special_characters]', 'left');   // "login_settings"
 *  extract_input_base_name('login_settings[password_must][has_special_characters]', 'right');  // "has_special_characters"
 *  extract_input_base_name('login_settings[login_social][]', 'right');                         // "login_social"
 *  extract_input_base_name('a[b][c][0]', 'right');                                            // "c" (skips numeric index)
 *
 * @param string $name Input name like "tb_info[roles][]", "foo[bar][baz]", "a[b][0]"
 * @param string $side 'left' (default) returns the first non-empty segment;
 *                     'right' returns the last non-empty, non-index segment (skips [] and numeric indexes)
 * @return string
 */
function extract_input_base_name(string $name, string $side = 'left'): string
{
    // Capture only non-empty tokens between brackets (and the leading root before the first '[')
    preg_match_all('/([^\[\]]+)/', $name, $m);
    $parts = $m[1] ?? [];

    if (empty($parts)) {
        return '';
    }

    if (strtolower($side) === 'right') {
        // Walk from the end and return the last non-numeric token.
        for ($i = count($parts) - 1; $i >= 0; $i--) {
            $tok = (string) $parts[$i];
            if ($tok === '') continue;
            // Skip numeric indexes like [0], [1], ...
            if (ctype_digit($tok)) continue;
            return $tok;
        }
        // If everything was numeric (edge case), return the last token anyway
        return (string) end($parts);
    }

    // default: 'left'
    return (string) $parts[0];
}

function prepare_crud_permissions($crud_id, $pages_list = [])
{
    $List = $pages_list['list_pg'] ?? '';

    // INSERT
    $insertMode = $pages_list['insert']['mode'] ?? 'page';
    $insertPage = $pages_list['insert']['page'] ?? '';

    // VIEW
    $viewMode = $pages_list['view']['mode'] ?? 'page';
    $viewPage = $pages_list['view']['page'] ?? '';

    // UPDATE (edit)
    $updateMode = $pages_list['update']['mode'] ?? 'page';
    $updatePage = $pages_list['update']['page'] ?? '';

    // return [
    //     'view'      => ($viewMode   === 'modal') ? load_permission($crud_id, 'view')   : load_permission($viewPage),
    //     'inset'    => ($insertMode === 'modal') ? load_permission($crud_id, 'insert') : load_permission($insertPage),
    //     'update'      => ($updateMode === 'modal') ? load_permission($crud_id, 'update')   : load_permission($updatePage),
    //     'list'      => load_permission($List),
    //     'truncate'  => load_permission($crud_id, 'truncate'),
    //     'delete'    => load_permission($crud_id, 'delete'),
    //     'order'     => load_permission($crud_id, 'order'),
    //     'duplicate' => load_permission($crud_id, 'duplicate'),
    // ];

    return [
        'view'      => load_permission($crud_id, 'view'),
        'inset'     => load_permission($crud_id, 'insert'),
        'update'    => load_permission($crud_id, 'update'),
        'list'      => load_permission($List),
        'truncate'  => load_permission($crud_id, 'truncate'),
        'delete'    => load_permission($crud_id, 'delete'),
        'order'     => load_permission($crud_id, 'order'),
        'duplicate' => load_permission($crud_id, 'duplicate'),
    ];
}

function prepare_crud_hooks($params)
{
    $type_crud      = $params['type_crud'];
    $crud_panel    = $params['crud_panel'];
    $permission    = $params['permission'];
    $list_settings = $params['list_settings'];
    $custom_urls    = $params['custom_urls'];
    $crud_id       = $params['crud_id'] ?? null;
    $foreign_key   = $params['foreign_key'] ?? null;
    $pages_list    = $params['pages_list'] ?? [];
    $id            = $params['id'] ?? id_by_get();

    $List = $pages_list['list_pg'] ?? '';

    // INSERT
    $insertMode  = $pages_list['insert']['mode']  ?? 'page';
    $insertPage  = $pages_list['insert']['page']  ?? '';
    $insertPiece = $pages_list['insert']['piece'] ?? '';

    // VIEW
    $viewMode  = $pages_list['view']['mode']  ?? 'page';
    $viewPage  = $pages_list['view']['page']  ?? '';
    $viewPiece = $pages_list['view']['piece'] ?? '';

    // UPDATE (edit)
    $updateMode  = $pages_list['update']['mode']  ?? 'page';
    $updatePage  = $pages_list['update']['page']  ?? '';
    $updatePiece = $pages_list['update']['piece'] ?? '';

    $hooks_out = [];
    $hooks_in  = [];

    $Truncate            = "crud={$crud_id}";
    $Delete = $Duplicate = "foreign_key={$foreign_key}&crud={$crud_id}&id=";

    // Botões principais (piece_id = da própria ação)
    $Insert_url = [
        'type'     => ($insertMode === 'modal') ? 'modal' : 'page',
        'piece_id' => $insertPiece,
        'pre_icon' => 'fas fa-plus',
        'title'    => 'Cadastrar',
        'url'      => ($insertMode === 'page') ? get_url_page($insertPage, 'full') : '',
        'color'    => 'success',
    ];

    $View_url = [
        'type'     => ($viewMode === 'modal') ? 'modal' : 'page',
        'piece_id' => $viewPiece,
        'pre_icon' => 'fas fa-eye',
        'title'    => 'Visualizar',
        'url'      => ($viewMode === 'page') ? get_url_page($viewPage, 'full') . '?id=' : '',
        'color'    => 'info',
    ];

    $Edit_url = [
        'type'     => ($updateMode === 'modal') ? 'modal' : 'page',
        'piece_id' => $updatePiece,
        'pre_icon' => 'fas fa-pencil',
        'title'    => 'Editar',
        'url'      => ($updateMode === 'page') ? get_url_page($updatePage, 'full') . '?id=' : '',
        'color'    => 'warning',
    ];

    $List_url = [
        'pre_icon' => 'fas fa-list',
        'title'    => 'Listar',
        'url'      => get_url_page($List, 'full'),
        'color'    => 'info',
    ];

    $Truncate_url = [
        'pre_icon' => 'fas fa-broom',
        'title'    => 'Apagar tudo',
        'url'      => rest_api_route_url("truncate-table?redirect=true&{$Truncate}"),
        'attr'     => 'data-controller: (truncate);',
        'color'    => 'danger',
    ];

    $Duplicate_url = [
        'pre_icon' => 'fas fa-copy',
        'title'    => 'Duplicar',
        'url'      => rest_api_route_url("duplicate-record?redirect=true&{$Duplicate}"),
        'attr'     => 'data-controller: (duplicate);',
        'color'    => 'info',
    ];

    $Order_url = [
        'pre_icon' => 'fas fa-arrow-up',
        'url'      => rest_api_route_url("order-record?redirect=true&{$Duplicate}"),
    ];

    $Delete_url = [
        'pre_icon' => 'fas fa-trash',
        'title'    => 'Apagar',
        'url'      => rest_api_route_url("delete-record?redirect=true&{$Delete}"),
        'attr'     => 'data-controller: (delete);',
        'color'    => 'danger',
    ];

    // Preenche ?id= quando não é lista
    if ($type_crud !== 'list')
    {
        $Duplicate_url['id'] = $id;
        $View_url['id']      = $id;
        $Edit_url['id']      = $id;
        $Delete_url['id']    = $id;

        $Duplicate_url['url'] .= $id;
        if ($View_url['url']) $View_url['url'] .= $id;
        if ($Edit_url['url']) $Edit_url['url'] .= $id;
        $Delete_url['url']   .= $id;
    }

    // URLs customizadas (mantido)
    foreach ((array)$custom_urls as $url)
    {
        $url = (array)$url;
        if ($url['where'] !== 'out-panel' && $url['where'] !== 'in-panel') continue;

        if (($url['type'] === 'custom') || ($url['type'] === 'page' && load_permission($url['url'])))
        {
            $url['url'] = ($url['type'] === 'page') ? get_url_page($url['url'], 'full') : $url['url'];
            if (!empty($url['attr']) && $url['attr'] === '[id]') $url['url'] .= "?id={$id}";
            ($url['where'] === 'out-panel') ? ($hooks_out[] = $url) : ($hooks_in[] = $url);
        }
    }

    // Regras de exibição (iguais, com OR corrigido)
    if ((in_array('show_list_pg', (array)$list_settings) && $List) || ($type_crud !== 'list' && $permission['list'])) {
        $hooks_out[] = $List_url;
    }

    if ($type_crud === 'list' && $permission['inset']) {
        $hooks_out[] = $Insert_url;
    }

    if ($type_crud === 'list' && $permission['truncate']) {
        $hooks_in[] = $Truncate_url;
    }

    if ($type_crud === 'list' && $permission['update'])
    {
        $hooks_in[] = [
            'pre_icon' => 'fas fa-pencil',
            'title'    => 'Edição rápida',
            'url'      => '#',
            'attr'     => 'aria-controls: (bulk-edit);',
        ];
    }

    if ($type_crud === 'update' && $permission['view']) {
        $hooks_out[] = $View_url;
    }

    if ($type_crud === 'view' && $permission['update']) {
        $hooks_out[] = $Edit_url;
    }

    if ($permission['duplicate'] && ($type_crud === 'update' || $type_crud === 'view')) {
        $hooks_in[] = $Duplicate_url;
    }

    if ($permission['delete'] && ($type_crud === 'update' || $type_crud === 'view')) {
        $hooks_in[] = $Delete_url;
    }

    // Compactação de ações
    if (!empty($crud_panel['minimize_actions']))
    {
        $hooks_in  = array_merge($hooks_out, $hooks_in);
        $hooks_out = [];
        $hooks_in  = [
            'pre_icon' => 'fas fa-ellipsis-vertical',
            'color'    => 'btn-settings',
            'options'  => $hooks_in,
        ];
    }

    else
    {
        if ($type_crud !== 'list') $hooks_out = array_merge($hooks_out, $hooks_in);

        else
        {
            $hooks_in = [
                'title'   => 'Ações em massa',
                'color'   => 'btn-st',
                'options' => $hooks_in,
            ];
        }
    }

    return [
        'hooks_out' => $hooks_out,
        'hooks_in'  => $hooks_in,
        'buttons'   => [
            'List_url'      => $List_url,
            'Insert_url'    => $Insert_url,
            'View_url'      => $View_url,
            'Edit_url'      => $Edit_url,
            'Truncate_url'  => $Truncate_url,
            'Duplicate_url' => $Duplicate_url,
            'Order_url'     => $Order_url,
            'Delete_url'    => $Delete_url,
        ],
    ];
}



function load_custom_tables_data(array $fields, string $table_crud, string $foreign_key, $id, array $base_data = []): array
{
    $tables = [ $table_crud => $base_data ];
    $added  = [];

    foreach ($fields as $bowl)
    {
        $custom_table = $bowl['settings']['table'] ?? null;
        $storage_mode = $bowl['settings']['storage_mode'] ?? null;

        // Skip fields stored as JSON
        if ($storage_mode === 'json') continue;

        if (!empty($custom_table) && !in_array($custom_table, $added))
        {
            $cols = show_columns($custom_table);

            $order_by = in_array('order_reg', $cols) ? 'order_reg' : 'id';

            $sql = "SELECT * FROM {$custom_table} WHERE {$foreign_key} = {$id} ORDER BY {$order_by} ASC";

            $tables[$custom_table] = ($bowl['type_field'] ?? '') === 'field_repeater'
                ? get_results($sql)
                : get_result($sql);

            $added[] = $custom_table;
        }
    }

    return $tables;
}



/**
 * This function is responsible for printing a form or a list, depending on the specified form type.
 *
 * @param int $piece_id The ID of the form to be printed.
 * @param bool $subscribers_only (Optional) A boolean parameter to indicate whether only the signa should be printed. The default value is false.
 *
 * @return void
 */
// function crud_piece($piece_id, bool $subscribers_only = false)
function crud_piece(array $Attr = [])
{
    //Define global variables
    global $page, $current_user, $info;

    $piece_id = $Attr['piece_id'] ?? null;


    /**
     *
     * Execute the query to build the form
     *
     */
    $status_id = is_dev()
        ? ' status_id != 2'
        : ' status_id = 1';

    $crud = get_crud_piece($piece_id);
    $piece_id = $crud['id'] ?? null;

    if (!$piece_id)  return 'This CRUD does not exist.';


    /**
     * Preconfigure the CRUD panel and list settings.
     */
    $type_crud      = $crud['type_crud'];
    $crud_panel    = $crud['crud_panel'];
    $list_settings = $crud['list_settings'];
    $form_settings = $crud['form_settings'];
    $related_to    = $crud['related_to'] ?? 'table';
    $view_mode     = $form_settings['view_mode'] ?? 'default';
    $custom_urls    = $crud['custom_urls'];
    $pages_list    = $crud['pages_list'];


    /**
     *
     * Set the main table.
     *
     */
    $table_crud     = in_array($type_crud, ['update', 'view'])
        ? force_final_table($crud['table_crud'], $related_to)
        : $crud['table_crud'];

    $foreign_key    = $crud['foreign_key'];
    $crud_id        = $crud['crud_id'] ?? '';


    /**
     *
     * Change the logic when has token.
     *
     */
    $token = null;
    if (!empty($_GET['token']))
    {
        $token = token_get_row([
            'type' => "form-progress:{$piece_id}",
            'token' => $_GET['token']
        ]);

        if (!is_null($token))
        {
            $type_crud = 'update';
            $Attr['register_id'] = $token['resource_id'];

            if (!empty($token['meta']['step'])) {
                $form_settings['step'] = $token['meta']['step'];
            }
        }
    }


    /**
     *
     * Take the data to put in the Crud.
     *
     */
    if (in_array($type_crud, ['update', 'view']))
    {
        /**
         *
         * When is related logged user
         *
         */
        if ($related_to == 'logged_in_user')
        {
            $crud['login_required'] = true;

            if (is_user_logged_in()) {
                $Attr['register_id'] = $current_user['id'];
            }
        }

        $id = $Attr['register_id'] ?? id_by_get();
        $_GET['id'] = $id;


        if (!is_null($token))
        {
            $type_crud               = 'update';
            $Attr['register_id']    = $token['resource_id'];
            $crud['login_required'] = false;

            if (!empty($token['meta']['step'])) {
                $form_settings['step'] = $token['meta']['step'];
            }
        }


        /**
         *
         * When is not table register.
         *
         */
        if ($related_to != 'system_info')
        {
            $data = get_result("SELECT * FROM {$table_crud} WHERE id = '{$id}'");

            if (empty($data)) {
                return alert_message("IF_NONEXISTENT_ID", 'alert');
            }
        }


        /**
         *
         * When is about the system infos.
         *
         */
        else
        {
            $data = [];
            $data = tb_info_serialize(['group' => 'none']);
        }
    }


    /**
     *
     * If permission is denied, short-circuit with system-standard JSON
     *
     */
    $permission_type = ($type_crud == 'list')
        ? 'view'
        : $type_crud;

    if (!load_permission($crud_id, $permission_type)) {
        return alert_message("ER_INVALID_PERMISSION", 'alert');
    }


    /**
     *
     * Shows a message saying that user must be logged in to continue.
     *
     */
    if (!is_user_logged_in() && !empty($crud['login_required'])) {
        return alert_message("IF_ONLY_LOGGED_USERS", 'alert');
    }


    /*
     * Break if this piece does not exist.
     */
    if (count($crud) == 0) return;


    /**
     * Plus one view to the form form
     */
    query_it("UPDATE tb_cruds SET views_count=views_count+1 WHERE id='{$piece_id}'");


    /**
     * Configure the CRUD panel.
     */
    $crud_panel = [
        'form_name'        => $crud['piece_name'],
        'show_name'        => in_array('show_name', $crud_panel),
        'minimize_actions' => in_array('minimize_actions', $crud_panel),
        'show_panel'       => !empty($crud_panel['show_panel']),
    ];


    /**
     * Define URL to the adjacent pages and permissions.
     */
    $permissions = prepare_crud_permissions($crud_id, $pages_list);


    /**
     * Prepare hooks
     */
    $hooks = prepare_crud_hooks([
        'type_crud' => $type_crud,
        'crud_panel' => $crud_panel,
        'permission' => $permissions,
        'list_settings' => $list_settings,
        'custom_urls' => $custom_urls,
        'crud_id' => $crud_id,
        'piece_id' => $piece_id,
        'foreign_key' => $foreign_key,
        'pages_list' => $pages_list
    ]);

    $hooks_out = $hooks['hooks_out'];
    $hooks_in  = $hooks['hooks_in'];
    extract($hooks['buttons']);


    /**
     *
     * Form treatment
     *
     */
    if ( is_array($crud) AND ($type_crud == 'insert' OR $type_crud == 'update') )
    {
        /**
         * Print the questions accordind with "type_field" and "subscribers_only"
         */
        if ( isset($current_user['signature_id']) && (function_exists('signatures_version') && is_basic_signature($current_user['signature_id'])) ) {
            $subscribers_only = (!$subscribers_only) ? ' AND subscribers_only = 0' : '';
        } else {
            $subscribers_only = '';
        }


        $fields = get_results(
        "SELECT * FROM tb_cruds_fields
        WHERE $status_id
        AND crud_id = '{$piece_id}'
        $subscribers_only
        AND type_field IS NOT Null
        ORDER BY order_reg ASC");


        if ($type_crud == 'update')
        {
            $tables = load_custom_tables_data(
                $fields,
                $table_crud,
                $foreign_key,
                $id,
                $data
            );
        }

        // dump($tables);


        /**
         *
         * Decompile data.
         *
         */
        $fields = normalize_fields($fields, false);
        foreach ($fields as $key => $input)
        {

            $table = !empty($input['table'])
                ? $input['table']
                : $table_crud;

            if ($input['type_field'] == 'field_repeater')
            {
                $input['type_crud'] = $type_crud;

                $origName = $input['name'];
                $prefixed = ensure_table_prefix($origName, $table);

                if ($input['storage_mode'] === 'table') {
                    $val = $tables[$input['table']] ?? [];
                }

                else
                {
                    $root = !empty($input['table'])
                        ? ($tables[$table] ?? [])
                        : ($data ?? []);

                    $val = value_by_input_name($prefixed, $root, $table);
                    if ($val === null) {
                        $val = value_by_input_name($origName, $root, null);
                    }

                    if (!is_array($val)) {
                        $val = [];
                    }
                }

                $form_data[$origName] = $val;
            }

            elseif ($input['type_field'] != 'field_repeater' AND !empty($input['name']))
            {
                $name = extract_input_base_name($input['name']);
                $input['og_name'] = $name;
                $input_value  = $data[$name] ?? null;

                $name_exploded = format_input_name($input['name']);
                $new_key = "{$table}{$name_exploded}";
                $input['name'] = $input['input_id'] = $new_key;


                // Exception for Hidden Inputs
                if ($input['type_field'] == 'hidden')
                {
                    if ($input['type'] == 'GET')
                    {
                        if (empty($_GET[$input['name']])) {
                            continue;
                        } else {
                            $input_value = $_GET[$input['name']];
                        }
                    }

                    elseif (($input['type'] == 'SESSION') OR ($input['type'] == 'SERVER')) {
                        continue;
                    }

                    elseif ($input['type'] == 'custom-value')
                    {
                        $input_value  = isset($data[$name])
                            ? $data[$name]
                            : ($input['Value'] ?? null);
                    }
                }

                if (!empty($input['table']))
                {
                    $input_value = $tables[$table][$name] ?? null;

                    if ($type_crud == 'update' && $input['type_field'] == 'upload') {
                        $input['register_id'] = $tables[$table]['id'] ?? '';
                        // dump($input['register_id']);
                    }
                }

                // 1) Descobrir o “root” certo para ler o valor
                $rootForValue = !empty($input['table'])
                    ? ($tables[$table] ?? [])
                    : ($data ?? []);

                // 2) Valor existente seguindo o caminho do name (se houver [] no final, você obtém o array-base)
                $input_value = value_by_input_name($input['name'], $rootForValue, $table);


                // 3) og_name útil (primeira chave após a tabela)
                $path = input_name_to_path($input['name']); // e.g. ['tb_info','brand_colors','primary']
                if (!empty($path) && $path[0] === $table) array_shift($path);
                $input['og_name'] = $path[0] ?? ($input['og_name'] ?? '');

                // 4) Nome final preservando [] e evitando duplicar o prefixo da tabela
                $finalName = ensure_table_prefix($input['name'], $table);
                $input['name']     = $finalName;
                $input['input_id'] = $finalName;

                $form_data[$finalName] = $input_value;

                if (!empty($input['Options']) && is_array($input['Options']))
                {
                    foreach ($input['Options'] as &$opt)
                    {
                        // Trata o "name" da opção da mesma forma que o input principal
                        if (!empty($opt['name']))
                        {
                            $name = $opt['name'];
                            $name_exploded  = format_input_name($name);
                            $opt['og_name'] = extract_input_base_name($name, 'right');
                            $opt['name']    = "{$table}{$name_exploded}";

                            $opt['checked'] = !empty($tables[$table][$name]);
                        }
                    }
                }

            }

            $inputs[] = $input;
        }


            // dump($inputs);


        /**
         *
         * Add the register ID in edit form
         *
         */
        if ($type_crud == 'update' && $related_to === 'table')
        {
            $inputs[] = [
                'type_field' => 'hidden',
                'name' => 'register_id_to_update',
                'Value' => $id,
            ];
            $form_data['register_id_to_update'] = $id;
        }

        if (!is_null($token))
        {
            $inputs[] = [
                'type_field' => 'hidden',
                'name' => 'token',
                'Value' => $_GET['token'],
            ];
            $form_data['token'] = $_GET['token'];
        }

        /**
         *
         * Add the Form ID to process
         *
         */
        $inputs[] = [
            'type_field' => 'hidden',
            'name' => 'id_of_crud_to_process',
            'Value' => $piece_id,
        ];
        $form_data['id_of_crud_to_process'] = $piece_id;

        $inputs[] = [
            'type_field' => 'hidden',
            'name' => 'crud_of_mode_to_process',
            'Value' => $type_crud,
        ];
        $form_data['crud_of_mode_to_process'] = $type_crud;

        $inputs = expand_field_repeaters($inputs, $view_mode);


        /**
         *
         * Make the Payload to create the Form
         *
         */
        $form = [
            'register_id'    => $register_id ?? ($id ?? null),
            'view_mode'      => $form_settings['view_mode'],
            'without_reload' => isset($form_settings['without_reload']),
            'type_crud'       => $type_crud,
            'form_settings'  => $form_settings,
            'form_method'     => $crud['form_method'],
            'form_action'     => $crud['form_action'],
            'attributes'       => $crud['attributes'],
            'contents'   => [
                'inputs' => $inputs,
                'data'   => $form_data,
            ],
            'crud_panel' => $crud_panel,
        ];

        $form['crud_panel']['hooks_in']  = $hooks_in;
        $form['crud_panel']['hooks_out'] = $hooks_out;


        $res = form($form);
    }

    elseif (is_array($crud) AND ($type_crud == 'list'))
    {

        $list_result = prepare_crud_list([
            'crud' => $crud,
            'piece_id' => $piece_id,
            'list_settings' => $list_settings,
            'table_crud' => $table_crud,
            'permissions' => $permissions,
            'custom_urls' => $custom_urls,
            'list_page' => $hooks['buttons']
        ]);

        // var_dump($crud);

        if (!empty($list_result['columns']))
        {
            $table = [
                'crud_id'       => $piece_id,
                'data_table'    => in_array('data_table', $list_settings),
                'limit_results' => $list_settings['limit_results'],
                'settings'      => $list_settings,
                'head'          => $list_result['head'],
                'body'          => $list_result['body'],
                'crud_panel'    => $crud_panel,
            ];

            $table['crud_panel']['hooks_in']  = $hooks_in;
            $table['crud_panel']['hooks_out'] = $hooks_out;

            $res = table($table);
        }

        else {
            $res = alert_message("IF_UNLOADED_FORM", 'alert');
        }
    }

    elseif ( is_array($crud) AND ($type_crud == 'view') )
    {

        /**
         *
         * Print the questions accordind with "type_field"
         *
         */
        $bowls = get_results(
        "SELECT * FROM tb_cruds_fields
        WHERE $status_id
        AND crud_id = '{$piece_id}'
        AND (
            type_field IS NOT Null
            AND type_field != 'submit_button'
            AND type_field != 'hr'
            AND type_field != 'shortcode'
            AND type_field != 'carousel'
            AND type_field != 'facial'
            AND type_field != 'break_line'
        )
        ORDER BY order_reg ASC");

        /**
         *
         * Decompile data.
         *
         */
        foreach ($bowls as $key => $values) $tbody[] = (array) $values['settings'] + $values;


        /**
         *
         * Take the data to put in the list
         *
         */
        $params['table'] = $table_crud;
        $params['where'] = [
            [
                'field'    => 'id',
                'value'    => $id,
                'operator' => '=',
            ]
        ];
        $query = query_builder($params);
        $data  = get_result($query);


        $tables = load_custom_tables_data($bowls, $table_crud, $foreign_key, $id, $data);
        $tbody = expand_field_repeaters($tbody);
        // var_dump($tables);
        // var_dump($tbody);


        /**
         *
         * Make the TBody of Table
         *
         */
        $content = [];
        $is_main_table = false;


        /**
         *
         * Add the Form ID to be show
         *
         */
        foreach($tables as $table_name => $full_data)
        {
            $is_main_table = ($table_crud == $table_name)
                ? true
                : false;

            $valid_data = ($is_main_table)
                ? [$full_data]
                : $full_data;


            if (!$is_main_table) {
                $valid_data = $full_data;
            }

            else {
                $content[0][] = [ 'title' => '#ID', 'value' => $data['id'] ];
            }


            foreach($valid_data as $data)
            {
                $row_group = [];

                if (!empty($data['id']))
                {
                    $row_group[] = [
                        'title' => '#ID',
                        'value' => $data['id'] ?? ''
                    ];
                }

                foreach($tbody as $key => $body)
                {

                    $items = !empty($body['childs'])
                        ? $body['childs']
                        : [$body];

                    $is_child = !empty($body['childs'])
                        ? true
                        : false;

                    $table = (!empty($body['table']))
                        ? $body['table']
                        : $table_crud;


                    foreach($items as $field)
                    {

                        if ($table != $table_name) continue;
                        if (empty($field['name'])) continue;


                        $function_view     = $field['function_view'] ?? '';
                        $function_proccess = $field['function_proccess'] ?? '';


                        $name = $field['name'];
                        $name = extract_input_base_name($name);

                        $type_field = $field['type_field'];

                        // Content Title
                        $title = ($field['type_field'] == 'status_selector')
                            ? $row['title'] = 'Status'
                            : (empty($field['label']) ? $name : $field['label']);


                        $field_value  = isset($data[$name])
                            ? $data[$name]
                            : ($field['Value'] ?? null);


                        if ($field['type_field'] != 'field_repeater' AND !empty($name))
                        {

                            $ctx = [
                                'permissions' => $permissions,
                                'title' => $title,
                                'field_value' => $field_value,
                                'name' => $name,
                                'field' => $field,
                                'id' => $data['id'],
                                'data' => $tables[$table],
                                'table_crud' => $table_crud,
                                'function_view' => $function_view,
                                'function_proccess' => $function_proccess,
                            ];

                            load_input($type_field, 'view');

                            $process_function = "view_{$type_field}_field";
                            if (function_exists($process_function)) {
                                $value          = $process_function($ctx) ?? $field_value;
                            }

                            // Rest of them
                            else
                            {
                                if (!empty($field_value)) {
                                    $value = $field_value;
                                }
                                else {
                                    $view   = function_view($function_view, $name, $tables[$table]);
                                    $value  =  $view ?? '-';
                                }
                            }
                        }


                        $row = [
                            'title' => $title,
                            'value' => $value
                        ];

                        if (!$is_main_table) {
                            $row_group[] = $row;
                        } else {
                            $content[0][] = $row;
                        }
                    }
                }

                if (!$is_main_table && !empty($row_group)) {
                    $content[$table_name][] = $row_group;
                }

                /**
                 * Add the dates to be shown.
                 */
                if ($is_main_table)
                {
                    if (!empty($data['created_at'])) $content[0][] = [
                        'title' => 'Criado em',
                        'value' => date('d/m/Y - H:i:s', strtotime($data['created_at']))
                    ];

                    if (!empty($data['updated_at'])) $content[0][] = [
                        'title' => 'Modificado em',
                        'value' => date('d/m/Y - H:i:s', strtotime($data['updated_at']))
                    ];
                }
            }
        }

        // var_dump($content['patch_notes']);


        /**
         * Make the Payload to create the Table
         */
        $view = [
            'content'    => $content,
            'crud_panel' => $crud_panel,
        ];

        $view['crud_panel']['hooks_in']  = $hooks_in;
        $view['crud_panel']['hooks_out'] = $hooks_out;

        $res = view($view);       // Create the View
    }

    else {
        $res = alert_message("IF_UNLOADED_FORM", 'alert');
    }
    // Verify the current user permission

    return $res;
}


function expand_field_repeaters(array $bowls, string $view_mode = 'default'): array
{
    // em default/only_fields o divider é omitido e não é contêiner
    $omitDividers = ($view_mode === 'default' || $view_mode === 'only_fields' || $view_mode === 'only_form');

    // contêineres válidos conforme o modo
    $containers = $omitDividers ? ['field_repeater'] : ['field_repeater', 'divider'];

    // em tabs_form, esses tipos podem (e devem) ficar na raiz (depth-0)
    $forceDepthZeroTypes = ($view_mode === 'tabs_form') ? ['submit_button', 'hidden'] : [];

    // nó raiz artificial
    $root  = ['depth' => -1, 'childs' => []];
    $stack = [&$root];

    foreach ($bowls as $field)
    {
        $type = $field['type_field'] ?? '';
        $field['depth']  = (int)($field['depth'] ?? 0);
        $field['childs'] = [];

        // omite divider quando requerido
        if ($omitDividers && $type === 'divider') {
            continue;
        }

        // força depth-0 em tabs_form para submit_button/hidden
        if (!empty($forceDepthZeroTypes) && in_array($type, $forceDepthZeroTypes, true)) {
            $field['depth'] = 0;
        }

        // sobe a pilha até o pai com depth < atual
        while (count($stack) > 1 && (($stack[count($stack)-1]['depth'] ?? -1) >= $field['depth'])) {
            array_pop($stack);
        }

        // anexa ao pai corrente (pode ser a raiz se depth foi 0)
        $parent = &$stack[count($stack)-1];
        $parent['childs'][] = $field;

        // referência do item recém-adicionado
        $idx = count($parent['childs']) - 1;
        $ref = &$parent['childs'][$idx];

        // se for contêiner válido, empilha
        if (in_array($type, $containers, true)) {
            $stack[] = &$ref;
        }

        unset($ref, $parent);
    }

    return $root['childs'];
}



function prepare_crud_list($params)
{
    $crud            = $params['crud'];
    $piece_id        = $params['piece_id'];
    $list_settings   = $params['list_settings'];
    $table_crud      = $params['table_crud'];
    $permissions     = $params['permissions'];
    $custom_urls      = $params['custom_urls'] ?? [];
    $list_page       = $params['list_page'] ?? [];
    $force_data      = $params['force_data'] ?? false;
    $filters         = $params['filters'] ?? [];

    extract($list_page);

    $status_id = is_dev()
        ? ' status_id != 2'
        : ' status_id = 1';

    $columns      = [];
    $head         = [];
    $body         = [];
    $ignore_field = [];

    $bowls = get_results(
    "SELECT * FROM tb_cruds_fields
    WHERE $status_id
    AND crud_id = '{$piece_id}'
    -- AND view_in_list = 1
    AND type_field IS NOT Null
    ORDER BY order_reg ASC");

    if (!empty($bowls))
    {
        $available_columns = show_columns($table_crud);

        /**
         *
         * Show ID if required.
         *
         */
        if (in_array('show_id', $list_settings))
        {
            $columns[] = [
                'name' => 'id',
                'label' => '#ID',
                'type_field' => 'hidden',
            ];
        }

        /**
         * Decompile data.
         */
        foreach ($bowls as $key => $values) {
            $columns[] = (array) $values['settings'] + $values;
        }

        foreach ($columns as $k => $value)
        {
            $col_name = explode('[', $value['name'])[0];
            $columns[$k]['name'] = $col_name;

            if (!in_array($col_name, $available_columns)) {
                $ignore_field[] = $col_name;
                // unset($columns[$k]);
                // continue;
            }
        }


        /**
         * Make the THead of Table
         */
        foreach ($columns as $thead)
        {
            if ($thead['type_field'] == 'status_selector') $head[] = 'Status';
            else $head[] = empty($thead['label']) ? $thead['name'] : $thead['label'];
        }

        if ($permissions['duplicate'] || $permissions['view'] || $permissions['update'] || $permissions['delete']) $head[] = 'Ações';

        /**
         * Make the TBody of Table
         */
        if ($force_data || !in_array('data_table_async', $list_settings))
        {
            /**
             * Build the query params
             */
            $query_params['table']          = $table_crud;
            $query_params['where_logic']    = 'AND';

            // Pagination
            if (isset($filters['start']) && isset($filters['length'])) {
                $query_params['current_page']       = floor($filters['start'] / $filters['length']) + 1;
                $query_params['registers_per_page'] = intval($filters['length']);
            } elseif (!empty($list_settings['limit_results']) && $list_settings['limit_results'] > 0) {
                $query_params['registers_per_page'] = $list_settings['limit_results'];
            }


            // Ordering
            if (isset($filters['order'][0]))
            {
                $order_col_index = intval($filters['order'][0]['column']);
                $order_dir       = $filters['order'][0]['dir'];

                if (isset($head[$order_col_index]) && !empty($columns[$order_col_index]['name']))
                {
                    $order_field = $columns[$order_col_index]['name'];

                    if (!in_array($order_field, $ignore_field))
                    {
                        $query_params['order_by'][] = [
                            'field' => $order_field,
                            'way'   => $order_dir
                        ];
                    }

                }
            }

            // Search
            if (!empty($filters['search']['value']))
            {
                $search_value = $filters['search']['value'];

                $fields_where = [];
                $fields_where[] = 'id';

                foreach ($columns as $col)
                {
                    $col_name = $col['name'];
                    if (!in_array($col_name, $ignore_field)) {
                        $fields_where[] = $col_name;
                    }
                }

                $query_params['where'][] = [
                    'field'    => 'CONCAT_WS(" ", ' . implode(',', $fields_where) . ')',
                    'value'    => "%$search_value%",
                    'operator' => 'LIKE',
                    'skip_sanitize' => true
                ];
            }


            $query = query_builder($query_params);
            $tbody = get_results($query);

            foreach ($tbody as $data)
            {
                $row     = [];
                $counter = 1;

                $data['register_id'] = $data['id'];

                foreach ($columns as $field)
                {
                    $function_view     = $field['function_view'] ?? '';
                    $function_proccess = $field['function_proccess'] ?? '';

                    $type_field = $field['type_field'] ?? '';

                    $name = $field['name'];
                    $name = extract_input_base_name($name);

                    $field_value  = isset($data[$name])
                        ? $data[$name]
                        : ($field['Value'] ?? null);

                    $ctx = [
                        'permissions' => $permissions,
                        // 'title' => $title,
                        'field_value' => $field_value,
                        'name' => $name,
                        'field' => $field,
                        'id' => $data['id'] ?? null,
                        'data' => $data,
                        'table_crud' => $table_crud,
                        'function_view' => $function_view,
                        'function_proccess' => $function_proccess,
                    ];

                    load_input($type_field, 'view');
                    $process_function = "view_{$type_field}_field";
                    if (function_exists($process_function)) {
                        $data[$name] = $process_function($ctx) ?? $field_value;
                    }

                    // Rest of them
                    $view = function_view($function_view, $name, $data);

                    $row[] = $view ?? '-';


                    /**
                     * Ações
                     */
                    if (count($columns) == $counter)
                    {
                        $hooks_out_row = $hooks_in_row = [];

                        foreach ($custom_urls as $url)
                        {
                            $url = (array) $url;
                            if ($url['where'] != 'out-content' && $url['where'] != 'in-content') continue;

                            if (($url['type'] == 'custom') || ($url['type'] == 'page' && load_permission($url['url'])))
                            {
                                $url['url'] = ($url['type'] == 'page') ? get_url_page($url['url'], 'full') : $url['url'];
                                if ($url['attr'] == '[id]') $url['url'] .= "?id={$data['id']}";
                                ($url['where'] == 'out-content') ? ($hooks_out_row[] = $url) : ($hooks_in_row[] = $url);
                            }
                        }

                        $Order_url_row     = $Order_url;
                        $Duplicate_url_row = $Duplicate_url;
                        $View_url_row      = $View_url;
                        $Edit_url_row      = $Edit_url;
                        $Delete_url_row    = $Delete_url;

                        $Order_url_row['url']     .= $data['id'];
                        $Duplicate_url_row['url'] .= $data['id'];
                        $View_url_row['url']      .= $data['id'];
                        $Edit_url_row['url']      .= $data['id'];
                        $Delete_url_row['url']    .= $data['id'];

                        $Order_url_row['id']      = $data['id'];
                        $Duplicate_url_row['id']  = $data['id'];
                        $View_url_row['id']       = $data['id'];
                        $Edit_url_row['id']       = $data['id'];
                        $Delete_url_row['id']     = $data['id'];

                        if ($permissions['order'])    $hooks_out_row[] = $Order_url_row;
                        if ($permissions['duplicate']) $hooks_in_row[] = $Duplicate_url_row;
                        if ($permissions['view'])     $hooks_in_row[] = $View_url_row;
                        if ($permissions['update'])     $hooks_in_row[] = $Edit_url_row;
                        if ($permissions['delete'])   $hooks_in_row[] = $Delete_url_row;

                        $hooks_in_row = [
                            'title'   => 'Ações',
                            'color'   => 'btn-st',
                            'options' => $hooks_in_row,
                        ];

                        if ($permissions['duplicate'] || $permissions['view'] || $permissions['update'] || $permissions['delete'])
                            $row[] = table_actions($hooks_out_row, $hooks_in_row);
                    }

                    $counter++;
                }

                $body[] = $row;
            }
        }
    }

    return [
        'columns' => $columns,
        'head'    => $head,
        'body'    => $body,
    ];
}
