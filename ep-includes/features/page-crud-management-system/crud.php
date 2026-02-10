<?php
if(!isset($seg)) exit;

/*
 * CRUD functions in second.
 *
 * It is not recommended to you updating or custom this file.
 *
 */

/**
 * Returns the input types as an array or formatted for selects.
 *
 * @param bool $ForSelects Indicates whether the output should be formatted for selects.
 * @return mixed|array|string The input types.
 */
function type_input(bool $ForSelects = false)
{
    $arr = [
        'text',
        'number',
        'url',
        'email',
        // 'range',
        'color',
        'date',
        'datetime-local',
        'mounth',
        'week',
        'password',
        'time',
        'search',
        'button',
        'tel',

        // Custom Input Type
        'name' => 'price',
    ];

    $str = $arr;

    if ($ForSelects == true) {
        $str = '';
        $count = 1;
        foreach($arr as $k => $i){
            $str .= $count."|| ".$i."|| ".$i.";";
            $count++;
        }
    }

    return $str;
}


function common_inputs_for_crud(string $type_form, string $selector, $data = null, $counter = 1)
{
    if ($selector == 'id')
    return input(
        'hidden',
        $type_form,
        [
            'name' => "Fields[$counter][id]",
            'Value' => $data,
            'Required' => true,
        ]
    );

    if ($selector == 'depth')
    return input(
        'hidden',
        $type_form,
        [
            'name' => "Fields[$counter][depth]",
            'Value' => $data,
            'Required' => true,
        ]
    );

    if ($selector == 'type_field')
    return input('hidden', $type_form,
        [
            'name' => "Fields[$counter][type_field]",
            'input_id' => "type_field-$counter",
            'Value' => $data,
            'Required' => true,
        ]
    );

    if ($selector == 'Alert')
    return input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Alert do Input',
            'Placeholder' => 'O alert aparece embaixo do Input',
            'name' => "Fields[$counter][Alert]",
            'input_id' => "Alert-$counter",
            'Alert' => 'Isso é o Alerta',
            'Value' => $data,
        ]
    );

    if ($selector == 'table')
    return input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Tabela do Input',
            'name' => "Fields[$counter][table]",
            'input_id' => "table-$counter",
            'Alert' => 'Se vazio, a tabela do CRUD vai ser atribuída',
            'Value' => $data,
        ]
    );

    if ($selector == 'label')
    return input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Label',
            'Placeholder' => 'Ex: Qual é seu nome?',
            'name' => "Fields[$counter][label]",
            'input_id' => "label-$counter",
            'Value' => $data,
        ]
    );

    if ($selector == 'obs')
    return input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Observação',
            'obs' => 'Exatamente isso que você está lendo! Isso é uma observação. Ex de uso: Campos monetários são em (R$)',
            'Placeholder' => 'Coloque o que achar pertinente.',
            'name' => "Fields[$counter][obs]",
            'input_id' => "obs-$counter",
            'Value' => $data,
            'Alert' => 'Apenas para inputs normais.'
        ]
    );

    if ($selector == 'div_class')
    return input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Classes da DIV',
            'Placeholder' => 'Por padrão a classe é .form-group',
            'name' => "Fields[$counter][div_class]",
            'input_id' => "div_class-$counter",
            'Value' => $data,
        ]
    );

    if ($selector == 'class')
    return input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Classes do input',
            'Placeholder' => 'Por padrão a classe é .form-group',
            'name' => "Fields[$counter][class]",
            'input_id' => "class-$counter",
            'Value' => $data,
        ]
    );

    if ($selector == 'div_attributes')
    return input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Atributos da DIV',
            'Placeholder' => 'data, ON events, etc.',
            'name' => "Fields[$counter][div_attributes]",
            'input_id' => "div_attributes-$counter",
            'Value' => $data,
        ]
    );

    if ($selector == 'Placeholder')
    return input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Placeholder',
            'Placeholder' => 'Isso o que você está vendo é um placeholder!',
            'name' => "Fields[$counter][Placeholder]",
            'input_id' => "Placeholder-$counter",
            'Value' => $data,
        ]
    );

    if ($selector == 'attributes')
    return input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Atributos do Input',
            'Placeholder' => 'Atributos, classes, ON events, etc.',
            'name' => "Fields[$counter][attributes]",
            'input_id' => "attributes-$counter",
            'Value' => $data,
        ]
    );

    if ($selector == 'name')
    return input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Name do Input',
            'attributes' => 'update-title:();',
            'Placeholder' => 'O Name de um input sobre idade seria: idade ou age',
            'name' => "Fields[$counter][name]",
            'input_id' => "name-$counter",
            'Value' => $data,
            'Required' => true,
        ]
    ) . input(
        'hidden',
        $type_form,
        [
            'name' => "Fields[$counter][old_name]",
            'Value' => $data,
            'Required' => true,
        ]
    );

    if ($selector == 'input_id')
    return input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'ID do Input',
            'Placeholder' => 'O ID de um input sobre idade seria: idade ou age',
            'name' => "Fields[$counter][input_id]",
            'input_id' => "input_id-$counter",
            'Value' => $data,
        ]
    );

    if ($selector == 'Value')
    return input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Value do Input',
            'Placeholder' => 'O value aparece aqui :)',
            'name' => "Fields[$counter][Value]",
            'input_id' => "Value-$counter",
            'Value' => $data,
        ]
    );

    if ($selector == 'Required')
    return input('selection_type', $type_form,
        [
            'type' => 'switch',
            'size' => 'col-md-6 col-lg-4',
            'name' => "Fields[$counter][Required]",
            'input_id' => "Required-$counter",
            'Options' => "1|| 1|| Campo obrigatório;",
            'Value' => $data,
        ]
    );

    if ($selector == 'disabled')
    return input('selection_type', $type_form,
        [
            'type' => 'switch',
            'size' => 'col-md-6 col-lg-4',
            'name' => "Fields[$counter][disabled]",
            'input_id' => "disabled-$counter",
            'Options' => "7|| 1|| Campo desativado;",
            'Value' => $data,
        ]
    );

    if ($selector == 'subscribers_only')
    return function_exists('signatures_version') ? input('selection_type', $type_form,
        [
            'type' => 'switch',
            'size' => 'col-md-6 col-lg-4',
            'name' => "Fields[$counter][subscribers_only]",
            'input_id' => "subscribers_only-$counter",
            'Options' => "2|| 1|| Apenas para assinantes;",
            'Value' => $data,
        ]
    ) : null;

    if ($selector == 'view_in_list')
    return input('selection_type', $type_form,
        [
            'type' => 'switch',
            'size' => 'col-md-6 col-lg-4',
            'name' => "Fields[$counter][view_in_list]",
            'input_id' => "view_in_list-$counter",
            'Options' => "3|| 1|| Exibir coluna na tabela;",
            'Value' => $data,
        ]
    );

    if ($selector == 'unique_key')
    return input('selection_type', $type_form,
        [
            'type' => 'switch',
            'size' => 'col-md-6 col-lg-4',
            'name' => "Fields[$counter][unique_key]",
            'input_id' => "unique_key-$counter",
            'Options' => "5|| 1|| Valor único;",
            'Value' => $data,
        ]
    );

    if ($selector == 'bd_action')
    {
        return input('selection_type', $type_form,
            [
                'type' => 'checkbox',
                'size' => 'col-md-6 col-lg-4',
                'name' => "Fields[$counter][bd_action]",
                'input_id' => "bd_action-$counter",
                'Options' => "6|| 1|| Inserir coluna na tabela;",
            ]
        );
    }

    if ($selector == 'status_id')
    return input('status_selector', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'function_proccess' => 'general_status',
            'name' => "Fields[$counter][status_id]",
            'input_id' => "status_id-$counter",
            'Value' => $data,
            'Required' => true
        ]
    );

    if ($selector == 'function_proccess')
    return input('basic', $type_form,
        [
            'size' => 'col-md-12 col-lg-6',
            'label' => 'Função de BACKEND',
            'Placeholder' => 'clean_text()',
            'name' => "Fields[$counter][function_proccess]",
            'input_id' => "function_proccess-$counter",
            'Value' => $data,
        ]
    );

    if ($selector == 'function_view')
    return input('basic', $type_form,
        [
            'size' => 'col-md-12 col-lg-6',
            'label' => 'Função de FRONTEND',
            'Placeholder' => 'Ex: BRL()',
            'name' => "Fields[$counter][function_view]",
            'input_id' => "function_view-$counter",
            'Value' => $data,
        ]
    );

    if ($selector == 'group_options')
    return input('selection_type', $type_form,
        [
            'type' => 'switch',
            'variation' => 'inline',
            'size' => 'col-12',
            'label' => 'Opções de campo',
            'name' => "Fields[$counter][group_options]",
            'Options' => [
                [
                    'value' => '1',
                    'name' => "Fields[$counter][Required]",
                    'display' => 'Obrigatório',
                    'checked' => $data['Required'] ?? '',
                ],
                [
                    'value' => '1',
                    'name' => "Fields[$counter][disabled]",
                    'display' => 'Desativado',
                    'checked' => $data['disabled'] ?? '',
                ],
                [
                    'value' => '1',
                    'name' => "Fields[$counter][readonly]",
                    'display' => 'Apenas visualização',
                    'checked' => $data['readonly'] ?? '',
                ],
                [
                    'value' => '1',
                    'name' => "Fields[$counter][view_in_list]",
                    'display' => 'Exibir coluna na tabela',
                    'checked' => $data['view_in_list'] ?? '',
                ],
                [
                    'value' => '1',
                    'name' => "Fields[$counter][run_before_action]",
                    'display' => 'Executar antes da ação principal',
                    'checked' => $data['run_before_action'] ?? '',
                ],
                [
                    'value' => '1',
                    'name' => "Fields[$counter][run_after_action]",
                    'display' => 'Executar após ação principal',
                    'checked' => $data['run_after_action'] ?? '',
                ],
            ],
        ]
    );

    if ($selector == 'attachment')
    {
        return input('basic', $type_form,
            [
                'size' => 'col-md-12 col-lg-6',
                'label' => 'Prepend',
                'attributes' => 'data-prepend:();',
                'Placeholder' => 'Ex: @ em usuário',
                'name' => "Fields[$counter][attachment][prepend]",
                'input_id' => "attachment-$counter",
                'Value' => $data['prepend'] ?? '',
                'attachment' => ['prepend' => 'Isso', 'append' => '']
            ]
        ).
        input('basic', $type_form,
            [
                'size' => 'col-md-12 col-lg-6',
                'label' => 'Append',
                'attributes' => 'data-append:();',
                'Placeholder' => 'Ex: .com em URLs',
                'name' => "Fields[$counter][attachment][append]",
                'input_id' => "attachment-$counter",
                'Value' => $data['append'] ?? '',
                'attachment' => ['prepend' => '', 'append' => 'Isso']
            ]
        );
    }
}


