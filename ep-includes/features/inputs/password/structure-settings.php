<?php
if(!isset($seg)) exit;

/**
 * Generate HTML form inputs for configuring 'password'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'password'.
 */
function inputs_for_password(string $type_form, $counter, array $data = [])
{
    $type_field = 'password';

    $res = '<div class="form-row">';
    $res.= common_inputs_for_crud($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_crud($type_form, 'depth', $data['depth'] ?? 0, $counter);
    $res.= common_inputs_for_crud($type_form, 'type_field', $type_field, $counter);
    $res.= common_inputs_for_crud($type_form, 'label', $data['label'] ?? '', $counter);
    $res.= common_inputs_for_crud($type_form, 'name', $data['name'] ?? '', $counter);
    $res.= input('selection_type', $type_form,
        [
            'attributes' => 'data-attachments:();',
            'size' => 'col-md-6 col-lg-4',
            'type' => 'radio',
            'label' => 'Tipo do input',
            'name' => "Fields[$counter][type]",
            'input_id' => "type-$counter",
            'Options' => [
                [
                    'value' => 'default',
                    'display' => 'Padrão',
                ],
                [
                    'value' => 'new-password',
                    'display' => 'Nova senha',
                ],
            ],
            'Value' => $data['type'] ?? 'default',
            'Required' => true
        ]
    );
    $res.= input('selection_type', $type_form,
        [
            'size' => 'col-12',
            'type' => 'switch',
            'name' => "Fields[$counter][can_genarate]",
            'input_id' => "can_genarate-$counter",
            'Options' => [
                [
                    'value' => '1',
                    'display' => 'Pode gerar senha',
                ],
            ],
            'Value' => $data['can_genarate'] ?? '',
            'Required' => true
        ]
    );
    $advanced_options = "<div class='form-row p-3'>";
    $advanced_options.= common_inputs_for_crud($type_form, 'table', $data['table'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'Placeholder', $data['Placeholder'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'attributes', $data['attributes'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'class', $data['class'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'status_id', $data['status_id'] ?? 1, $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'obs', $data['obs'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'Alert', $data['Alert'] ?? '', $counter);
    $advanced_options.= "</div>";

    $res.= dropdown_content( ['size' => 'col-md-6', 'title' => 'Opções avançadas', 'content' => $advanced_options] );
    $res.= sizes_selector($type_form, 'Fields', $data['size'] ?? '', $counter);
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
    $res.= common_inputs_for_crud($type_form, 'bd_action', null, $counter);

    $res.= "</div>";

    return $res;
}
