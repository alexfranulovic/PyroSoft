<?php
if(!isset($seg)) exit;


function put_name_as_key_in_fields(array $cruds_fields = [], string $main_table = '', bool $in = false)
{
    $fields = [];
    foreach ($cruds_fields as $key => $field)
    {
        if (!empty($field['name']))
        {
            $table = !empty($field['table']) ? $field['table'] : $main_table;

            $name = explode('[', $field['name']);

            if (!$in) {
                $fields[$table][$name[0]] = $field;
            }

            else{
                $fields[$name[0]] = $field;
            }
        }

        if (!empty($field['childs']))
        {
            if (!$in)
            {
                $fields[$table]+= put_name_as_key_in_fields($field['childs'], $main_table, true);
                unset($fields[$table][$name[0]]['childs']);
            }

            else {
                $fields+= put_name_as_key_in_fields($field['childs'], $main_table);
                unset($fields[$name[0]]['childs']);
            }
        }
    }

    return $fields;
}


/**
 * Processes a dynamic CRUD form based on configuration in `tb_cruds` and `tb_cruds_fields`.
 *
 * This function handles data insertion or update for a specific CRUD form, validating fields,
 * handling file uploads, executing any field-specific processing functions, and applying
 * unique key constraints. It also tracks submission statistics and builds a response suitable
 * for AJAX consumption, including validation errors, success messages, and redirection if needed.
 *
 * @param array $payload Optional parameters (currently unused but reserved for future expansion).
 * @return array Response payload including status code, messages, field errors, and redirection.
 */
