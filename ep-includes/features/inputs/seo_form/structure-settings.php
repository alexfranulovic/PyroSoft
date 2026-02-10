<?php
if(!isset($seg)) exit;


/**
 * Generate HTML form inputs for configuring 'seo_form'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'seo_form'.
 */
function inputs_for_seo_form(string $type_form, $counter, array $data = [])
{
    $type_field = 'seo_form';

    $res = '<div class="form-row">';
    $res.= common_inputs_for_crud($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_crud($type_form, 'depth', $data['depth'] ?? 0, $counter);
    $res.= common_inputs_for_crud($type_form, 'type_field', $type_field, $counter);
    $res.= common_inputs_for_crud($type_form, 'name', $data['name'] ?? 'seo', $counter);
    $res.= common_inputs_for_crud($type_form, 'status_id', $data['status_id'] ?? 1, $counter);
    $res.= common_inputs_for_crud($type_form, 'bd_action', null, $counter);
    $res.= common_inputs_for_crud($type_form, 'subscribers_only', $data['subscribers_only'] ?? '', $counter);
    $res.= input('selection_type', $type_form,
        [
            'type' => 'radio',
            'size' => 'col-md-6',
            'variation' => 'inline',
            'label' => 'Formato do Navbar',
            'name' => "Fields[$counter][mode]",
            'input_id' => "mode-$counter",
            'Options' => [
                [ 'value' => 'common', 'display' => 'Generalista' ],
                [ 'value' => 'content', 'display' => 'Do conteúdo' ],
            ],
            'Value' => $data['mode'] ?? 'common',
        ]
    );
    $res.= "</div>";

    return $res;
}
