<?php
if(!isset($seg)) exit;


/**
 * Generate HTML form inputs for configuring 'selection_type'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'selection_type'.
 */
function inputs_for_selection_type(string $type_form, $counter, array $data = [])
{
    $type_field = 'selection_type';

    if ($type_form == 'insert')
    {
        $data['Options'] = json_encode([['value' => '', 'display' => 'Selecione']]);
    }

    $res = '<div class="form-row">';
    $res.= common_inputs_for_crud($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_crud($type_form, 'depth', $data['depth'] ?? 0, $counter);
    $res.= common_inputs_for_crud($type_form, 'type_field', $type_field, $counter);
    $res.= common_inputs_for_crud($type_form, 'label', $data['label'] ?? '', $counter);
    $res.= common_inputs_for_crud($type_form, 'name', $data['name'] ?? '', $counter);
    $res.= input('selection_type', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Tipo de seleção',
            'name' => "Fields[$counter][type]",
            'input_id' => "type-$counter",
            'Options' => [
                [ 'value' => 'switch', 'display' => 'Switch' ],
                [ 'value' => 'checkbox', 'display' => 'Checkbox' ],
                [ 'value' => 'radio', 'display' => 'Radio' ],
                [ 'value' => 'select', 'display' => 'Select' ],
                [ 'value' => 'search', 'display' => 'Pesquisa' ],
            ],
            'Value' => $data['type'] ?? '',
            'Required' => true
        ]
    );
    $res.= input('selection_type', $type_form,
        [
            'type' => 'radio',
            'size' => 'col-12',
            'label' => 'Variação',
            'variation' => 'inline',
            'name' => "Fields[$counter][variation]",
            'input_id' => "variation-$counter",
            'Options' => [
                [ 'value' => 'original', 'display' => 'Original' ],
                [ 'value' => 'inline', 'display' => 'Em linha' ],
                [ 'value' => 'balloons', 'display' => 'Balões' ],
                [ 'value' => 'btn-group', 'display' => 'Grupo de botões' ],
                [ 'value' => 'block', 'display' => 'Blocos' ],
                [ 'value' => 'multiple', 'display' => 'Múltiplo' ],
            ],
            'Value' => $data['variation'] ?? 'original',
            'Required' => true
        ]
    );
    $res.= common_inputs_for_crud($type_form, 'group_options',
        [
            'view_in_list' => $data['view_in_list'] ?? '',
            'Required' => $data['Required'] ?? '',
            'disabled' => $data['disabled'] ?? '',
            'readonly' => $data['readonly'] ?? '',
            'run_before_action' => $data['run_before_action'] ?? '',
            'run_after_action' => $data['run_after_action'] ?? '',
        ],
    $counter);
    $res.= sizes_selector($type_form, 'Fields', $data['size'] ?? '', $counter);

    $advanced_options = "<div class='form-row p-3'>";
    $advanced_options.= common_inputs_for_crud($type_form, 'table', $data['table'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'function_proccess', $data['function_proccess'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'function_view', $data['function_view'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'div_class', $data['div_class'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'div_attributes', $data['div_attributes'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'status_id', $data['status_id'] ?? 1, $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'input_id', $data['input_id'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'Alert', $data['Alert'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'subscribers_only', $data['subscribers_only'] ?? '', $counter);
    $advanced_options.= "</div>";

    $res.= dropdown_content( ['size' => 'col-md-6', 'title' => 'Opções avançadas', 'content' => $advanced_options] );

    $res.= common_inputs_for_crud($type_form, 'bd_action', null, $counter);
    $res.= "</div>";

    // Box to create the options.
    $res.= addable_content([
        'group' => 'input-content',
        'title' => 'Opções',
        'type_form' => $type_form,
        'counter' => $counter,
        'function' => "inputs_for_{$type_field}_content",
        'content' => $data['Options'] ?? null,
        'type' => $type_field
    ]);


    $res.= "<div class='form-row'>";
    $res.= input('textarea', $type_form,
        [
            'size' => 'col-md-6',
            'attributes' => 'rows:(1);',
            'label' => 'Através de uma função',
            'name' => "Fields[$counter][options_resolver]",
            'input_id' => "options_resolver-$counter",
            'Value' => $data['options_resolver'] ?? '',
        ]
    );
    $res.= input('basic', $type_form,
        [
            'size' => 'col-md-6',
            'label' => 'Através de uma tabela',
            'name' => "Fields[$counter][Query]",
            'input_id' => "Query-$counter",
            'Value' => $data['Query'] ?? "SELECT [id] as value, [name] as display FROM [table]",
        ]
    );
    $res.= "</div>";

    return $res;
}

function inputs_for_selection_type_content(string $type_form, $counter, $module_counter, array $data = [])
{
    $res = '<div class="form-row">';
    $res.= input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Value da opção',
            'name' => "Fields[$counter][Options][$module_counter][value]",
            'Value' => $data['value'] ?? '',
            'Required' => true
        ]
    );
    $res.= input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Texto da opção',
            'name' => "Fields[$counter][Options][$module_counter][display]",
            'Value' => $data['display'] ?? '',
        ]
    );
    $res.= input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Name',
            'name' => "Fields[$counter][Options][$module_counter][name]",
            'Value' => $data['name'] ?? ''
        ]
    );

    $res.= input('textarea', $type_form,
        [
            'size' => 'col-12',
            'label' => 'Atributos da opção',
            'name' => "Fields[$counter][Options][$module_counter][description]",
            'Value' => $data['description'] ?? '',
        ]
    );
    $res.= input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Highlight',
            'name' => "Fields[$counter][Options][$module_counter][highlight]",
            'Value' => $data['highlight'] ?? '',
        ]
    );
    $res.= input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Small',
            'name' => "Fields[$counter][Options][$module_counter][small]",
            'Value' => $data['small'] ?? '',
        ]
    );
    $res.= input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Atributos da opção',
            'name' => "Fields[$counter][Options][$module_counter][attributes]",
            'Value' => $data['attributes'] ?? '',
        ]
    );

    $res.= input('selection_type', $type_form,
        [
            'type' => 'checkbox',
            'size' => 'col-12',
            'variation' => 'inline',
            'name' => "Fields[$counter][variation]",
            'Options' => [
                [
                    'name' => "Fields[$counter][Options][$module_counter][checked]",
                    'value' => 'true',
                    'display' => 'Pré-selecionado',
                    'checked' => $data['checked'] ?? '',
                ],
                [
                    'name' => "Fields[$counter][Options][$module_counter][required]",
                    'value' => 'true',
                    'display' => 'Obrigatório',
                    'checked' => $data['required'] ?? '',
                ],
                [
                    'name' => "Fields[$counter][Options][$module_counter][disabled]",
                    'value' => 'true',
                    'display' => 'Desabilidado',
                    'checked' => $data['disabled'] ?? '',
                ],
            ],
        ]
    );
    $res.= "</div>";

    return addable_content_input_group([ 'group' => 'custom-urls', 'title' => 'Opção', 'content' => $res, 'counter' => $counter, 'module_counter' => $module_counter ]);
}
