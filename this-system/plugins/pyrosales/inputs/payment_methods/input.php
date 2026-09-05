<?php

function input_payment_methods(string $type_form, array $Attr = [])
{
    extract($Attr);
    $res = '';

    global $config, $seg;

    $field_attr                       = [];
    $only_these                       = $Attr['only_these'] ?? [];
    $bypass_saved_methods             = $Attr['bypass_saved_methods'] ?? false;
    $field_attr['allow_installments'] = false;
    $user_payment_methods             = format_user_payment_gateways($only_these);


    if (!$bypass_saved_methods && !empty($user_payment_methods))
    {
        $res.= input(
            'selection_type',
            $type_form,
            [
                'div_attributes' => $div_attributes,
                'size' => 'col-12',
                'type' => 'radio',
                'variation' => 'group-block',
                'label' => 'Seus cartões',
                'name' => 'payment_method',
                'Options' => $user_payment_methods,
                'Required' => true
            ]
        );
    }

    $res.= input(
        'selection_type',
        $type_form,
        [
            'div_attributes' => $div_attributes,
            'size' => 'col-12',
            'type' => 'radio',
            'variation' => 'group-block',
            'label' => !empty($Attr['label']) ? $Attr['label'] : 'Método de pagamento',
            'name' => 'payment_method',
            'Options' => format_payment_gateways('checkout', [], $only_these),
            'Required' => true
        ]
    );


    if (!empty($plan_id)) {
        $field_attr['plan_id'] = $plan_id;
        $field_attr['allow_installments'] = false;
    }

    if (!empty($product_id)) {
        $field_attr['product_id'] = $product_id;
        $field_attr['allow_installments'] = true;
    }

    if (!empty($one_off))
    {
        $field_attr['one_off'] = $one_off;
        $field_attr['allow_installments'] = get_system_info('pyrosales_one_off_allow_installments');
    }

    if (!empty($custom_statement_descriptor))
    $field_attr['custom_statement_descriptor'] = $custom_statement_descriptor;

    $payment_methods = $config['active_payment_methods'] ?? [];
    foreach ($payment_methods as $key => $provider_method)
    {
        $field_attr['provider_method'] = $GLOBALS['payment_gateways'][$provider_method];

        $provider = explode('.', $provider_method)[0];
        $provider_method = str_replace('.', '_', $provider_method);

        $fields = "{$provider_method}_fields";
        require_once plugin_path("{$provider}/src/checkout-fields.php");

        if (function_exists($fields)) {
            $res.= $fields($type_form, $field_attr);
        }
    }

    return $res;
}
