<?php
if(!isset($seg)) exit;


/**
 * Generate HTML form inputs for configuring 'button'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'button'.
 */
function inputs_for_button(string $type_form, $counter, array $data = [])
{
    global $info;

    $type_field = 'button';

    if ($type_form == 'insert')
    {
        $data['class']      = "btn btn-st";
        $data['name']       = 'process-form';
        $data['input_id']   = 'process-form';
        $data['Value']      = 'Enviar';
    }

    $res = '<div class="form-row">';
    $res.= common_inputs_for_crud($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_crud($type_form, 'depth', $data['depth'] ?? 0, $counter);
    $res.= common_inputs_for_crud($type_form, 'type_field', $type_field, $counter);
    $res.= input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Título do botão',
            'Placeholder' => 'Cadastrar/Editar/Enviar',
            'name' => "Fields[$counter][Value]",
            'input_id' => "Value-$counter",
            'Value' => $data['Value'] ?? '',
            'Required' => true,
        ]
    );

    $res.= common_inputs_for_crud($type_form, 'class', $data['class'] ?? 'btn btn-secondary', $counter);
    $res.= input('selection_type', $type_form,
        [
            'type' => 'checkbox',
            'size' => 'col-12',
            'name' => "Fields[$counter][type]",
            'variation' => 'inline',
            'input_id' => "type-$counter",
            'Options' => [
                [
                    'value' => 1,
                    'display' => 'Largura 100%',
                    'name' => "Fields[$counter][block]",
                    'checked' => $data['block'] ?? '',
                ],
            ],
            'Required' => true
        ]
    );
    $res.= sizes_selector($type_form, 'Fields', $data['size'] ?? '', $counter);
    $res.= input('hidden', $type_form, [ 'name' => "Fields[$counter][status_id]", 'input_id' => "status_id-$counter", 'Value' => $data['status_id'] ?? 1, 'Required' => true ]);

    $advanced_options = "<div class='form-row p-3'>";
    $advanced_options.= common_inputs_for_crud($type_form, 'attributes', $data['attributes'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'div_class', $data['div_class'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'div_attributes', $data['div_attributes'] ?? '', $counter);
    $advanced_options.= "</div>";

    $res.= dropdown_content( ['size' => 'col-md-6', 'title' => 'Opções avançadas', 'content' => $advanced_options] );
    $res.= "</div>";

    return $res;
}