function load_crud_piece_actions($id = 0)
{
    $res = '';
    if ($id)
    {
        $pieces = get_results("SELECT id, piece_name, type_crud FROM tb_cruds WHERE crud_id = {$id}");
        if (count($pieces) > 0)
        {
            $slide = 0;
            foreach ($pieces as $piece)
            {
                $res.= "
                <div class='piece'>
                    <div class='btn-group remove'>
                    <button type='button' class='control remove-confirm dropdown-toggle' data-bs-toggle='dropdown'>". icon('fas fa-trash') ."</button>
                    <ul class='dropdown-menu'>
                        <p>Deseja excluir este fragmento?</p>
                        <li><button class='dropdown-item' load-crud-piece='{$piece['id']}' data-mode='delete' type='button'>Excluir</button></li>
                        <li><button class='dropdown-item' type='button'>Cancelar</button></li>
                    </ul>
                    </div>
                    <button type='button' class='control copy-db' load-crud-piece='{$piece['id']}' data-mode='duplicate'>". icon('fas fa-copy') ."</button>
                    <button type='button' class='crud-piece' load-crud-piece='{$piece['id']}' data-mode='see'>
                    <div>
                        <span class='name'>#{$piece['id']} - {$piece['piece_name']}</span>
                        <span class='type'>{$piece['type_crud']}</span>
                    </div>
                    </button>
                </div>";

                $slide++;
            }
        }
        else {
            $res.= 'Esse CRUD não tem fragmentos salvos.';
        }
    }

    return $res;
}


function get_fields_by_table(array $tables = [])
{
    if (empty($tables)) return [];

    $exceptions = [
        'hr',
        'break_line',
        'field_repeater',
        'text',
        'submit_button',
    ];

    $escaped_tables = array_map(fn($t) => "'".db_escape($t)."'", $tables);
    $escaped_exceptions = array_map(fn($e) => "'".db_escape($e)."'", $exceptions);
    $tables_sql = implode(', ', $escaped_tables);
    $exceptions_sql = implode(', ', $escaped_exceptions);

    $sql = "
        SELECT
            f.id,
            f.type_field,
            f.name,
            f.settings,
            c.crud_id AS group_id,
            c.piece_name,
            c.table_crud,
            c.type_crud
        FROM tb_cruds_fields AS f
        LEFT JOIN tb_cruds AS c ON c.id = f.crud_id
        WHERE c.table_crud IN ($tables_sql)
        AND f.type_field NOT IN ($exceptions_sql)
        ORDER BY f.name, c.piece_name, c.table_crud ASC
    ";

    $fields_from_table = get_results($sql);

    $fields = [];
    foreach ($fields_from_table as $field)
    {
        $field = (array) $field['settings'] + $field;
        unset($field['settings']);
        $fields[] = $field;
    }

    return $fields;
}


