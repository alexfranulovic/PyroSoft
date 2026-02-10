<?php
if(!isset($seg)) exit;


/**
 * Generate HTML form inputs for configuring 'g_recaptcha'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'g_recaptcha'.
 */
function inputs_for_g_recaptcha(string $type_form, $counter, array $data = [])
{
    $type_field = 'g_recaptcha';

    $res = '<div class="form-row">';
    $res.= common_inputs_for_crud($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_crud($type_form, 'depth', $data['depth'] ?? 0, $counter);
    $res.= common_inputs_for_crud($type_form, 'type_field', $type_field, $counter);
    $res.= "</div>";

    return $res;
}
