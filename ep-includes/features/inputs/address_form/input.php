<?php

function input_address_form(string $type_form, array $Attr = [])
{
    extract($Attr);
    $res = '';

    if (isset($Value))
    {
        $address = is_json($Value)
            ? json_decode($Value, true)
            : $Value;
    }

    $name              = $name ?? 'address';
    $Required          = !empty($Required) ? true : false;

    $function_proccess = !empty($function_proccess)
        ? 'onblur:(calculoFrete());"'
        : '';

    $res = input(
        'basic',
        $type_form,
        [
            'size' => 'col-md-6',
            'label' => 'CEP',
            'attributes' => $function_proccess,
            'class' => 'mask-cep',
            'Placeholder' => '11740-000',
            'name' => $name.'[zipcode]',
            'input_id' => 'zipcode',
            'Value' => $address['zipcode'] ?? '',
            'Required' => $Required,
        ]
    ) . input(
        'basic',
        $type_form,
        [
            'size' => 'col-md-6',
            'label' => 'Cidade',
            'Placeholder' => 'Ribeirão Preto',
            'name' => $name.'[city]',
            'input_id' => 'city',
            'Value' => $address['city'] ?? '',
            'Required' => $Required,
        ]
    ) . input(
        'selection_type',
        $type_form,
        [
            'size' => 'col-md-6',
            'label' => 'Estado',
            'name' => $name.'[state]',
            'input_id' => 'state',
            'Options' => states_address(true),
            'Value' => $address['state'] ?? '',
            'Required' => $Required
        ]
    ) . input(
        'basic',
        $type_form,
        [
            'size' => 'col-md-6',
            'label' => 'Endereço',
            'Placeholder' => 'Rua Doutor Paulo Muzy',
            'name' => $name.'[street]',
            'input_id' => 'street',
            'Value' => $address['street'] ?? '',
            'Required' => $Required,
        ]
    ) . input(
        'basic',
        $type_form,
        [
            'size' => 'col-md-6',
            'label' => 'Número',
            'Placeholder' => '2676',
            'name' => $name.'[number]',
            'input_id' => 'number',
            'Value' => $address['number'] ?? '',
            'Required' => $Required,
        ]
    ) . input(
        'basic',
        $type_form,
        [
            'size' => 'col-md-6',
            'label' => 'Complemento (Opcional)',
            'Placeholder' => 'Casa',
            'name' => $name.'[complement]',
            'input_id' => 'complement',
            'Value' => $address['complement'] ?? '',
        ]
    ) . input(
        'basic',
        $type_form,
        [
            'size' => 'col-md-6',
            'label' => 'Bairro',
            'Placeholder' => 'Vila Mariana',
            'name' => $name.'[district]',
            'input_id' => 'district',
            'Value' => $address['district'] ?? '',
            // 'Required' => $Required,
        ]
    );

    return $res;
}