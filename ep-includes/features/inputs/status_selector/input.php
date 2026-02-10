<?php

function input_status_selector(string $type_form, array $Attr = [])
{
    extract($Attr);
    $res = '';

    $function_proccess = $function_proccess ?? 'general_status';

    return input(
        'selection_type',
        $type_form,
        [
            'div_attributes' => $div_attributes,
            'div_class' => $div_class,
            'attributes' => $attributes,
            'size' => $size,
            'label' => 'Status de registro',
            'name' => $name ?? 'status_id',
            'input_id' => $input_id ?? 'status_id',
            'Options' => $function_proccess(true),
            'Value' => $Value ?? null,
            'Required' => $Required,
            'data_required' => $data_required
        ]
    );
}
