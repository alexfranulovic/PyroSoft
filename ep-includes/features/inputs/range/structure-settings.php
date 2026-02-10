<?php
if(!isset($seg)) exit;

/**
 * Generate HTML form inputs for configuring 'range'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'range'.
 */
function inputs_for_range(string $type_form, $counter, array $data = [])
{
    $type_field = 'range';

    $res = '<div class="form-row">';
    $res.= common_inputs_for_crud($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_crud($type_form, 'depth', $data['depth'] ?? 0, $counter);
    $res.= common_inputs_for_crud($type_form, 'type_field', $type_field, $counter);
    $res.= common_inputs_for_crud($type_form, 'label', $data['label'] ?? '', $counter);
    $res.= common_inputs_for_crud($type_form, 'name', $data['name'] ?? '', $counter);

    $res .= input('basic', $type_form, [
        'size' => 'col-6 col-md-4',
        'label' => 'Minimum value',
        'type' => 'number',
        'name' => "Fields[$counter][range_min]",
        'input_id' => "range_min_$counter",
        'Value' => $data['range_min'] ?? 0,
        'attributes' => 'step="0.1"',
        'Required' => true
    ]);

    $res .= input('basic', $type_form, [
        'size' => 'col-6 col-md-4',
        'label' => 'Minimum value',
        'type' => 'number',
        'name' => "Fields[$counter][range_min]",
        'input_id' => "range_min_$counter",
        'Value' => $data['range_min'] ?? 0,
        'attributes' => 'step="0.1"',
        'Required' => true
    ]);

    // Max value
    $res .= input('basic', $type_form, [
        'size' => 'col-6 col-md-4',
        'label' => 'Maximum value',
        'type' => 'number',
        'name' => "Fields[$counter][range_max]",
        'input_id' => "range_max_$counter",
        'Value' => $data['range_max'] ?? 100,
        'attributes' => 'step="0.1"',
        'Required' => true
    ]);

    $res .= input('basic', $type_form, [
        'size' => 'col-md-4',
        'label' => 'Step interval',
        'type' => 'number',
        'name' => "Fields[$counter][range_step]",
        'input_id' => "range_step_$counter",
        'Value' => $data['range_step'] ?? 1,
        'attributes' => 'step="0.1"',
        'Required' => true
    ]);

    $res .= input('basic', $type_form, [
        'size' => 'col-md-4',
        'label' => 'Prefix',
        'name' => "Fields[$counter][prefix]",
        'input_id' => "prefix_$counter",
        'Value' => $data['prefix'] ?? null,
        'Required' => true
    ]);


    // Range mode: single value or between two values
    $res .= input('selection_type', $type_form, [
        'size' => 'col-md-6',
        'label' => 'Range mode',
        'name' => "Fields[$counter][range_mode]",
        'input_id' => "range_mode_$counter",
        'Options' => [
            [ 'value' => 'simple', 'display' => 'Single value (one handle)' ],
            [ 'value' => 'between', 'display' => 'Interval (two handles)' ],
        ],
        'Value' => $data['range_mode'] ?? 'simple',
        'Required' => true
    ]);

    // Show numbers below the slider?
    $res .= input('selection_type', $type_form, [
        'size' => 'col-md-6',
        'type' => 'switch',
        'name' => "Fields[$counter][show_numbers]",
        'input_id' => "show_numbers_$counter",
        'Options' => [
            [ 'value' => '1', 'display' => 'Show numbers below' ]
        ],
        'Value' => $data['show_numbers'] ?? '',
    ]);

    $advanced_options = "<div class='form-row p-3'>";
    $advanced_options.= common_inputs_for_crud($type_form, 'table', $data['table'] ?? '', $counter);
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

    $res.= "</div>";

    return $res;
}
