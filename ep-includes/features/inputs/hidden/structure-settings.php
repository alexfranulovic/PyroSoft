<?php
if(!isset($seg)) exit;


/**
 * Generate HTML form inputs for configuring 'hidden'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'hidden'.
 */
function inputs_for_hidden(string $type_form, $counter, array $data = [])
{
    $type_field = 'hidden';

    $res = '<div class="form-row">';
    $res.= common_inputs_for_crud($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_crud($type_form, 'depth', $data['depth'] ?? 0, $counter);
    $res.= common_inputs_for_crud($type_form, 'type_field', $type_field, $counter);
    $res.= common_inputs_for_crud($type_form, 'attributes', $data['attributes'] ?? '', $counter);
    $res.= common_inputs_for_crud($type_form, 'input_id', $data['input_id'] ?? '', $counter);
    $res.= common_inputs_for_crud($type_form, 'status_id', $data['status_id'] ?? 1, $counter);
    $res.= common_inputs_for_crud($type_form, 'name', $data['name'] ?? '', $counter);
    $res.= input('selection_type', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Tipo do input',
            'name' => "Fields[$counter][type]",
            'input_id' => "type-$counter",
            'Options' => [
                [ 'value' => 'custom-value', 'display' => 'Valor personalizado' ],
                [ 'value' => 'GET'     ],
                [ 'value' => 'SERVER'  ],
                [ 'value' => 'SESSION' ],
            ],
            'Value' => $data['type'] ?? null,
            'Required' => true
        ]
    );
    $res.= input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Ponteiro do valor',
            'name' => "Fields[$counter][pointer]",
            'Alert' => "Caso tenha precise de um subitem, use o separador: ->",
            'input_id' => "pointer-$counter",
            'Value' => $data['pointer'] ?? '',
            // 'Required' => true,
        ]
    );
    $res.= common_inputs_for_crud($type_form, 'Value', $data['Value'] ?? '', $counter);
    $res.= common_inputs_for_crud($type_form, 'table', $data['table'] ?? '', $counter);
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
    $res.= common_inputs_for_crud($type_form, 'view_in_list', $data['view_in_list'] ?? '', $counter);
    $res.= common_inputs_for_crud($type_form, 'subscribers_only', $data['subscribers_only'] ?? '', $counter);
    // $res.= common_inputs_for_crud($type_form, 'unique_key', $data['unique_key'] ?? '', $counter);
    $res.= common_inputs_for_crud($type_form, 'bd_action', null, $counter);
    $res.= "</div>";

    return $res;
}
