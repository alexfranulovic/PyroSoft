<?php
if (!isset($seg)) exit;

function mercadopago_credit_card_fields(string $type_form, array $Attr = [])
{
    global $info;

    add_asset('footer', "<script src='https://sdk.mercadopago.com/js/v2'></script>");
    add_asset('footer', "<script src='". plugin_path('/mercadopago/assets/scripts/credit_card.js', 'url') ."' defer></script>");

    extract($Attr);

    $res = '';
    // $res.= input('user_payment_methods', 'insert', [
    //     'div_attributes' => "style: (display: none;);",
    //     'div_class' => 'credit_card-fields',
    //     'size' => 'col-12',
    //     'label' => 'seus cartões'
    // ]);


    $res.= input('basic', 'insert', [
        'name' => "payment_data[card_number]",
        'label' => 'Número do cartão',
        'div_attributes' => "style: (display: none;);",
        'div_class' => 'credit_card-fields',
        'class' => 'mask-credit-card-number',
        'attachment' => [
            'append' => '<img alt="Bandeira" src="" style="height: 20px; display: none">'
        ],
        'Required' => true,
        'Alert' => 'Seu cartão será salvo de forma SEGURA para as renovações.'
    ]);

    $res.= input('basic', 'insert', [
        'name' => "payment_data[name]",
        'label' => 'Nome impresso no cartão',
        'div_attributes' => "style: (display: none;);",
        'div_class' => 'credit_card-fields',
        'class' => '',
        'Required' => true,
    ]);

    $res.= input('basic', 'insert', [
        'size' => 'col-6 col-md-3',
        'name' => "payment_data[expiration]",
        'div_attributes' => "style: (display: none;);",
        'div_class' => 'credit_card-fields',
        'class' => 'mask-credit-card-date',
        'label' => 'Expira em (mm/aa)',
        'Required' => true,
    ]);

    $res.= input('basic', 'insert', [
        'size' => 'col-6 col-md-3',
        'type' => 'number',
        'name' => "payment_data[cvv]",
        'div_attributes' => "style: (display: none;);",
        'div_class' => 'credit_card-fields',
        'class' => 'mask-credit-card-cvv',
        'label' => 'CVV',
        'Required' => true,
    ]);

    if (empty($plan_id))
    {
        $res.= input('selection_type', 'insert', [
            'name' => "payment_data[installments]",
            'div_attributes' => "style: (display: none;);",
            'div_class' => 'credit_card-fields',
            'class' => 'mask-credit-card-cvv',
            'label' => 'Parcelas',
            'Required' => true,
        ]);
    }

    if (!empty($custom_statement_descriptor) && $custom_statement_descriptor)
    {
        $res.= input('basic', 'insert', [
            'name' => "payment_data[statement_descriptor]",
            'label' => 'Escolha o nome em sua fatura',
            'div_attributes' => "style: (display: none;);",
            'div_class' => 'credit_card-fields',
            'class' => '',
            'Value' => $info['short_name'],
            'Alert' => 'Tenha sigilo personalizando o nome da compra que aparecerá no extrato do cartão de crédito.',
            'Required' => true,
        ]);
    }

    $res.= input('hidden', 'insert', [
        'name' => "payment_data[card_brand]",
        'div_class' => 'credit_card-fields',
    ]);

    $res.= input('hidden', 'insert', [
        'name' => "payment_data[token]",
        'div_class' => 'credit_card-fields',
    ]);

    return $res;
}

function mercadopago_pix_fields(string $type_form, array $Attr = [])
{
    $res = '';
    return $res;
}
