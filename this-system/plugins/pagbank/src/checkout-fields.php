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

    if ($allow_installments)
    {
        $res.= input('selection_type', 'insert', [
            'name' => "payment_data[installments]",
            'div_attributes' => "style: (display: none;);",
            'div_class' => 'credit_card-fields credit_card-user_payment_method',
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
            'div_class' => 'credit_card-fields credit_card-user_payment_method',
            'class' => '',
            'Value' => $info['short_name'],
            'Alert' => 'Tenha sigilo personalizando o nome da compra que aparecerá no extrato do cartão de crédito.',
            'data_required' => true,
        ]);
    }

    /**
     * Shows the accpeted brands
     */
    $res.= "<div class='credit_card-fields' style='display: none;'>";
    if (!empty($provider_method['accepted_brands']))
    {
        foreach ($provider_method['accepted_brands'] as $brand) {
            $res.= "<img src='". card_icon_url($brand) ."' alt='{$brand} é aceito' loading='lazy'>";
        }
    }
    $res.= "</div>";

    // $res.= input('address_form', 'insert', [
    //     'div_attributes' => "style: (display: none;);",
    //     'div_class' => 'credit_card-fields',
    //     'Value' => $Attr['address'] ?? [],
    //     'name' => 'payment_data[billing_address]'
    // ]);

    $res.= input('hidden', 'insert', [
        'name' => "payment_data[card_brand]",
        'div_class' => 'credit_card-fields',
    ]);

    $res.= input('hidden', 'insert', [
        'name' => "payment_data[token]",
        'div_class' => 'credit_card-fields',
    ]);

    $res.= input('hidden', 'insert', [
        'name' => "payment_data[threeds_id]",
        'div_class' => 'credit_card-fields',
    ]);

    return $res;
}

function pagbank_debit_card_fields(string $type_form, array $Attr = [])
{
    global $info, $current_user;

    extract($Attr);

    add_asset('footer', "<script src='https://assets.pagseguro.com.br/checkout-sdk-js/rc/dist/browser/pagseguro.min.js'></script>");
    add_asset('footer', "<script src='". plugin_path('/pagbank/assets/scripts/credit_card.js', 'url') ."' defer></script>");

    /**
     * Field keys here are prefixed (debit_...) and distinct from
     * pagbank_credit_card_fields()'s -- both blocks can be rendered on the
     * same checkout at once when both methods are active, and a duplicate
     * `name` shared between two inputs is fragile: anything that binds by
     * name alone (masking/formatting scripts, form serialization) always
     * targets the first match in the DOM regardless of which method is
     * actually selected. See pagbank_debit_card_process_payment(), which
     * reads these same debit_* keys (with a couple of safe fallbacks to
     * the generic key for pieces that come from a shared component, like
     * the saved-card CVV re-entry field).
     */
    $res = '';
    $res.= input('basic', 'insert', [
        'name' => "payment_data[debit_card_number]",
        'label' => 'Número do cartão',
        'div_attributes' => "style: (display: none;);",
        'div_class' => 'debit_card-fields',
        'class' => 'mask-credit-card-number pagbank-card-number',
        'attachment' => [
            'append' => '<img class="card-brand" alt="Bandeira" src="" style="height:20px;display:none">'
        ],
        'data_required' => true,
        'Alert' => 'Seu cartão será salvo de forma SEGURA para as renovações.'
    ]);

    $res.= input('basic', 'insert', [
        'name' => "payment_data[debit_name]",
        'label' => 'Nome impresso no cartão',
        'div_attributes' => "style: (display: none;);",
        'div_class' => 'debit_card-fields',
        'class' => '',
        'data_required' => true,
    ]);

    $res.= input('basic', 'insert', [
        'size' => 'col-6 col-md-3',
        'name' => "payment_data[debit_expiration]",
        'div_attributes' => "style: (display: none;);",
        'div_class' => 'debit_card-fields',
        'class' => 'mask-credit-card-date',
        'label' => 'Expira em (mm/aa)',
        'data_required' => true,
    ]);

    $res.= input('basic', 'insert', [
        'size' => 'col-6 col-md-3',
        'type' => 'number',
        'name' => "payment_data[debit_cvv]",
        'div_attributes' => "style: (display: none;);",
        'div_class' => 'debit_card-fields',
        'class' => 'mask-credit-card-cvv',
        'label' => 'CVV',
        'data_required' => true,
    ]);

    if (!empty($custom_statement_descriptor) && $custom_statement_descriptor)
    {
        $res.= input('basic', 'insert', [
            'name' => "payment_data[debit_statement_descriptor]",
            'label' => 'Escolha o nome em sua fatura',
            'div_attributes' => "style: (display: none;);",
            'div_class' => 'debit_card-fields debit_card-user_payment_method',
            'class' => '',
            'Value' => $info['short_name'],
            'Alert' => 'Tenha sigilo personalizando o nome da compra que aparecerá no extrato do cartão de débito.',
            'data_required' => true,
        ]);
    }

    $res.= input('address_form', 'insert', [
        'div_attributes' => "style: (display: none;);",
        'div_class' => 'debit_card-fields',
        'Value' => $Attr['address'] ?? ($current_user['address'] ?? []),
        'name' => 'payment_data[customer_address]',
        'data_required' => true,
        'required_complement' => true,
    ]);

    $res.= input('hidden', 'insert', [
        'name' => "payment_data[debit_card_brand]",
        'div_class' => 'debit_card-fields',
    ]);

    $res.= input('hidden', 'insert', [
        'name' => "payment_data[debit_token]",
        'div_class' => 'debit_card-fields',
    ]);

    $res.= input('hidden', 'insert', [
        'name' => "payment_data[debit_threeds_id]",
        'div_class' => 'debit_card-fields',
    ]);

    return $res;
}

function pagbank_pix_fields(string $type_form, array $Attr = [])
{
    $res = '<div class="pix-fields" style="display: none;"></div>';
    return $res;
}
