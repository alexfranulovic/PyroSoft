<?php
if (!isset($seg)) exit;

function pagbank_credit_card_fields(string $type_form, array $Attr = [])
{
    global $info;

    extract($Attr);

    add_asset('footer', "<script src='https://assets.pagseguro.com.br/checkout-sdk-js/rc/dist/browser/pagseguro.min.js'></script>");
    add_asset('footer', "<script src='". plugin_path('/pagbank/assets/scripts/credit_card.js', 'url') ."' defer></script>");

    $res = '';
    $res.= input('basic', 'insert', [
        'name' => "payment_data[card_number]",
        'label' => 'Número do cartão',
        'div_attributes' => "style: (display: none;);",
        'div_class' => 'credit_card-fields',
        'class' => 'mask-credit-card-number pagbank-card-number',
        'attachment' => [
            'append' => '<img class="card-brand" alt="Bandeira" src="" style="height:20px;display:none">'
        ],
        'data_required' => true,
        'Alert' => 'Seu cartão será salvo de forma SEGURA para as renovações.'
    ]);

    $res.= input('basic', 'insert', [
        'name' => "payment_data[name]",
        'label' => 'Nome impresso no cartão',
        'div_attributes' => "style: (display: none;);",
        'div_class' => 'credit_card-fields',
        'class' => '',
        'data_required' => true,
    ]);

    $res.= input('basic', 'insert', [
        'size' => 'col-6 col-md-3',
        'name' => "payment_data[expiration]",
        'div_attributes' => "style: (display: none;);",
        'div_class' => 'credit_card-fields',
        'class' => 'mask-credit-card-date',
        'label' => 'Expira em (mm/aa)',
        'data_required' => true,
    ]);

    $res.= input('basic', 'insert', [
        'size' => 'col-6 col-md-3',
        'type' => 'number',
        'name' => "payment_data[cvv]",
        'div_attributes' => "style: (display: none;);",
        'div_class' => 'credit_card-fields',
        'class' => 'mask-credit-card-cvv',
        'label' => 'CVV',
        'data_required' => true,
    ]);

    if (empty($plan_id))
    {
        $res.= input('selection_type', 'insert', [
            'name' => "payment_data[installments]",
            'div_attributes' => "style: (display: none;);",
            'div_class' => 'credit_card-fields',
            'class' => 'mask-credit-card-cvv',
            'label' => 'Parcelas',
            'data_required' => true,
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
            'data_required' => true,
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

function pagbank_pix_fields(string $type_form, array $Attr = [])
{
    $res = '<div class="pix-fields" style="display: none;">teste</div>';
    return $res;
}
