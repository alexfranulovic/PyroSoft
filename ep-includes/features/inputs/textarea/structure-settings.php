<?php
if(!isset($seg)) exit;


/**
 * Generate HTML form inputs for configuring 'textarea'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'textarea'.
 */
function inputs_for_textarea(string $type_form, $counter, array $data = [])
{
    $type_field = 'textarea';

    $res = '<div class="form-row">';
    $res.= common_inputs_for_crud($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_crud($type_form, 'depth', $data['depth'] ?? 0, $counter);
    $res.= common_inputs_for_crud($type_form, 'type_field', $type_field, $counter);
    $res.= common_inputs_for_crud($type_form, 'label', $data['label'] ?? '', $counter);
    $res.= common_inputs_for_crud($type_form, 'name', $data['name'] ?? '', $counter);
    $res.= input('selection_type', $type_form,
        [
            'type' => 'switch',
            'size' => 'col-md-6 col-lg-4',
            'name' => "Fields[$counter][text_editor]",
            'input_id' => "text_editor-$counter",
            'Options' => [ ['value' => '1', 'display' => 'Editor de texto'] ],
            'Value' => $data['text_editor'] ?? '',
        ]
    );
    // $res.= common_inputs_for_crud($type_form, 'Required', $data['Required'] ?? '', $counter);
    // $res.= common_inputs_for_crud($type_form, 'disabled', $data['disabled'] ?? '', $counter);

    $advanced_options = "<div class='form-row p-3'>";
    $advanced_options.= common_inputs_for_crud($type_form, 'table', $data['table'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'Placeholder', $data['Placeholder'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'attributes', $data['attributes'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'div_class', $data['div_class'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'div_attributes', $data['div_attributes'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'status_id', $data['status_id'] ?? 1, $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'input_id', $data['input_id'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'obs', $data['obs'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'function_proccess', $data['function_proccess'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'function_view', $data['function_view'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'subscribers_only', $data['subscribers_only'] ?? '', $counter);
    $advanced_options.= input('textarea', $type_form,
        [
            'size' => 'col-12" rows="3"',
            'label' => 'Value do Input',
            'Placeholder' => 'Aqui você pode colocar o conteúdo de um post',
            'name' => "Fields[$counter][Value]",
            'input_id' => "Value-$counter",
            'Value' => $data['Value'] ?? '',
        ]
    );
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
