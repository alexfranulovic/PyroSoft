<?php
if(!isset($seg)) exit;


/**
 * Generate HTML form inputs for configuring 'upload'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'upload'.
 */
function inputs_for_upload(string $type_form, $counter, array $data = [])
{
    global $allowed_mime_types;

    $type_field = 'upload';

    $type = $data['type'] ?? null;

    $allowed_mime_types = isset($allowed_mime_types[$type])
        ? $allowed_mime_types[$type]
        : [];

    $allowed_mime_types = implode(', ', $allowed_mime_types) ?? '';

    $res = '<div class="form-row">';
    $res.= common_inputs_for_crud($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_crud($type_form, 'depth', $data['depth'] ?? 0, $counter);
    $res.= common_inputs_for_crud($type_form, 'type_field', $type_field, $counter);
    $res.= common_inputs_for_crud($type_form, 'label', $data['label'] ?? '', $counter);
    $res.= common_inputs_for_crud($type_form, 'name', $data['name'] ?? '', $counter);
    $res.= input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Diretório dos registros',
            'Placeholder' => 'Nome da pasta',
            'name' => "Fields[$counter][Src]",
            'input_id' => "Src-$counter",
            'Value' => $data['Src'] ?? '',
            'Required' => true,
        ]
    );
    $res.= input('basic', $type_form, [
        'size'      => 'col-12',
        'label'     => 'Extensões permitidas (ex: .jpg, .png, .pdf)',
        'name'      => "Fields[$counter][accepted_extensions]",
        'input_id'  => "accepted_extensions-$counter",
        'Placeholder' => '.jpg, .png, .pdf',
        'Value'     => !empty($data['accepted_extensions'])
            ? $data['accepted_extensions']
            : $allowed_mime_types,
    ]);
    $res.= input('basic', $type_form, [
        'size'      => 'col-12',
        'label'     => 'Nome final',
        'name'      => "Fields[$counter][final_name]",
        'input_id'  => "final_name-$counter",
        'Value'     => $data['final_name'] ?? '',
        'Alert'     => 'Escreva o nome do arquivo (pode forçar uma extenção se quiser).',
    ]);
    $res.= input('selection_type', $type_form,
        [
            'type' => 'radio',
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Tipo de upload',
            'name' => "Fields[$counter][type]",
            'input_id' => "type-$counter",
            'Options' => [
                [
                    'value' => 'images',
                    'display' => 'Imagem',
                    'attributes' => 'data-upload-input-is:(images);'
                ],
                [
                    'value' => 'archives',
                    'display' => 'Arquivos',
                    'attributes' => 'data-upload-input-is:(archives);',
                ],
                [
                    'value' => 'videos',
                    'display' => 'Vídeo',
                    'attributes' => 'data-upload-input-is:(videos);',
                ],
                [
                    'value' => 'audios',
                    'display' => 'Áudio',
                    'attributes' => 'data-upload-input-is:(audios);',
                ]
            ],
            'Value' => $type,
            'Required' => true
        ]
    );
    $res.= input('selection_type', $type_form,
        [
            'type' => 'switch',
            'size' => 'col-md-6 col-lg-4',
            'name' => "Fields[$counter][multiple]",
            'Options' => [
                [
                    'value' => '1',
                    'display' => 'Aceita multiplos',
                    'name' => "Fields[$counter][multiple]",
                    'checked' => (!empty($data['multiple']) AND $data['multiple']) ? true : false
                ],
                [
                    'value' => '1',
                    'display' => 'Capturar ao vivo',
                    'name' => "Fields[$counter][capture]",
                    'checked' => (!empty($data['capture']) AND $data['capture']) ? true : false
                ],
                [
                    'value' => '1',
                    'display' => 'Force .webp (images only)',
                    'name' => "Fields[$counter][force_webp]",
                    'checked' => (!empty($data['force_webp']) AND $data['force_webp']) ? true : false
                ],
                [
                    'value' => 'profile',
                    'display' => 'Profile pic (images only)',
                    'name' => "Fields[$counter][variation]",
                    'checked' => (!empty($data['variation']) AND $data['variation'] == 'profile') ? true : false
                ],
            ],
        ]
    );
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
    $res.= hr();

    $res.= input('selection_type', $type_form,
        [
            'type' => 'switch',
            'size' => 'col-md-6',
            'label' => 'AWS options',
            'name' => "Fields[$counter][upload_to_s3]",
            'Value' => $data['upload_to_s3'] ?? null,
            'Options' => [
                [
                    'value' => '1',
                    'display' => 'Upload to AWS S3',
                ],
            ],
        ]
    );
    $res.= input('selection_type', $type_form,
        [
            'type' => 'radio',
            'size' => 'col-md-6',
            'label' => 'Visibility',
            'variation' => 'inline',
            'name' => "Fields[$counter][visibility]",
            'Required' => true,
            'Value' => $data['visibility'] ?? DEFAULT_FILES_VISIBILITY,
            'Options' => [
                [
                    'value' => 'public',
                    'display' => 'Public',
                ],
                [
                    'value' => 'private',
                    'display' => 'Private',
                ],
            ],
        ]
    );
    $res.= sizes_selector($type_form, 'Fields', $data['size'] ?? '', $counter);

    $advanced_options = "<div class='form-row p-3'>";
    $advanced_options.= common_inputs_for_crud($type_form, 'table', $data['table'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'attributes', $data['attributes'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'div_class', $data['div_class'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'div_attributes', $data['div_attributes'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'status_id', $data['status_id'] ?? 1, $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'input_id', $data['input_id'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'Value', $data['Value'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'obs', $data['obs'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'Alert', $data['Alert'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'function_proccess', $data['function_proccess'] ?? '', $counter);
    $advanced_options.= common_inputs_for_crud($type_form, 'function_view', $data['function_view'] ?? '', $counter);
    $advanced_options.= input('basic', $type_form,
        [
            'size' => 'col-12',
            'label' => 'Largura e Altura (Ex: 300X300)',
            'name' => "Fields[$counter][image_size]",
            'input_id' => "image_size-$counter",
            'Value' => $data['image_size'] ?? '',
            // 'Required' => true,
        ]
    );
    $advanced_options.= common_inputs_for_crud($type_form, 'subscribers_only', $data['subscribers_only'] ?? '', $counter);
    $advanced_options.= "</div>";

    $res.= dropdown_content( ['size' => 'col-md-6', 'title' => 'Opções avançadas', 'content' => $advanced_options] );
    $res.= common_inputs_for_crud($type_form, 'bd_action', null, $counter);
    $res.= "</div>";

    return $res;
}
