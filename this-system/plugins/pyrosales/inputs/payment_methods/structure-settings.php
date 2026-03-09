<?php
if(!isset($seg)) exit;

/**
 * Generate HTML form inputs for configuring 'payment_methods'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'payment_methods'.
 */
function inputs_for_payment_methods(string $type_form, $counter, array $data = [])
{
    $type_field = 'payment_methods';

    $res = '<div class="form-row">';
    $res.= common_inputs_for_crud($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_crud($type_form, 'depth', $data['depth'] ?? 0, $counter);
    $res.= common_inputs_for_crud($type_form, 'type_field', $type_field, $counter);
    $res.= sizes_selector($type_form, 'Fields', $data['size'] ?? '', $counter);
    $res.= "</div>";

    return $res;
}