function get_crud_piece_to_edit($id = 0)
{
    global
        $pages_to_choose,
        $routes_to_choose,
        $type_items;

    $crud = get_result("SELECT * FROM tb_cruds WHERE id = '{$id}'");

    $settings = "";

    /**
     *
     * Load the CRUD fields.
     *
     */
    $boxes = '';
    $counter = 1;
    $fields = get_results("SELECT * FROM tb_cruds_fields WHERE crud_id = '{$id}' AND type_field IS NOT null ORDER BY order_reg ASC");
    foreach ($fields as $field)
    {
        $field = (array) $field['settings'] + $field;
        unset($field['settings']);

        $questions = inputs_select_type($field['type_field'], 'update', $counter, $field);

        $boxes.= field_content_card(
            'update',
            [
                'delete' => true,
                'move' => true,
                'label' => $field['title'] ?? $field['name'],
                'type' => $field['type_field'],
                'counter' => $counter,
                'questions' => $questions,
                'depth' => $field['depth'] ?? 0,
                'id' => $field['id'],
            ]
        );

        $counter++;
    }


    /**
     *
     * Load the models.
     *
     */
    $models_options = "<div class='list-group'>";
    $models = get_results("SELECT * FROM tb_cruds_fields WHERE is_model = 1 AND type_field IS NOT null ORDER BY order_reg ASC");
    if (count($models) > 0)
    {
        foreach ($models as $field)
        {
            $field = (array) $field['settings'] + $field;
            unset($field['settings']);

            $models_options.= "
            <button type='button' data-insert-model-id='{$field['id']}'>
                <span class='name'>{$field['name']}</span>
                <span class='type'>{$field['type_field']}</span>
            </button>";
        }
    }
    else {
        $models_options.= 'Você não tem modelos salvos.';
    }
    $models_options.= "</div>";


    $options = '';
    foreach ($type_items['crud'] as $optgroup => $option)
    {
        $options.= "
        <section class='content-options'>
        <h6>". ($optgroup ?? '') ."</h6>
        <div class='d-grid gap-2'>";
        foreach ($option as $value => $info)
        {
            $options.= "
            <button type='button' data-insert-model='$value' value='$value'>
                ". icon($info['icon'] ?? '' ) ."
                <p>{$info['name']}</p>
            </button>";
        }
        $options.= '
        </div>
        </section>';
    }


    /**
     *
     * Show the setp form settings.
     *
     */
    $display_steps_form= !in_array('steps_form', $crud['form_settings'])
        ? 'style="display: none;"'
        : '';
    $display_steps_form.= 'steps-form-settings';

    $steps_form = "<div class='form-row p-3'>";
    $steps_form.= input('selection_type', 'update',
        [
            'type' => 'switch',
            'size' => 'col-12',
            'name' => "form_settings[container]",
            'Options' => "1|| 1|| Container;",
            'Value' => $crud['form_settings']['container'] ?? null,
        ]
    ) . input('selection_type', 'update',
        [
            'type' => 'switch',
            'size' => 'col-12',
            'variation' => 'inline',
            'label' => 'Modo de salvar',
            'name' => "form_settings[steps_form][]",
            'Options' => [
                [ 'name' => 'form_settings[steps_form][save_between_steps]', 'value' => '1', 'display' => 'Salvar entre etapas' ],
                [ 'name' => 'form_settings[steps_form][one_step_at_a_time]', 'value' => '1', 'display' => 'Um passo de cada vez' ],
                [ 'name' => 'form_settings[steps_form][show_progess]', 'value' => '1', 'display' => 'Mostrar progresso' ],
                [ 'name' => 'form_settings[steps_form][show_steps]', 'value' => '1', 'display' => 'Mostrar passos' ],
            ],
            'Value' => $crud['form_settings']['steps_form'] ?? [],
            // 'Required' => true
        ]
    ) . input('selection_type', 'update',
        [
            'size' => 'col-12',
            'variation' => 'inline',
            'label' => 'Estilo progesso',
            'name' => "form_settings[steps_form][progess_style]",
            'Options' => [
                [ 'value' => 'progress_bar' ],
                [ 'value' => 'progress_bar_striped' ],
                [ 'value' => 'progress_bar_striped_animated' ],
                [ 'value' => 'progress_steps_detailed' ],
                [ 'value' => 'progress_steps_summary' ],
                [ 'value' => 'progress_steps_detailed_vertical' ],
                [ 'value' => 'progress_steps_summary_vertical' ],
            ],
            'Value' => $crud['form_settings']['steps_form']['progess_style'] ?? 'progress_bar'
        ]
    ) . input('selection_type', 'update',
        [
            'size' => 'col-12',
            'label' => 'Cor do progresso',
            'name' => "form_settings[steps_form][progress_color]",
            'Options' => theme_background_colors(true),
            'Value' => $crud['form_settings']['steps_form']['progress_color'] ?? '',
            // 'Required' => true
        ]
    ) . input('basic', 'update',
        [
            'size' => 'col-12',
            'label' => 'Nome botão de salvar',
            'name' => "form_settings[steps_form][button_name_send]",
            'Value' => $crud['form_settings']['steps_form']['button_name_send'] ?? '',
        ]
    );
    $steps_form.= "</div>";


    /**
     *
     * Fields for CRUD type view.
     *
     */
    $related_display = ($crud['type_crud'] == 'list')
        ? 'style="display: none;"'
        : '';

    $settings.= "<div class='row' data-content-related {$related_display}>";
    $settings.= input('selection_type', 'update',
        [
            'type' => 'radio',
            'size' => 'col-12',
            'variation' => 'btn-group',
            'label' => 'Relacionado a',
            'name' => 'related_to',
            'Options' => [
                [ 'value' => 'table', 'display' => 'Tabela' ],
                [ 'value' => 'logged_in_user', 'display' => 'Usuário logado' ],
                [ 'value' => 'system_info', 'display' => 'Informações do sistema' ],
            ],
            'Value' => $crud['related_to'] ?? 'table',
            'Required' => true,
        ]
    );
    // var_dump($crud['related_to'] );
    $settings.= "</div>";


    /**
     *
     * Fields for CRUD type insert OR update.
     *
     */
    $form_display = !(($crud['type_crud'] == 'insert') OR ($crud['type_crud'] == 'update'))
        ? 'style="display: none;"'
        : '';

    $settings.= "<div class='row' data-content-form {$form_display}>";
    $settings.= input('selection_type', 'update',
        [
            'type' => 'checkbox',
            'size' => 'col-12',
            'label' => 'Opções de formulário',
            'name' => 'form_settings[]',
            'input_id' => 'form_settings',
            'Options' => [
                [
                    'name' => 'form_settings[without_reload]',
                    'value' => 1,
                    'display' => 'Enviar form. sem reload',
                    'checked' => isset($crud['form_settings']['without_reload']),
                ],
            ],
            // 'Value' => $crud['form_settings'] ?? ['without_reload'],
        ]
    );
    $settings.= input('selection_type', 'update',
        [
            'type' => 'radio',
            'size' => 'col-12',
            'variation' => 'btn-group',
            'label' => 'Exibição',
            'name' => 'form_settings[view_mode]',
            'input_id' => 'form_settings',
            'Options' => [
                [ 'value' => 'default', 'display' => 'Padrão' ],
                [ 'value' => 'steps_form', 'display' => 'Passos' ],
                [ 'value' => 'tabs_form', 'display' => 'Abas' ],
                [ 'value' => 'only_fields', 'display' => 'Apenas campos' ],
                [ 'value' => 'only_form', 'display' => 'Apenas form.' ],
            ],
            'Value' => $crud['form_settings']['view_mode'] ?? ['default'],
            // 'Required' => true,
        ]
    );
    $settings.= dropdown_content([
        'attr' => $display_steps_form,
        'size' => 'col-md-12',
        'title' => 'Opções dos passos',
        'content' => $steps_form,
    ]);
    $settings.= hr();
    $settings.= input('selection_type', 'update',
        [
            'type' => 'radio',
            'size' => 'col-6',
            'label' => 'Método',
            'name' => 'form_method',
            'input_id' => 'form_method',
            'variation' => 'inline',
            'Options' => [
                [ 'value' => 'POST' ],
                [ 'value' => 'GET' ],
            ],
            'Value' => $crud['form_method'] ?? 'POST',
        ]
    );

    $crud['form_action'] = [
        'type'   => $crud['form_action']['type'] ?? 'api',
        'action' => $crud['form_action']['action'] ?? 'form-processor',
    ];

    $settings.= input('selection_type', 'update',
        [
            'type' => 'radio',
            'size' => 'col-6',
            'label' => 'Tipo de ação',
            'name' => 'form_action[type]',
            'input_id' => 'form_action[type]',
            'variation' => 'inline',
            'Options' => [
                [ 'value' => 'api', 'display' => 'API', 'attributes' => 'form-action-trigger:();' ],
                [ 'value' => 'page', 'display' => 'Página', 'attributes' => 'form-action-trigger:();' ],
                [ 'value' => 'external', 'display' => 'Externa', 'attributes' => 'form-action-trigger:();' ],
            ],
            'Value' => $crud['form_action']['type'],
        ]
    );

    if ($crud['form_action']['type'] == 'external')
    {
        $settings.= input('basic', 'update',
            [
                'type' => 'url',
                'div_attributes' => 'id:(form-action-div);',
                'class' => 'hide-content-action-form',
                'size' => 'col-12',
                'label' => 'Ação do formulário',
                'Placeholder' => 'Coloque uma URL ou uma ação',
                'name' => 'form_action[action]',
                'input_id' => 'form_action',
                'Value' => $crud['form_action']['action'],
                'attachment' => [ 'prepend' => 'https' ]
            ]
        );
    }

    elseif ($crud['form_action']['type'] == 'page')
    {
        $settings.= input('selection_type', 'update',
            [
                'div_attributes' => 'id:(form-action-div);',
                'class' => 'hide-content-action-form',
                'type' => 'search',
                'size' => 'col-12',
                'label' => 'Ação do formulário',
                'name' => 'form_action[action]',
                'input_id' => 'form_action',
                'Options' => $pages_to_choose,
                'Value' => $crud['form_action']['action'],
            ]
        );
    }

    elseif ($crud['form_action']['type'] == 'api')
    {
        $settings.= input('selection_type', 'update',
            [
                'div_attributes' => 'id:(form-action-div);',
                'class' => 'hide-content-action-form',
                'type' => 'search',
                'size' => 'col-12',
                'label' => 'Ação do formulário',
                'name' => 'form_action[action]',
                'input_id' => 'form_action',
                'Options' => $routes_to_choose,
                'Value' => $crud['form_action']['action'] ?? 'form-processor',
            ]
        );
    }
    $settings.= input('selection_type', 'update',
        [
           'type' => 'search',
           'size' => 'col-12',
           'label' => 'Página de resultados',
           'name' => 'result_page',
           'input_id' => 'result_page',
           'Options' => $pages_to_choose,
           'Value' => $crud['result_page'] ?? '',
        ]
    );
    $settings.= input('basic', 'update',
        [
            'type' => 'number',
            'size' => 'col-12',
            'label' => 'Delay (em MS)',
            'name' => 'form_settings[delay]',
            'Value' => $crud['form_settings']['delay'] ?? '',
        ]
    );
    $settings.= "</div>";


    /**
     *
     * List settings
     *
     */
    $list_display = ($crud['type_crud'] != 'list')
        ? 'style="display: none;"'
        : '';
    $settings.= "<div class='row' data-content-list {$list_display}>";

    $list_settings = $crud['list_settings'] ?? [];
    unset($list_settings['limit_results']);

    $settings.= input('selection_type', 'update',
        [
            'type' => 'checkbox',
            'size' => 'col-12',
            'label' => 'Opções da lista:',
            'name' => 'list_settings[]',
            'input_id' => 'list_settings',
            'Options' => [
                [ 'value' => 'show_id', 'display' => 'Mostrar ID na tabela' ],
                [ 'value' => 'show_list_pg', 'display' => 'Mostrar página de listagem no painel' ],
                [ 'value' => 'data_table', 'display' => 'Tabela avançada' ],
                [ 'value' => 'data_table_async', 'display' => 'Tabela assíncrona (precisa de Tabela avançada)' ],
            ],
            'Value' => $list_settings ?? [],
        ]
    );
    $settings.= input('basic', 'update',
        [
            'size' => 'col-12',
            'label' => 'Limite de registros',
            'type' => 'number',
            'Placeholder' => 'Ex: 5',
            'name' => 'list_settings[limit_results]',
            'Value' => $crud['list_settings']['limit_results'] ?? '',
        ]
    );
    $settings.= "</div>";



    /**
     *
     * Get fields from the CRUD table.
     *
     */
    $from_table = "
    <div id='fields-from-table-crud'>

        <ul id='fields-from-table-crud-list'";
        $slide = 0;
        $fields_from_table = get_fields_by_table([$crud['table_crud']]);
        if (count($fields_from_table) > 0)
        {
            foreach ($fields_from_table as $field)
            {
                $title = $field['title'] ?? $field['name'];

                $from_table.= "
                <li>
                <button type='button' data-insert-model-id='{$field['id']}'>
                    <div>
                      <strong>{$title}</strong>
                      <span>{$field['type_field']}</span>
                      <span>{$field['piece_name']}</span>
                    </div>
                    <span class='type'>{$field['type_crud']}</span>
                </button>
                </li>";

                $slide++;
            }
        }
        else {
            $from_table.= 'Essa tabela não possui campos.';
        }
        $from_table .= "
        </ul>

        <div class='loading-template' style='display: none;'>
        <div class='spinner-border' role='status'>
            <span class='visually-hidden'>Loading...</span>
        </div>
        </div>

    </div>";


    /**
     *
     * Build & brings the fields options.
     *
     */
    $navtabs = block('navtabs', [
        'id' => 'fields-tab',
        'variation' => 'navtabs_pills',
        'contents' => [
            [
                'id' => 'models',
                'title' => 'Comuns',
                'body' => $options,
                'active' => true,
            ],
            [
                'id' => 'itens',
                'title' => 'Salvos',
                'body' => $models_options,
            ],
            [
                'id' => 'by-table',
                'title' => 'Tabela',
                'body' => $from_table,
            ],
        ],
    ]);

    /**
     *
     * Build the modal body.
     *
     */
    $modal_body =
    input('hidden', 'update', [ 'name' => 'id', 'input_id' => 'id', 'Value' => $id ]) ."
    <div class='row'>

    <div class='col-12'>
        <div class='row'>".
        input('basic', 'update',
            [
                'size' => 'col-md-12 col-lg-3',
                'label' => 'Nome',
                'name' => 'piece_name',
                'Value' => $crud['piece_name'] ?? '',
                'Required' => true,
            ]
        ) . input('basic', 'update',
            [
                'size' => 'col-md-12 col-lg-3',
                'label' => 'Slug',
                'name' => 'slug',
                'Value' => $crud['slug'] ?? '',
                'Required' => true,
            ]
        ) . input('selection_type', 'update',
            [
                'type' => 'select',
                'size' => 'col-12 col-lg-3',
                'label' => 'Modo',
                'name' => 'type_crud',
                'input_id' => 'type_crud',
                'Options' => [
                    ['value' => 'insert', 'display' => 'Cadastrar'],
                    ['value' => 'update', 'display' => 'Editar'],
                    ['value' => 'list', 'display' => 'Listar'],
                    ['value' => 'view', 'display' => 'Visualizar'],
                ],
                'Value' => $crud['type_crud'] ?? '',
                'Required' => true
            ]
        ) . input('status_selector', 'update',
            [
                'size' => 'col-12 col-lg-3',
                'function_proccess' => 'general_status',
                'name' => 'status_id',
                'input_id' => 'status_id',
                'Value' => $crud['status_id'] ?? 4,
                'Required' => true
            ]
        ) . input('selection_type', 'update',
            [
                'type' => 'switch',
                'size' => 'col-12 col-lg-3',
                'name' => 'login_required',
                'Options' => [[ 'value' => 1, 'display' => 'Requer login' ]],
                'Value' => $crud['login_required'] ?? null,
            ]
        )."
        </div>
        <hr>
   </div>

    <div class='col-md-6 col-lg-3 col-xl-2 options'>
        {$navtabs}
    </div>

    <div class='col-md-6 col-lg-3 col-xl-2 options'>
        {$settings}

        <hr>
        ". input('selection_type', 'update',
            [
                'type' => 'switch',
                'size' => 'col-12',
                'name' => 'crud_panel[show_panel]',
                'Options' => [
                    [ 'value' => '1', 'display' => 'Mostrar painel' ],
                ],
                'Value' => $crud['crud_panel']['show_panel'] ?? '',
            ]
        ) . input('selection_type', 'update',
            [
                'type' => 'checkbox',
                'size' => 'col-12',
                'label' => 'Painel do CRUD:',
                'name' => 'crud_panel[]',
                'input_id' => 'crud_panel',
                'Options' => [
                    [ 'value' => 'show_name', 'display' => 'Exibir nome do elemento' ],
                    [ 'value' => 'minimize_actions', 'display' => 'Minimizar ações (botões)' ],
                ],
                'Value' => $crud['crud_panel'] ?? '',
            ]
        ) . input('basic', 'update',
            [
                'size' => 'col-12',
                'label' => 'Atributos da section',
                'name' => 'div_attributes',
                'Value' => $crud['div_attributes'] ?? '',
            ]
        ) . input('basic', 'update',
            [
                'size' => 'col-12',
                'label' => 'Atributos do fragmento',
                'Placeholder' => 'name, id, enctype, etc.',
                'name' => 'attributes',
                'Value' => $crud['attributes'] ?? '',
            ]
        ). "
    </div>

    <div class='col-md-12 col-lg-6 col-xl-8'>
        <div class='draggable-column' id='field-container-card' data-counter-fields='{$counter}'>$boxes</div>
    </div>

    ". input('hidden', 'update', [ 'name' => 'delete_items' ]) ."

    </div>";

    $modal_footer = "
    <button type='button' class='btn btn-link' data-history='undo' title='Desfazer (Ctrl+Z)' aria-label='Desfazer'>
        ". icon('fas fa-rotate-left') ."
    </button>
    <button type='button' class='btn btn-link' data-history='redo' title='Refazer (Ctrl+Y ou Ctrl+Shift+Z)' aria-label='Refazer'>
        ". icon('fas fa-rotate-right') ."
    </button>".
    input('submit_button', 'update', [ 'class' => 'btn btn-success btn-lg', 'Value' => 'Editar' ]);

    return block('modal', [
        'form' => [
            'active' => true,
            'attributes' => "method:(POST); data-send-ctrl-s:(); data-send-without-reload:(); action:(". rest_api_route_url("manage-crud-system?mode=update") ."); enctype:(multipart/form-data);"
        ],
        'id' => 'edit-fields',
        'size' => 'fullscreen',
        'title' => 'Gerenciar fragmento',
        'close_button' => true,
        'body' => $modal_body,
        'footer' => $modal_footer
    ]);
}


