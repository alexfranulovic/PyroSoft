<?php

function input_payment_methods(string $type_form, array $Attr = [])
{
    extract($Attr);
    $res = '';

    global $config, $seg;

    $user_payment_methods = format_user_payment_gateways(false);
    if (!empty($user_payment_methods))
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
                'Options' => format_user_payment_gateways(false),
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
            'label' => 'Método de pagamento',
            'name' => 'payment_method',
            'Options' => format_payment_gateways(false),
            'Required' => true
        ]
    );

    $field_attr = [];

    if (!empty($plan_id))
    $field_attr['plan_id'] = $plan_id;

    if (!empty($product_id))
    $field_attr['product_id'] = $product_id;

    if (!empty($custom_statement_descriptor))
    $field_attr['custom_statement_descriptor'] = $custom_statement_descriptor;

    $payment_methods = $config['active_payment_methods'] ?? [];
    foreach ($payment_methods as $key => $provider_method)
    {
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
