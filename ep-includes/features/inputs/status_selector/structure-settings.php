<?php
if(!isset($seg)) exit;


/**
 * Generate HTML form inputs for configuring 'status_selector'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'status_selector'.
 */
function inputs_for_status_selector(string $type_form, $counter, array $data = [])
{
    $type_field = 'status_selector';

    $res = '<div class="form-row">';
    $res.= common_inputs_for_crud($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_crud($type_form, 'depth', $data['depth'] ?? 0, $counter);
    $res.= common_inputs_for_crud($type_form, 'type_field', $type_field, $counter);
    $res.= input('selection_type', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Tipo de status',
            'name' => "Fields[$counter][function_proccess]",
            'input_id' => "function_proccess-$counter",
            'Options' => type_status(true),
            'Value' => $data['function_proccess'] ?? '',
            'Required' => true
        ]
    );
    $res.= common_inputs_for_crud($type_form, 'Value', $data['Value'] ?? '', $counter);
    $res.= common_inputs_for_crud($type_form, 'view_in_list', $data['view_in_list'] ?? '', $counter);
    $res.= common_inputs_for_crud($type_form, 'Required', $data['Required'] ?? '', $counter);
    $res.= common_inputs_for_crud($type_form, 'disabled', $data['disabled'] ?? '', $counter);
    $res.= common_inputs_for_crud($type_form, 'bd_action', null, $counter);
    $res.= sizes_selector($type_form, 'Fields', $data['size'] ?? '', $counter);

    $advanced_options = "<div class='form-row p-3'>";
    $advanced_options.= common_inputs_for_crud($type_form, 'table', $data['table'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'name', $data['name'] ?? 'status_id', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'input_id', $data['input_id'] ?? 'status_id', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'status_id', $data['status_id'] ?? 1, $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'div_class', $data['div_class'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'div_attributes', $data['div_attributes'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'obs', $data['obs'] ?? '', $counter);
    $advanced_options.= "</div>";

    $res.= dropdown_content( ['size' => 'col-md-6', 'title' => 'Opções avançadas', 'content' => $advanced_options] );
    $res.= "</div>";

    return $res;
}