/**
 * Generate HTML form inputs for configuring 'divider'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'divider'.
 */
function inputs_for_divider(string $type_form, $counter, array $data = [])
{
    $type_field = 'divider';

    $res = '<div class="form-row">';
    $res.= common_inputs_for_crud($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_crud($type_form, 'depth', $data['depth'] ?? 0, $counter);
    $res.= common_inputs_for_crud($type_form, 'type_field', $type_field, $counter);
    $res.= input('basic', $type_form,
        [
            'size' => 'col-md-6',
            'label' => 'Título',
            'attributes' => 'update-title:();',
            'name' => "Fields[$counter][title]",
            'input_id' => "title-$counter",
            'Value' => $data['title'] ?? '',
        ]
    );
    $res.= input('basic', $type_form,
        [
            'size' => 'col-md-6',
            'label' => 'Icon',
            'name' => "Fields[$counter][icon]",
            'input_id' => "icon-$counter",
            'Value' => $data['icon'] ?? '',
        ]
    );
    $res.= input('textarea', $type_form,
        [
            'size' => 'col-12',
            'label' => 'Descrição',
            'attributes' => 'rows:(4);',
            'name' => "Fields[$counter][description]",
            'input_id' => "description-$counter",
            'Value' => $data['description'] ?? '',
            'text_editor' => 1,
        ]
    );
    $res.= hr();

    $res.= common_inputs_for_crud($type_form, 'status_id', $data['status_id'] ?? 1, $counter);
    $res.= common_inputs_for_crud($type_form, 'div_attributes', $data['div_attributes'] ?? '', $counter);
    $res.= common_inputs_for_crud($type_form, 'div_class', $data['div_class'] ?? '', $counter);

    $res.= "</div>";

    return $res;
}


/**
 * Generate HTML form inputs for configuring 'hr'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'hr'.
 */
function inputs_for_hr(string $type_form, $counter, array $data = [])
{
    $type_field = 'hr';

    $res = '<div class="form-row">';
    $res.= common_inputs_for_crud($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_crud($type_form, 'depth', $data['depth'] ?? 0, $counter);
    $res.= common_inputs_for_crud($type_form, 'type_field', $type_field, $counter);
    $res.= common_inputs_for_crud($type_form, 'subscribers_only', $data['subscribers_only'] ?? '', $counter);
    $res.= "</div>";

    return $res;
}


/**
 * Generate HTML form inputs for configuring 'break_line'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'break_line'.
 */
function inputs_for_break_line(string $type_form, $counter, array $data = [])
{
    $type_field = 'break_line';

    $res = '<div class="form-row">';
    $res.= common_inputs_for_crud($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_crud($type_form, 'depth', $data['depth'] ?? 0, $counter);
    $res.= common_inputs_for_crud($type_form, 'type_field', $type_field, $counter);
    $res.= common_inputs_for_crud($type_form, 'subscribers_only', $data['subscribers_only'] ?? '', $counter);
    $res.= "</div>";

    return $res;
}


/**
 * Generate HTML form inputs for configuring 'shortcode'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'shortcode'.
 */
function inputs_for_shortcode(string $type_form, $counter, array $data = [])
{
    $type_field = 'shortcode';

    $res = '<div class="form-row">';
    $res.= common_inputs_for_crud($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_crud($type_form, 'depth', $data['depth'] ?? 0, $counter);
    $res.= common_inputs_for_crud($type_form, 'type_field', $type_field, $counter);
    $res.= input('textarea', $type_form,
        [
            'size' => 'col-12',
            'label' => 'Conteúdo',
            'attributes' => 'rows:(4);',
            'name' => "Fields[$counter][content]",
            'input_id' => "content-$counter",
            'Value' => $data['content'] ?? '',
            'text_editor' => 1,
        ]
    );
    $res.= common_inputs_for_crud($type_form, 'subscribers_only', $data['subscribers_only'] ?? '', $counter);
    $res.= sizes_selector($type_form, 'Fields', $data['size'] ?? '', $counter);

    $advanced_options = "<div class='form-row p-3'>";
    $advanced_options.= common_inputs_for_crud($type_form, 'div_class', $data['div_class'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'div_attributes', $data['div_attributes'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'status_id', $data['status_id'] ?? 1, $counter);
    $advanced_options.= input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Atributos da tag HTML',
            'Placeholder' => 'classes, styles, etc.',
            'name' => "Fields[$counter][attributes]",
            'input_id' => "attributes-$counter",
            'Value' => $data['attributes'] ?? "",
        ]
    );
    $advanced_options.= "</div>";

    $res.= dropdown_content( ['size' => 'col-md-6', 'title' => 'Opções avançadas', 'content' => $advanced_options] );
    $res.= "</div>";

    return $res;
}


/**
 * Generate a CRUD management form for a content management system.
 *
 * @param string $type_form - The type of form, either 'insert' for creating a new record or 'update' for editing an existing one.
 * @param int $counter - An optional counter used for form fields (default is 1).
 *
 * @return int - The updated counter value.
 */
function manage_crud_form(string $type_form = 'insert', int $counter = 1)
{
    global
        $crud_action_triggers,
        $type_items,
        $routes_to_choose,
        $page,
        $pages_to_choose;

    $id = id_by_get();

    $crud               = get_result("SELECT * FROM tb_cruds WHERE id = '{$id}'");
    $pieces_to_choose   = get_results("SELECT id AS value, piece_name AS display FROM tb_cruds WHERE crud_id = '{$id}' AND type_crud != 'list'");

    $type_form = $id
        ? 'update'
        : 'insert';

    if (count($crud) == 0) {
        $type_form = 'insert';
    }

    if ($type_form == 'update')
    {
        $panel['hooks_out'][] = [
            'title' => 'Duplicar',
            'url'  => rest_api_route_url("duplicate-record?id={$id}&table=tb_cruds&foreign_key=crud_id"),
            'attr'  => 'data-controller: (duplicate);',
            'color' => 'outline-info',
            'pre_icon' => 'fas fa-copy',
        ];

        $panel['hooks_out'][] = [
            'title' => 'Apagar',
            'url'  => rest_api_route_url("delete-record?id={$id}&table=tb_cruds&foreign_key=crud_id"),
            'attr'  => 'data-controller: (delete);',
            'color' => 'outline-danger',
            'pre_icon' => 'fas fa-trash',
        ];
    }
    ?>

    <section class="col-12">
    <div class="card box-fields">
        <form method="GET" action="" class="card-body row content-center">
            <?= input(
            'selection_type',
                'update',
                [
                    'size' => 'col-md-8',
                    // 'attributes' => 'data-min:(2);data-max:(5);data-allow-create:();',
                    // 'variation' => 'multiple',
                    'type' => 'search',
                    'label' => 'Selecione o CRUD para editar',
                    'name' => 'id',
                    'Query' => "SELECT id as value, piece_name as display FROM tb_cruds WHERE type_crud = 'master'",
                    'Value' => $id,
                ]
            ).
            input(
            'submit_button',
            $type_form,
            [
                'size' => 'col',
                'class' => 'btn btn-outline-nd',
                'block' => true,
                'Value' => 'Selecionar'
            ])?>

            <div class="col-12">
                <a href="<?= get_url_page($page['id'], 'full') ?>">ou criar um novo CRUD.</a>
            </div>

        </form>
    </div>
    </section>

    <?= crud_panel( $panel ?? [] ) ?>

    <form method="POST" data-send-ctrl-s data-send-without-reload action="<?= rest_api_route_url("manage-crud-system?mode={$type_form}") ?>" enctype="multipart/form-data">
    <div class="row">

    <div class="col-md-7 col-lg-8">

        <?php if ($type_form == 'update'): ?>
        <div class="card box-fields piece-controls">
        <div class="card-header">
            <h6 class="mb-0">Fragmentos</h6>
        </div>
        <div class="card-body">
        <div>

            <div class="insert-crud-piece-form">
            <div class='input-group'>
                <input type="text" class="form-control" placeholder="Nome" data-insert-crud-piece='piece_name'>
                <select class='form-select' data-insert-crud-piece='type_crud'>
                    <option selected>Tipo</option>
                    <option value='insert'>insert</option>
                    <option value='update'>update</option>
                    <option value='list'>list</option>
                    <option value='view'>view</option>
                </select>
                <button type='button' load-crud-piece data-mode='insert' class='btn btn-outline-nd btn-sm'>+</button>
            </div>
            </div>

            <div class='list-group' id="crud-piece-list">
            <?= load_crud_piece_actions($id) ?>
            </div>

        </div>
        </div>
        </div>
        <?php endif; ?>


        <div class="card box-fields">
        <div class="card-header">
            <h6 class="mb-0">Banco de dados</h6>
        </div>
        <div class="card-body">
        <div class="form-row">
            <?=
            input('basic', $type_form,
                [
                    'size' => 'col-md-6',
                    'label' => 'Tabela de gravação',
                    'Placeholder' => 'tb_users',
                    'name' => 'table_crud',
                    'Value' => ($type_form=='update') ? $crud['table_crud'] : '',
                    'Required' => true,
                ]
            ).input('basic', $type_form,
                [
                    'size' => 'col-md-6',
                    'label' => 'Chave estrangeira',
                    'Placeholder' => 'Ex: user_id',
                    'name' => 'foreign_key',
                    'Value' => ($type_form=='update') ? $crud['foreign_key'] : '',
                ]
            );
            ?>
        </div>
        </div>
        </div>

        <div class="card box-fields">
        <div class="card-header">
            <h6 class="mb-0">Persmissões</h6>
        </div>
        <div class="card-body">
        <div class="form-row">

            <?php
            echo input('selection_type', $type_form,
                [
                    'type' => 'radio',
                    'size' => 'col-12',
                    'name' => 'permission_type',
                    'Options' => [
                        [ 'value' => 'only_these', 'display' => 'Liberado apenas para esses' ],
                        [ 'value' => 'except_these', 'display' => 'Exclua apenas esses' ],
                    ],
                    'Value' => ($type_form=='update') ? $crud['permission_type'] : 'only_these',
                ]
            );

            foreach ($crud_action_triggers as $name => $trigger)
            {
                echo input('selection_type', $type_form, [
                'type' => 'checkbox',
                'size' => 'col-md-6 col-lg-4',
                'label' => "Liberar \"{$trigger}\" para:",
                'name' => "allowed[{$name}][]",
                'variation' => 'inline',
                'Query' => 'SELECT id as value, name as display FROM tb_user_roles',
                'Value' => get_results("
                    SELECT role_id as value FROM tb_user_role_permissions
                    WHERE crud_id = '{$id}' AND allowed = 1 AND action_trigger = '{$name}'"),
                ]);
            }?>

        </div>
        </div>
        </div>

    </div>

    <div class="col-md-5 col-lg-4">
        <?php
        if ($type_form == 'update') {
         echo input('hidden', $type_form, [ 'name' => 'id', 'input_id' => 'id', 'Value' => $id ]);
        }

        $accordion = 1;
        ?>

        <div class="card box-fields piece-controls">
        <div class="card-header">
            <h6 class="mb-0">Informações básicas</h6>
        </div>
        <div class="card-body">
        <div class="form-row">
            <?php
            echo input('submit_button', $type_form,
            [
                'size' => 'col-12',
                'class' => 'btn btn-st',
                'block' => true,
                'Value' => ($type_form == 'update') ? 'Editar' : 'Cadastrar'
            ]) . input('basic', $type_form,
                [
                    'size' => 'col-md-12',
                    'label' => 'Nome',
                    'name' => 'piece_name',
                    'Value' => ($type_form=='update') ? $crud['piece_name'] : '',
                    'Required' => true,
                ]
            ) . input('basic', $type_form,
                [
                    'size' => 'col-md-12',
                    'label' => 'Slug',
                    'name' => 'slug',
                    'Value' => ($type_form=='update') ? $crud['slug'] : '',
                    'Required' => true,
                ]
            ) . input('status_selector', $type_form,
                [
                    'size' => 'col-12',
                    'function_proccess' => 'general_status',
                    'name' => 'status_id',
                    'input_id' => 'status_id',
                    'Value' => ($type_form=='update') ? $crud['status_id'] : 4,
                    'Required' => true
                ]
            );
            ?>
        </div>
        </div>
        </div>

        <div class="card box-fields piece-controls">
        <div class="card-header">
            <h6 class="mb-0">Informações básicas</h6>
        </div>
        <div class="card-body">
            <div class="form-row">
            <?php

            $group = '';

            if ($type_form == 'insert')
            {
                $group.= input('selection_type', $type_form,
                    [
                        'type' => 'radio',
                        'size' => 'col-12',
                        'label' => 'Atribuição do item de CRUD à página:',
                        'name' => "create-all-pages",
                        'input_id' => "create-all-pages",
                        'Required' => true,
                        'Options' => [
                            [
                                'value' => 'automatic',
                                'attributes' => 'data-page:();',
                                'display' => 'Automático',
                            ],
                            [
                                'value' => 'manual',
                                'attributes' => 'data-page:();',
                                'display' => 'Manual',
                            ],
                        ],
                    ]
                ) . input('selection_type', $type_form,
                    [
                        'type' => 'checkbox',
                        'size' => 'col-12" data-redirect-automatic style="display: none;"',
                        'label' => 'Podem acessar as páginas criadas:',
                        'name' => 'allowed[]',
                        'input_id' => 'allowed',
                        'Query' => 'SELECT id as value, name as display FROM tb_user_roles',
                        'Required' => true
                    ]
                );
            }

            $group.= "<div class='form-row' data-redirect-manual ". (($type_form=='insert') ? 'style:(display: none;);' : '') .">";

            // Normalize existing data when editing
            $pages_list = ($type_form === 'update' && !empty($crud['pages_list']))
                ? $crud['pages_list']
                : [];

            $actions = [
                'insert' => 'Cadastro',
                'update' => 'Edição',
                'view'   => 'Visualização',
            ];

            $mode_options = [
                ['value' => 'page',  'display' => 'Abrir como página'],
                ['value' => 'modal', 'display' => 'Abrir em modal'],
            ];

            // Start group
            $group  = '';

            // List page (unchanged)
            $group .= input('selection_type', $type_form, [
                'type'     => 'search',
                'size'     => 'col-12',
                'label'    => 'Página de listagem',
                'name'     => 'pages_list[list_pg]',
                'input_id' => 'pages_list[list_pg]',
                'Options'  => $pages_to_choose,
                'Value' => ($type_form=='update' && isset($pages_list['list_pg'])) ? $pages_list['list_pg'] : '',
            ]);

            $group.= hr();

            foreach ($actions as $act => $label)
            {
                $conf      = isset($pages_list[$act]) && is_array($pages_list[$act]) ? $pages_list[$act] : [];
                $mode_val  = isset($conf['mode'])  ? $conf['mode']  : 'page';
                $page_val  = isset($conf['page'])  ? $conf['page']  : '';
                $piece_val = isset($conf['piece']) ? $conf['piece'] : '';

                // 1) Modo (radio)
                $group .= input('selection_type', $type_form, [
                  'type'     => 'radio',
                  'size'     => 'col-12',
                  'label'    => "{$label}: como abrir?",
                  'name'     => "pages_list[{$act}][mode]",
                  'input_id' => "pages_list[{$act}][mode]",
                  'Options'  => $mode_options,
                  'Value'    => $mode_val,
                ]);

                // 2) if mode = page → choose page
                $group .= '<div class="col-12 mode-section" data-mode-section="'.$act.'" data-mode="page" style="'.($mode_val==='page'?'':'display:none').'">';
                $group .= input('selection_type', $type_form, [
                  'type'     => 'search',
                  'size'     => 'col-12',
                  'label'    => "{$label}: página destino",
                  'name'     => "pages_list[{$act}][page]",
                  'input_id' => "pages_list[{$act}][page]",
                  'Options'  => $pages_to_choose,
                  'Value'    => $page_val,
                ]);
                $group .= '</div>';

                // 3) if mode = modal → choose piece (CRUD)
                $group .= '<div class="col-12 mode-section" data-mode-section="'.$act.'" data-mode="modal" style="'.($mode_val==='modal'?'':'display:none').'">';
                $group .= input('selection_type', $type_form, [
                  'type'     => 'search',
                  'size'     => 'col-12',
                  'label'    => "{$label}: modal (peça CRUD)",
                  'name'     => "pages_list[{$act}][piece]",
                  'input_id' => "pages_list[{$act}][piece]",
                  'Options'  => $pieces_to_choose,
                  'Value'    => $piece_val,
                ]);
                $group .= '</div>';
            }


            $group.= '
            <fieldset class="col-12">
                <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#pages-custom">URLs customizadas</button>
            </fieldset>';

            $group.= '</div>';

            echo $group;

            echo block('modal', [
                'id' => 'pages-custom',
                'size' => 'lg',
                'title' => 'URLs customizadas',
                'close_button' => true,
                'body' => addable_content(
                    [
                        'title' => 'Adicione',
                        'type_form' => $type_form,
                        'counter' => $counter,
                        'function' => "inputs_for_custom_url",
                        'content' => ($type_form=='update') ? $crud['custom_urls'] : null,
                        'type' => 'custom_url'
                    ]
                ),
            ]);
            ?>
        </div>
        </div>
        </div>

    </div><!--/.col-lg-3 -->
    </div>

    </form>

    <?php

    return $counter;
}


/**
 * Manage crud data in a content management system.
 *
 * @param array $data - An array containing crud data.
 * @param string $mode - The mode of operation, either 'insert' or 'update'.
 * @param bool $debug - A flag for enabling debugging (default is false).
 *
 * @return array - An array containing status information.
 */
function manage_crud_system(array $data, string $mode, bool $debug = false)
{
    global $crud_action_triggers;

    $error        = false;
    $valid_data   = $data;


    // print_r($valid_data);

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
        $type_crud = $valid_data['type_crud'] ?? 'master';

        $args = [
           'piece_name'          => $valid_data['piece_name'] ?? null,
           'slug'              => $valid_data['slug'] ?? null,
           'type_crud'          => $valid_data['type_crud'] ?? $type_crud,
           'attributes'          => $valid_data['attributes'] ?? null,
           'div_attributes'    => $valid_data['div_attributes'] ?? null,
           'form_method'        => $valid_data['form_method'] ?? null,
           'related_to'        => $valid_data['related_to'] ?? 'table',
           'result_page'         => $valid_data['result_page'] ?? null,
           'form_action'        => $valid_data['form_action'] ?? [],
           'table_crud'         => $valid_data['table_crud'] ?? null,
           'crud_panel'        => $valid_data['crud_panel'] ?? [],
           'form_settings'     => $valid_data['form_settings'] ?? [],
           'list_settings'     => $valid_data['list_settings'] ?? [],
           'pages_list'        => $valid_data['pages_list'] ?? [],
           'custom_urls'        => $valid_data['custom_urls'] ?? [],
           // 'limit_results'     => $valid_data['limit_results'] ?? null,
           'foreign_key'        => $valid_data['foreign_key'] ?? null,
           'permission_type'   => $valid_data['permission_type'] ?? null,
           'status_id'         => $valid_data['status_id'] ?? null,
           'login_required'    => $valid_data['login_required'] ?? null,
        ];

        // print_r($args['form_settings']);
        // exit;

        if ($type_crud != 'master' && !empty($valid_data['crud_id'])) {
            $args['crud_id'] = $valid_data['crud_id'];
        }

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
        $mode('tb_cruds', $args, $debug);


        /*
        * Verify if inserted/updated correctaly.
        */
        if ($verifyer())
        {
           $crud_id = ($mode == 'insert')
               ? inserted_id()
               : $valid_data['id'];

           unset($_SESSION['FormData']);


           $Fields      = $valid_data['Fields'] ?? [];
           $order_reg   = 1;

           // print_r($Fields);

           /*
            * Run the routine of the fields.
            */
           $updated_order = [];
           foreach ($Fields as $field)
           {
               /*
                * Treatment of type_field.
                */
               $type_field = $field['type_field'];


               /*
                * This compile the field's datas.
                */
               $args_bd = [];
               $exceptions = [
                   'status_id',
                   'view_in_list',
                   'type_field',
                   'name',
                   'id',
               ];

               foreach ($field as $key => $value)
               {
                   if (in_array($key, $exceptions)) continue;

                   if ($key === 'size' && is_array($value)) {
                       $args_bd[$key] = implode(' ', $value);
                       continue;
                   }

                   $args_bd[$key] = (is_array($value) || is_object($value)) ? json_encode($value) : $value;
               }


                /*
                 * Group by settings.
                 */
                $args_bd  = filter_empty_values($args_bd);
                $args_bd['Query'] = isset($args_bd['Query']) ? $args_bd['Query'] : '';

                $res = $args_bd;
                unset(
                    $res['contents'],
                    $res['old_name'],
                    $args_bd
                );


                /*
                 * This add more infos to the field's datas.
                 */
                $args_bd['name']             = $field['name'] ?? '';
                $args_bd['settings']         = json_encode($res);
                $args_bd['view_in_list']     = $field['view_in_list'] ?? '';
                $args_bd['subscribers_only'] = $field['subscribers_only'] ?? '';
                $args_bd['status_id']        = $field['status_id'] ?? 1;
                $args_bd['crud_id']          = $crud_id;
                $args_bd['order_reg']        = $order_reg;
                $args_bd['type_field']        = $type_field;


                // Verify If insert a new field
                if ($mode == 'insert'
                    OR empty($field['id'])
                 ) {
                    // This var is used to 'bd_action' flow.
                    $input_mode_was = 'insert';

                    insert('tb_cruds_fields', $args_bd, false, $debug);
                    $current_id = inserted_id();
                }

                // OR edit an existent
                else
                {
                    // This var is used to 'bd_action' flow.
                    $input_mode_was = 'update';

                    $args_bd['data']  = $args_bd;
                    $args_bd['where'] = where_equal_id($field['id']);

                    update('tb_cruds_fields', $args_bd, false, $debug);
                    $current_id = $field['id'];
                }

                $updated_order[] = [
                    'id' => $current_id,
                    'depth' => $depth ?? 0
                ];


                /*
                 * Do the routine that add the column if is required.
                 */
                if (!empty($type_field) && isset($field['bd_action']) && $type_crud != 'master')
                {
                    $name           = $field['name'] ?? null;
                    $old_name       = $field['old_name'] ?? null;

                    $idCrud         = get_col("SELECT crud_id FROM tb_cruds WHERE id = {$valid_data['id']}");
                    $table_crud      = get_col("SELECT table_crud FROM tb_cruds WHERE id = {$idCrud} AND type_crud = 'master'");

                    $table = !empty($field['table'])
                       ? $field['table']
                       : ($table_crud ?? '');


                    if (in_array($type_field, ['hidden','basic','address_form'], true)) {
                        $type = 'VARCHAR(250)';
                    } elseif (in_array($type_field, ['textarea','upload'], true)) {
                        $type = 'longtext';
                    } elseif ($type_field === 'status_selector') {
                        $type = 'VARCHAR(11)';
                    } else {
                        $type = 'VARCHAR(50)';
                    }


                    $search_col = ($input_mode_was == 'update')
                        ? $old_name
                        : $name;

                    $field_exist = if_exist_col_bd($table, $search_col);

                    // Add the column in the wished Table
                    if (!$field_exist){
                        add_col_bd($table, $name, $type);
                    }

                    // Rename the column in the wished Table
                    elseif ($field_exist && ($old_name != $name))
                    {
                        rename_col_bd($table, $old_name, $name, $type);

                        // Change the name field in fields that has the same table.
                        $sql = "
                        UPDATE tb_cruds_fields AS f
                        SET f.name = '{$name}'
                        WHERE f.name = '{$old_name}'
                          AND f.crud_id IN (
                            SELECT c.id
                            FROM tb_cruds AS c
                            INNER JOIN tb_cruds AS m ON m.id = c.crud_id
                            WHERE m.type_crud = 'master'
                              AND m.table_crud = '{$table_crud}'
                          )";
                        query_it($sql);
                    }
                }

                $order_reg++;
            }


           /*
            * Create pages if it is selected.
            */
           if ($mode == 'insert' AND isset($valid_data['create-all-pages']))
           {
               $page = [
                   'title' => $valid_data['piece_name'],
                   'slug' => $valid_data['piece_name'],
                   'allowed' => $valid_data['allowed'],
                   'Modules' => [
                       '1' => [
                           'TypeModule' => 'crud-1',
                           'section_attributes' => " class='col-12 module pt-0'",
                           'Crud' => $crud_id,
                           'status_id' => 1,
                       ],
                   ],
               ];

               // Lights, camera & action.
               manage_page_system($page, 'insert', $debug);
           }

           $msg = alert_message("SC_TO_". strtoupper($mode), $msg_type);
        }

        else
        {
           $_SESSION['FormData'] = $data;
           $msg = alert_message("ER_TO_". strtoupper($mode), $msg_type);
        }


        /*
         * Insert the allowed levels acording with the types os permissions.
         */
        feature('permissions-management');
        foreach ($crud_action_triggers as $name => $trigger)
        {
            $args = [
                'allowed' => $valid_data['allowed'][$name] ?? [],
                'action_trigger' => $name,
                'type' => 'crud',
                'parent_id' => $crud_id,
            ];

            $res = update_permissions($args ?? [], false);
        }

        /*
         * Delete the wished modules if is updating.
         */
        if ($mode == 'update' AND !empty($valid_data['delete_items']))
        {
            $delete_items = explode( '-', $valid_data['delete_items'] );
            foreach ($delete_items as $delete_field) query_it("DELETE FROM tb_cruds_fields WHERE id = '{$delete_field}'", $debug);
        }

    }

    $res = [
        'code' => !$error ? 'success' : 'error',
        'detail' => [
            'type' => $msg_type,
            'msg' => $msg,
        ],
    ];

    $res['updated_items_order'] = $updated_order ?? [];

    if ($mode == 'insert') {
        // $res['redirect'] = get_url_page('crud-management', 'full'). "?id={$menu_id}";
    }

    return $res;
}