function form_processor(array $payload = [])
{
    global
        $alerts,
        $config,
        $current_user;

    $alert = '';
    $error = false;
    $msg = "ER_UNDEFINED_ERROR";

    /**
     *
     * Returns a error if payload's empty.
     *
     */
    if (empty($payload))
    {
        return [
            'code' => 'error',
            'msg' => 'The payload is empty.'
        ];
    }

    $is_main_table          = false;
    $piece_id               = $payload['id_of_crud_to_process'] ?? false;
    $mode = $original_mode  = $payload['crud_of_mode_to_process'] ?? false;
    $parent_register_id     = $payload['register_id_to_update'] ?? null;
    $schedule_event         = $payload['schedule_event'] ?? null;
    $actual_step            = $payload['actual_step'] ?? null;
    $total_steps            = $payload['total_steps'] ?? null;
    $token                  = $payload['token'] ?? null;
    $current_parent_id      = $parent_register_id;


    /**
     * Brings the CRUD to build the form
     */
    $crud       = get_crud_piece($piece_id);
    $crud_id    = $crud['crud_id'] ?? '';
    $type_crud   = $crud['type_crud'];


    /**
     * Logic of mode
     */
    $mode = $original_mode = $crud['type_crud'] ?? false;
    if ($mode != 'insert' AND $mode != 'update') {
        $error = true;
    }

    /**
     *
     *  When the form is related to logged user.
     *
     */
    $related_to = $crud['related_to'] ?? 'table';
    if ($related_to == 'logged_in_user')
    {
        if ($original_mode == 'update')
        {
            $crud['login_required'] = true;

            if (is_user_logged_in()) {
                // print_r($current_user);
                $current_parent_id = $parent_register_id = $current_user['id'];
            }
        }
    }

    /**
     *
     * Change the logic when has token.
     *
     */
    $token_info = null;
    if (!empty($token))
    {
        $token_info = token_get_row([
            'type' => "form-progress:{$piece_id}",
            'token' => $token
        ]);

        if (!is_null($token_info))
        {
            // print_r($token_info);
            $mode = $original_mode                   = 'update';
            $current_parent_id = $parent_register_id = $token_info['resource_id'];
        }
    }

    // /**
    //  *
    //  * Change the logic when has token.
    //  *
    //  */
    // $token_info = null;
    // if (!empty($token))
    // {
    //     $token_info = token_get_row([
    //         'type' => "form-progress:{$piece_id}",
    //         'token' => $token
    //     ]);

    //     if (!is_null($token_info))
    //     {
    //         // print_r($token_info);
    //         $mode = $original_mode                   = 'update';
    //         $current_parent_id = $parent_register_id = $token_info['resource_id'];
    //     }
    // }


    // /**
    //  *  When the form is related to logged user.
    //  */
    // $related_to = $crud['related_to'] ?? 'table';
    // if ($related_to == 'logged_in_user')
    // {
    //     if ($original_mode == 'update')
    //     {
    //         $crud['login_required'] = true;

    //         if (is_user_logged_in()) {
    //             // print_r($current_user);
    //             $current_parent_id = $parent_register_id = $current_user['id'];
    //         }
    //     }
    // }


    /**
     * If permission is denied, short-circuit with system-standard JSON
     */
    if (!load_permission($crud_id, $mode)) {
        return invalid_permission_response();
    }


    /**
     *
     * Verify the user is logged-in.
     *
     */
    if (!is_user_logged_in() && !empty($crud['login_required'])) {
        return login_required_response();
    }


    /**
     * Brings the inputs settings.
     */
    $status_id = is_dev()
        ? ' status_id != 2'
        : ' status_id = 1';


    $tables = $payload;
    $tables = array_filter($tables, 'is_array');
    unset(
        $tables['register_id_to_update'],
        $tables['id_of_crud_to_process'],
        $tables['crud_of_mode_to_process'],
        $tables['schedule_event'],
        $tables['process-form'],
    );


    // print_r($_FILES);
    // print_r($tables);
    // print_r($payload);
    // exit;

    $form_settings  = $crud['form_settings'] ?? [];
    $view_mode      = $form_settings['view_mode'] ?? 'default';


    /**
     *
     * Set the main table to be executed first.
     *
     */
    $main_table     = $crud['table_crud'];
    $foreign_key    = $crud['foreign_key'];

    if (array_key_exists($main_table, $tables))
    {
        $main_data = [$main_table => $tables[$main_table]];
        unset($tables[$main_table]);

        $tables = $main_data + $tables;
    }


    $cruds_fields = get_results(
    "SELECT * FROM tb_cruds_fields
    WHERE $status_id
    AND crud_id = '{$piece_id}'
    AND type_field IS NOT Null
    ORDER BY order_reg ASC");

    $cruds_fields = normalize_fields($cruds_fields, true);
    $cruds_fields = expand_field_repeaters($cruds_fields);

    $fields = put_name_as_key_in_fields($cruds_fields, $main_table);


    /**
     * Plus one view to the form form
     */
    query_it("UPDATE tb_cruds SET submits_count=submits_count+1 WHERE id='{$piece_id}'");


    /**
     *
     * Sechedule as a event.
     *
     */
    if (!empty($schedule_event['date']) && !empty($schedule_event['hour']))
    {
        // Corrige espaços e formata a hora
        $date = trim($schedule_event['date']);
        $hour = trim(str_replace(' ', '', $schedule_event['hour'])); // ex: "21:30"

        // Monta datetime e converte para timestamp
        $datetime = "{$date} {$hour}";
        $timestamp = strtotime($datetime);

        if ($timestamp && $timestamp > time())
        {
            unset($payload['schedule_event']);

            $slug = "{$crud['piece_name']}";

            if (!empty($current_parent_id)) {
                $slug.= ": #{$current_parent_id}";
            }

            cron_schedule_event([
                'hook'      => "form_processor",
                'slug'      => $slug,
                'args'      => $payload,
                'timestamp' => $timestamp,
                'mode'      => 'crud',
            ]);
        }

        else  $error = true;


        // Return the message.
        $code               = $error ? 'ER' : 'SC';
        $message_code       = "{$code}_TO_SCHEDULE";

        $res =
        [
            'code' => ($code == 'ER') ? 'error' : 'success',
            'detail' => [
                'type' => 'toast',
                'msg' => alert_message($message_code, 'toast'),
                'code' => $message_code,
            ],
        ];

        return $res;
    }


    /**
     *
     * Force default ('') for checkboxes that weren't send in payload
     *
     */
    foreach($fields as $from_table => $field)
    {
        foreach ($field as $field_name => $settings)
        {
            $type = $settings['type'] ?? null;
            $table = $settings['table'] ?? $from_table;

            if (($type === 'checkbox') || ($type === 'switch'))
            {
                if (!isset($tables[$from_table][$field_name])) {
                    $tables[$from_table][$field_name] = '';
                }
            }

        }
    }


    /**
     *
     * Start proccessing the tables data.
     *
     */
    $must_break = false;
    $last_table = $order_reg = null;
    $invalid_inputs = $deferred_functions = $processed_ids = [];
    foreach ($tables as $table_name => $table_data)
    {
        $mode = $original_mode;

        $available_columns = show_columns($table_name);

        $is_main_table = ($main_table == $table_name)
            ? true
            : false;

        $is_multi_row = is_multi_row_array($table_data);

        $data_in_table = $is_multi_row
            ? $table_data
            : [$table_data];

        $data_in_table = remove_all_index_keys($data_in_table);


        /**
         *
         * Delete child records that were not sent by frontend (only on update).
         *
         */
        if (!$is_main_table)
        {
            $existing_ids_in_db = "SELECT id FROM $table_name WHERE {$foreign_key} = '{$current_parent_id}'";

            if (!$is_multi_row)
            {
                $existing_ids_in_db = get_col($existing_ids_in_db);

                if (empty($existing_ids_in_db)) {
                    $mode = 'insert';
                }

                else {
                    $mode = 'update';
                    $data_in_table[0]['id'] = $existing_ids_in_db;
                }
            }

            elseif ($mode === 'update' && in_array('id', $available_columns))
            {
                // Get all existing child IDs for this parent from the database
                $existing_ids_in_db = get_cols($existing_ids_in_db);

                // Collect all child IDs that came from the frontend payload
                $sent_ids_from_front = array_filter(array_map(function ($row) {
                    return isset($row['id']) ? (int) $row['id'] : null;
                }, $data_in_table));

                // Remove null values (for new rows without ID)
                $sent_ids_from_front = array_filter($sent_ids_from_front);

                // Find IDs that exist in database but were not sent (those must be deleted)
                $ids_to_delete = array_diff($existing_ids_in_db, $sent_ids_from_front);

                if (!empty($ids_to_delete)) {
                    $ids_str = implode(',', array_map('intval', $ids_to_delete));
                    query_it("DELETE FROM $table_name WHERE id IN ($ids_str)", false, false);
                }
            }
        }


        $last_table = $table_name;

        // print_r($fields);


        /**
         *
         * It proccess table data.
         *
         */
        foreach ($data_in_table as $k => $data)
        {
            $mode = $original_mode;

            // Ensure this exists before any handlers potentially push file moves
            $pending_moves = $pending_moves ?? [];

            /**
             *
             * Force default ('') for checkboxes that weren't send in payload
             *
             */
            // foreach ($fields[$table_name] as $field_name => $field)
            // {
            //     // print_r($field);

            //     $field_type = $field['type'] ?? null;
            //     $field_table = $field['table'] ?? null;

            //     if (($field_type === 'checkbox') || $field_type === 'switch')
            //     {
            //         if (!array_key_exists($field_name, $data)) {
            //             $data[$field_name] = '';
            //         }
            //     }
            // }


            if (is_array($data))
            {
                /**
                 *
                 * Do exclusive things for the secondaries tables.
                 *
                 */
                if (!$is_main_table)
                {
                    $parent_register_id = null;

                    if (!empty($data['id'])) {
                        $parent_register_id = $data['id'];
                    }

                    elseif (empty($data['id'])) {
                        $parent_register_id = null;
                        $mode = 'insert';
                    }

                    else {
                        $parent_register_id = get_col("SELECT id FROM $table_name WHERE {$foreign_key} = '{$current_parent_id}' LIMIT 1");
                    }


                    // Start the verification to ordenate the registers.
                    if (in_array('order_reg', $available_columns))
                    {
                        if ($order_reg == null OR $table_name != $last_table) {
                            $order_reg = 1;
                        }
                    }

                }


                $verifyer = ($mode == 'insert')
                    ? 'inserted_id'
                    : 'affected_rows';

                if ($mode == 'update') {
                    $data['register_id'] = $parent_register_id;
                }


                /**
                 *
                 * Run functions before the main query.
                 *
                 */
                // print_r($data);
                foreach ($fields[$table_name] as $key => $field)
                {
                    if (!empty($field['run_before_action']) && !empty($field['function_proccess']))
                    {
                        $result = function_proccess($field['function_proccess'], $key, $data);

                        /**
                         * Pre-set function result as value.
                         */
                        if (isset($result)) {
                            $data[$key] = $result;
                        }

                        /**
                         * Pre-set function result as value.
                         */
                        if (is_array($result))
                        {
                            if (isset($result['error']) && $result['error'] === true) {
                                $error = true;
                                $invalid_inputs["{$table_name}[$key]"] = $result['msg'] ?? 'Invalid field';
                            }

                            if (isset($result['value'])) {
                                $data[$key] = $result['value'];
                            }

                            if (isset($result['modify']) && is_array($result['modify'])) {
                                foreach ($result['modify'] as $field_to_update => $new_value) {
                                    $data[$field_to_update] = $new_value;
                                }
                            }
                        }

                    }
                }

                if ($error) continue;

                /**
                 *
                 * Add the hidden inputs type SERVER or SESSION.
                 *
                 */
                $hiddens = array_filter($fields[$table_name], fn($field) =>
                    $field['type_field'] === 'hidden' &&
                    in_array($field['type'], ['SESSION', 'SERVER'])
                );

                foreach ($hiddens as $hidden)
                {
                    $hidden_name = $hidden['name'] ?? false;
                    if (!$hidden_name) continue;

                    $pointer = explode('.', $hidden['pointer'] ?? '');

                    if ($hidden['type'] === 'SERVER') {
                        $data[$hidden_name] = get_value_from_pointer($_SERVER, $pointer) ?? '';
                    }

                    elseif ($hidden['type'] === 'SESSION') {
                        $data[$hidden_name] = get_value_from_pointer($_SESSION, $pointer) ?? '';
                    }
                }




                $field_files = $args = $args_bd = [];
                foreach ($data as $key => $value)
                {
                    if (isset($fields[$table_name][$key]))
                    {
                        $field      = $fields[$table_name][$key];
                        $type       = $field['type'] ?? null;
                        $type_field  = $field['type_field'] ?? null;
                        $function   = $field['function_proccess'] ?? null;

                        $must_continue = false;
                        $detail = null;

                        if (!empty($field['run_after_action']))
                        {
                            $deferred_functions[] = [
                                'function' => $function,
                                'key'      => $key,
                                'data'     => $data,
                            ];
                            continue;
                        }

                        if (!empty($field['run_before_action'])) {
                            $value = $data[$key] ?? null;
                        }
                        elseif ($type_field !== 'status_selector' && $type_field !== 'address_form') {
                            $value = function_proccess($function, $key, $data);
                        }


                        /**
                         *
                         * Build and pass context for the per-type_field processor
                         *
                         */
                        $ctx = [
                            // Common field/meta
                            'field'               => $field,                 // array: full field config
                            'key'                 => $key,                   // string: column name
                            'table_name'          => $table_name,            // string
                            'available_columns'   => $available_columns,     // array

                            // CRUD flow/meta
                            'mode'                => $mode,                  // 'insert'|'update'
                            'related_to'          => $related_to,            // e.g. 'table'|'system_info'
                            'parent_register_id'  => $parent_register_id,    // int|null
                            'current_parent_id'   => $current_parent_id,     // int|null
                            'foreign_key'         => $foreign_key,           // string
                            'is_main_table'       => $is_main_table,         // bool

                            // Values (allow processors to modify)
                            'type'                => $type,                  // e.g. 'text', 'password', 'images', etc.
                            'type_field'          => $type_field,             // e.g. 'basic', 'upload', 'seo_form'
                            'value'               => &$value,                // by-ref: processors can change it
                            'data'                => &$data,                 // by-ref: full row payload
                            'args'                => &$args,                 // by-ref: final args to persist
                            'args_bd'             => &$args_bd,              // by-ref: db payload builder
                            'must_continue'       => &$must_continue,        // by-ref: signal to skip persisting this field
                            'must_break'          => &$must_break,           // by-ref: signal to break the execution
                            'pending_moves'       => &$pending_moves,        // by-ref: for temp -> final file moves
                        ];

                        load_input($type_field, 'process');

                        $process_function = "process_input_{$type_field}";
                        if (function_exists($process_function))
                        {
                            $process_return = $process_function($ctx);

                            $value          = $process_return['value'] ?? $value;
                            $detail         = $process_return['detail'] ?? $detail;
                            $must_continue  = $process_return['must_continue'] ?? $must_continue;
                            $must_break     = $process_return['must_break'] ?? $must_break;

                            if (!empty($process_return['pending_moves'])) {
                                $pending_moves[] = $process_return['pending_moves'];
                            }

                        }

                        if ($must_break) {
                            break;
                        }

                        if ($must_continue) {
                            continue;
                        }


                        /**
                         *
                         * Verifications for unique values.
                         *
                         */
                        if (!empty($field['unique_key']))
                        {
                            $verify_unique_key = "SELECT id FROM $table_name WHERE $key = '$value' AND ($key != NULL OR $key != '')";

                            if ($mode == 'update') {
                                $verify_unique_key.= "AND id != '$parent_register_id'";
                            }

                            $verify_unique_key = count_results($verify_unique_key);

                            if ($verify_unique_key > 0)
                            {
                                $error = true;
                                $input_name = "{$table_name}[$key]";

                                $invalid_inputs[$input_name] = 'Já há um registro com esse dado.';
                            }
                        }


                        /**
                         *
                         * Remove the template.
                         *
                         */
                        if (is_array($value) && isset($value['__index__'])) {
                            unset($value['__index__']);
                        }

                    }

                    /**
                     *
                     * After all verify if this field exists in final table.
                     *
                     */
                    if (in_array($key, $available_columns) || $related_to == 'system_info') {
                        $args[$key] = $value;
                    }
                }
                unset($args['register_id']);
                    // print_r($args);

                if ($must_break) {
                    break;
                }

                /**
                 *
                 *  Processing data in Secondary entities.
                 *
                 */
                if (!$is_main_table)
                {
                    // Set the child id if exists.
                    $child_id = $args['id'] ?? null;
                    unset($args['id']);

                    /**
                     *
                     * Set the register order (if its table has this option).
                     *
                     */
                    if (in_array('order_reg', $available_columns)) {
                        // $args['order_reg'] = $k+1;
                        $args['order_reg'] = $order_reg;
                    }

                    if ($mode == 'insert') {
                        if (in_array('created_at', $available_columns)) $args['created_at'] = 'NOW()';
                        $args[$foreign_key] = $current_parent_id;
                        $args_bd = $args;
                    }

                    elseif ($mode == 'update')
                    {
                        if (in_array('updated_at', $available_columns)) $args['updated_at'] = 'NOW()';

                        $exists_where = ($child_id == null)
                            ? "id = '{$child_id}'"
                            : "{$foreign_key} = '{$current_parent_id}'";

                        /**
                         *
                         * Check if register exists.
                         *
                         */
                        $exists = get_col("SELECT id FROM $table_name WHERE {$exists_where} LIMIT 1");
                        if ($exists)
                        {
                            $where = ($child_id != null)
                                ? where_equal_id($child_id)
                                : [
                                [
                                    'field'    => $foreign_key,
                                    'operator' => '=',
                                    'value'    => $current_parent_id,
                                ],
                            ];

                            $args_bd['data']  = $args;
                            $args_bd['where'] = $where;
                        }

                        else
                        {
                            if (in_array('created_at', $available_columns)) $args['created_at'] = 'NOW()';
                            $args[$foreign_key] = $current_parent_id;
                            $args_bd = $args;
                            $mode = 'insert';
                        }
                    }
                }


                /**
                 *
                 * Processing data in Main entity.
                 *
                 */
                else
                {
                    if ($mode == 'insert') {
                        if (in_array('created_at', $available_columns)) $args['created_at'] = 'NOW()';
                        $args_bd = $args;
                    }

                    elseif ($mode == 'update')
                    {
                        if (in_array('updated_at', $available_columns)) $args['updated_at'] = 'NOW()';
                        $args_bd['data']  = $args;
                        $args_bd['where'] = where_equal_id($current_parent_id);
                    }
                }


                /**
                 *
                 * Lights, camera, action.
                 *
                 * This case serves when the form is not related to system_info.
                 *
                 */
                $id_new_register = null;
                if ($related_to != 'system_info' && (!$error && !empty($args_bd)))
                {
                    $query = $mode($table_name, $args_bd, false, false);
                    $id_new_register = $verifyer();

                    $current_id = ($mode === 'insert')
                        ? $id_new_register
                        : $parent_register_id;

                    if (!$is_main_table && !empty($current_id)) {
                        $processed_ids[$table_name][] = $current_id;
                    }

                    if ($is_main_table AND $mode == 'insert') {
                        $current_parent_id = $id_new_register;
                    }
                }


                /**
                 *
                 * Lights, camera, action.
                 *
                 * This case serves when the form is related to system_info.
                 *
                 */
                else
                {
                    foreach ($args as $key => $value) {
                        $query = update_option($key, $value);
                    }
                }

            }

            /**
             *
             * Handles post-query processing for file movements and hook execution after a successful or failed insert.
             *
             */
            if (!empty($query->code) && ($query->code !== 'error'))
            {
                // Decide ID final para mover (insert usa $id_new_register; update usa $parent_register_id)
                if ($view_mode == 'steps_form')
                {
                    $finalId = ($type_crud === 'insert')
                        ? $id_new_register
                        : $parent_register_id;

                    if (!empty($token_info['resource_id'])) {
                        // $finalId = $token_info['resource_id'];
                        // $finalId = $parent_register_id;
                    }
                }
                else
                {
                    $finalId = ($mode === 'insert')
                        ? $id_new_register
                        : $parent_register_id;
                }


                if (!empty($pending_moves))
                {
                    foreach ($pending_moves as $mv)
                    {
                        if (empty($mv['files'])) continue;

                        // Destino:
                        // - UPDATE: 'dest_base' JÁ tem /{parent_register_id}/
                        // - INSERT: completar com /{id_new_register}/
                        $dest = rtrim($mv['dest_base'], '/').'/';
                        if ($related_to != 'system_info')
                        {
                            if (!$mv['is_update']) {
                                $dest .= "{$finalId}/";
                            }
                        }

                        foreach ($mv['files'] as $fname)
                        {
                            if (!$fname) continue;

                            if (!is_temp_filename($fname)) continue;

                            move_temp_file_to_final($fname, [
                                'storage'    => $mv['storage'] ?? 'local',
                                'temp_dir'   => $mv['temp_dir'],
                                'final_name' => $mv['final_name'] ?? null,
                                'dest_base'  => $dest,
                                // 'final_id'   => $finalId,
                                'type'       => $mv['type'] ?? 'archives',
                                'Src'        => $mv['Src'] ?? '',
                                'related_to' => $related_to,
                            ]);
                        }

                    }
                }
            }


            /**
             *
             * Add to ordenate the registers.
             *
             */
            if (in_array('order_reg', $available_columns))  $order_reg++;
        }

        if ($must_break) {
            break;
        }

    }

        // print_r($pending_moves);

    /**
     *
     * Execute hook functions after success.
     *
     */
    if (!empty($query->code) && ($query->code !== 'error'))
    {
        run_after_action_hooks(
            $deferred_functions/*,
            $parent_register_id*/
        );
    }


    /**
     *
     * Validate the query.
     *
     */
    if (!empty($invalid_inputs)) {
        $code = 'ER';
    }

    elseif (!isset($query)) $code = 'ER';

    else
    {
        $id_new_register = $verifyer() ?? null;
        if ($query->code == 'success')
        {
            $code = 'SC';
        }
        elseif ($query->code == 'alert') {
            $code = 'AL';
            $alert.= "<br><br>".$query->message;
        }
        else {
            $code = 'ER';
            $alert.= "<br><br>".$query->message;
        }
    }

    /**
     *
     * Return the message.
     *
     */
    $message_code       = "{$code}_TO_". strtoupper($original_mode);
    $new_body           = $alerts[$message_code];
    $new_body['body']   = $alerts[$message_code]['body'] . $alert;


    /**
     *
     * Build the response.
     *
     */
    $res =
    [
        'code' => ($code == 'ER') ? 'error' : 'success',
        'detail' => !empty($detail) ? $detail : [
            'type' => 'toast',
            'msg' => alert_message($new_body, 'toast'),
            'code' => $message_code,
        ],
    ];

    // Add inputs to revalidate.
    if (!empty($invalid_inputs)) {
        $res['invalid_inputs'] = $invalid_inputs;
    }

    // Return the new ID of the main query
    if (!empty($current_parent_id) && ($related_to != "logged_in_user")) {
        $res['id'] = $current_parent_id;
    }

    // Redirect to results page.
    if (($code == 'SC') && !empty($crud['result_page'])) {
        $res['redirect'] = get_url_page($crud['result_page'], 'full');
    }

    // Return all processed ids.
    if (!empty($processed_ids)) {
        $res['processed_ids'] = $processed_ids;
    }


    /**
     *
     * Special treatments for steps form.
     *
     */
    if ($view_mode == 'steps_form')
    {
        if ($actual_step != $total_steps)
        {
            unset(
                $res['detail'],
                $res['redirect']
            );

            if ($code == 'SC')
            {
                /**
                 * Update token
                 */
                if (!is_null($token_info))
                {
                    update('tb_tokens', [
                        'data' => [
                            'meta' => [ 'step' => ($actual_step+1) ],
                        ],
                        'where' => where_equal_id($token_info['id']),
                    ]);
                }

                /**
                 * Create token.
                 */
                else
                {
                    $token = token_create([
                        'type'      => "form-progress:$piece_id",
                        'mode'      => 'md5',
                        'length'    => 12,
                        'overwrite' => true,
                        'resource_id' => $current_parent_id,
                        'meta'      => [
                            'step' => ($actual_step+1)
                        ],
                    ]);

                    if (!is_null($token)) {
                        $res['token'] = $token['token'];
                    }
                }
            }
        }

        /**
         * Last step.
         */
        else
        {
            token_validate([
                'token' => $token,
                'type' => "form-progress:$piece_id",
            ]);
        }
    }

    /**
     *
     * If is related to logged_in_user and Insert do somethings diferent.0
     *
     */
    if (($code == 'SC') AND ($type_crud === 'insert') AND ($related_to == "logged_in_user"))
    {
        $login_settings = $config['login_settings'];
        $login_after_register = $login_settings['register_page']['login_after_register'] ?? false;

        /**
         *
         * Force login if set.
         *
         */
        if (!is_user_logged_in() && $login_after_register)
        {
            $res['login'] = user_login([
                'user' => $current_parent_id,
                'force' => true,
            ]);
            // $res['login']['user'] = $current_parent_id;
        }

        /**
         *
         * Force the lowest role if nothing was informed.
         *
         */
        if (empty($tables['tb_users']['roles'])) {
            edit_user_role_assignments($current_parent_id, lowest_role_user());
        }
    }


    return $res;
}


/**
 * Executes a list of deferred functions after a form action (insert or update) is completed.
 *
 * This is commonly used to trigger additional logic such as logging, notifications,
 * or custom processing after the main database operation is successful.
 *
 * Each function receives a data array merged with the `register_id` of the affected record.
 *
 * @param array $functions    List of deferred functions with keys: 'function', 'key', and 'data'.
 * @param int   $register_id  ID of the record that was inserted or updated.
 */
function run_after_action_hooks(array $functions, int $register_id = null)
{
    foreach ($functions as $deferred)
    {
        $data = $deferred['data'];

        if (!is_null($register_id)) {
            $data['register_id'] = $register_id;
        }

        function_proccess(
            $deferred['function'] ?? '',
            $deferred['key'] ?? '',
            $data
        );
    }
}


/**
 * Recursively removes all "__index__" keys from an array.
 *
 * @param array $data
 * @return array
 */
function remove_all_index_keys(array $data): array
{
    foreach ($data as $key => $value)
    {
        if ($key === '__index__') {
            unset($data[$key]);
            continue;
        }

        if (is_array($value)) {
            $data[$key] = remove_all_index_keys($value);
        }
    }
    return $data;
}


function is_multi_row_array($array)
{
    if (!is_array($array)) return false;

    $keys = array_keys($array);
    $all_numeric = array_filter($keys, 'is_numeric');

    if (count($all_numeric) === count($keys)) return true;

    if (in_array('__index__', $keys)) return true;

    return false;
}



// function auto_fill_name_by_cpf($key) {
//     return [
//         'modify' => [
//             'cpf' => '123.456.789-00',
//             'email' => 'auto@email.com',
//             'genero' => 'masculino'
//         ]
//     ];
// }
