<?php
if(!isset($seg)) exit;

/**
 * Generate HTML form inputs for configuring 'address_form'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'address_form'.
 */
function inputs_for_address_form(string $type_form, $counter, array $data = [])
{
    $type_field = 'address_form';

    $res = '<div class="form-row">';
    $res.= common_inputs_for_crud($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_crud($type_form, 'depth', $data['depth'] ?? 0, $counter);
    $res.= common_inputs_for_crud($type_form, 'type_field', $type_field, $counter);
    $res.= common_inputs_for_crud($type_form, 'label', $data['label'] ?? 'Endereço', $counter);
    $res.= common_inputs_for_crud($type_form, 'name', $data['name'] ?? 'address', $counter);
    $res.= common_inputs_for_crud($type_form, 'view_in_list', $data['view_in_list'] ?? '', $counter);
    $res.= common_inputs_for_crud($type_form, 'Required', $data['Required'] ?? '', $counter);


    $advanced_options = "<div class='form-row p-3'>";
    $advanced_options.= common_inputs_for_crud($type_form, 'table', $data['table'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'status_id', $data['status_id'] ?? 1, $counter);
    $advanced_options.= input('selection_type', $type_form,
        [
            'type' => 'switch',
            'size' => 'col-12',
            'label' => 'Autocomplete',
            'name' => "Fields[$counter][function_proccess]",
            'input_id' => "function_proccess-$counter",
            'Options' => "4|| 1|| Sim;",
            'Value' => $data['function_proccess'] ?? '1',
        ]
    );
    $advanced_options.= common_inputs_for_crud($type_form, 'subscribers_only', $data['subscribers_only'] ?? '', $counter);
    $advanced_options.= "</div>";

    $res.= dropdown_content( ['size' => 'col-md-6', 'title' => 'Opções avançadas', 'content' => $advanced_options] );
    $res.= common_inputs_for_crud($type_form, 'bd_action', null, $counter);
    $res.= "</div>";

    return $res;
}
