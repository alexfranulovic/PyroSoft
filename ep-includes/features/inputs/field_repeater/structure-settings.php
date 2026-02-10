<?php
if(!isset($seg)) exit;


/**
 * Generate HTML form inputs for configuring 'field_repeater'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'field_repeater'.
 */
function inputs_for_field_repeater(string $type_form, $counter, array $data = [])
{
    $type_field = 'field_repeater';

    $res = '<div class="form-row">';
    $res.= common_inputs_for_crud($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_crud($type_form, 'depth', $data['depth'] ?? 0, $counter);
    $res.= common_inputs_for_crud($type_form, 'type_field', $type_field, $counter);
    $res.= common_inputs_for_crud($type_form, 'label', $data['label'] ?? '', $counter);
    $res.= common_inputs_for_crud($type_form, 'status_id', $data['status_id'] ?? 1, $counter);
    $res.= common_inputs_for_crud($type_form, 'attributes', $data['attributes'] ?? '', $counter);
    $res.= input('selection_type', $type_form,
        [
            'type' => 'radio',
            'variation' => 'inline',
            'size' => 'col',
            'label' => 'Modo de salvar',
            'name' => "Fields[$counter][storage_mode]",
            'input_id' => "storage_mode-$counter",
            'Options' => [
                [ 'value' => 'table', 'display' => 'Table' ],
                [ 'value' => 'json', 'display' => 'Json' ],
            ],
            'Value' => $data['storage_mode'] ?? null,
            'Required' => true
        ]
    );
    $res.= common_inputs_for_crud($type_form, 'table', $data['table'] ?? '', $counter);
    $res.= input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Nome da coluna',
            'attributes' => 'update-title:();',
            'Placeholder' => 'O Name de um input sobre idade seria: idade ou age',
            'name' => "Fields[$counter][name]",
            'input_id' => "name-$counter",
            'Value' => $data['name'] ?? '',
            'Required' => true
        ]
    );
    $res.= break_line();
    $res.= input('basic', $type_form,
        [
            'size' => 'col-6 col-lg-3',
            'label' => 'Titulo do botão +',
            'name' => "Fields[$counter][add_btn_title]",
            'input_id' => "add_btn_title-$counter",
            'Value' => $data['add_btn_title'] ?? '',
        ]
    );
    $res.= input('basic', $type_form,
        [
            'type' => 'number',
            'size' => 'col-6',
            'label' => 'Min. de linhas',
            'name' => "Fields[$counter][min_rows]",
            'input_id' => "min_rows-$counter",
            'Value' => $data['min_rows'] ?? '',
        ]
    );
    $res.= input('basic', $type_form,
        [
            'type' => 'number',
            'size' => 'col-6 col-lg-3',
            'label' => 'Max. de linhas',
            'name' => "Fields[$counter][max_rows]",
            'input_id' => "max_rows-$counter",
            'Value' => $data['max_rows'] ?? '',
        ]
    );
    $res.= input('selection_type', $type_form,
        [
            'type' => 'switch',
            'size' => 'col-md-6 col-lg-4',
            'name' => "Fields[$counter][is_orderable]",
            'input_id' => "is_orderable-$counter",
            'Options' => [ ['value' => '1', 'display' => 'Linhas ordenáveis'] ],
            'Value' => $data['is_orderable'] ?? '',
        ]
    );
    $res.= input('textarea', $type_form,
        [
            'size' => 'col-12',
            'label' => 'Conteúdo',
            'attributes' => 'rows:(4);',
            'name' => "Fields[$counter][content]",
            'input_id' => "content-$counter",
            'Value' => $data['content'] ?? '',
            'text_editor' => 1,
        ]
    );
    $res.= "</div>";

    return $res;
}
