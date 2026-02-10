<?php
if(!isset($seg)) exit;


/**
 * Generate HTML form inputs for configuring 'copy'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'copy'.
 */
function inputs_for_copy(string $type_form, $counter, array $data = [])
{
    $type_field = 'copy';

    $res = '<div class="form-row">';
    $res.= common_inputs_for_crud($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_crud($type_form, 'depth', $data['depth'] ?? 0, $counter);
    $res.= common_inputs_for_crud($type_form, 'type_field', $type_field, $counter);
    $res.= common_inputs_for_crud($type_form, 'label', $data['label'] ?? '', $counter);
    $res.= input('selection_type', $type_form,
        [
            'attributes' => 'data-attachments:();',
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Tipo do input',
            'name' => "Fields[$counter][type]",
            'input_id' => "type-$counter",
            'Options' => type_input(true),
            'Value' => $data['type'] ?? 'text',
            'Required' => true
        ]
    );
    $res.= common_inputs_for_crud($type_form, 'name', $data['name'] ?? '', $counter);
    $advanced_options = "<div class='form-row p-3'>";
    $advanced_options.= common_inputs_for_crud($type_form, 'table', $data['table'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'Placeholder', $data['Placeholder'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'div_class', $data['div_class'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'div_attributes', $data['div_attributes'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'attributes', $data['attributes'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'class', $data['class'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'status_id', $data['status_id'] ?? 1, $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'input_id', $data['input_id'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'Value', $data['Value'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'obs', $data['obs'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'Alert', $data['Alert'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'function_proccess', $data['function_proccess'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'function_view', $data['function_view'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'attachment', $data['attachment'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'subscribers_only', $data['subscribers_only'] ?? '', $counter);
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
    $res.= common_inputs_for_crud($type_form, 'unique_key', $data['unique_key'] ?? '', $counter);

    $res.= "</div>";

    return $res;
}
