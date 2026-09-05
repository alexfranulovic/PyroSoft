<?php

/**
 * Dropdown-look-alike input for picking a saved card (tb_user_payment_methods)
 * -- built instead of a plain <select> because a native <select> can't
 * render render_card_brand_html()'s brand icon + masked number + expiration
 * markup for each option, only plain text.
 *
 * Behaves like any other form input: a hidden `<input type='hidden'>` (this
 * one's `name`/`value`) carries the actual value ('' = system default, no
 * card picked), synced to the visible button label on click -- both handled
 * by this input's own dedicated script (assets/scripts/user-payment-methods-input.js,
 * enqueued below), not any page-specific JS.
 *
 * `data-subscription-id` is OPTIONAL, passed through `attributes` (e.g.
 * `'attributes' => 'data-subscription-id:(123);'`) -- when present, the
 * dedicated script ALSO auto-saves the pick immediately via
 * update-subscription-field (plans-subscriptions/api.php), for callers with
 * no wrapping <form> to submit (see custom-listings/my-subscriptions.php).
 * Without it, this is a plain input: whatever <form> it lives in submits
 * the hidden field's value on its own (see the "Atualizar pagamento" modal,
 * custom-pages/my-subscriptions.php).
 */
function input_user_payment_methods(string $type_form, array $Attr = [])
{
    extract($Attr);
    $res = '';

    global $config, $seg;

    add_asset('footer', "<script src='" . plugin_path('/pyrosales/assets/scripts/user-payment-methods-input.js', 'url') . "' defer></script>");

    $cards        = (array) ($Options ?? []);
    $show_default = (bool) ($Attr['show_default'] ?? false);
    $default_key   = (string) get_system_info('default_payment_method');
    $default_label = $GLOBALS['payment_gateways'][$default_key]['label'] ?? '';

    $current_card = null;
    foreach ($cards as $card)
    {
        if ((int) $card['id'] === $Value) {
            $current_card = $card;
            break;
        }
    }

    $default_html = $show_default
        ? render_card_brand_html(['brand_name' => $default_label])
        : '';

    $toggle_label = $current_card
        ? render_card_brand_html($current_card)
        : ($default_html !== '' ? $default_html : "<span class='text-muted'>Selecione um cartão</span>");

    $items = '';

    if ($show_default)
    {
        $items .= "<li><a class='dropdown-item" . ($Value === 0 ? ' active' : '') . "' href='#' data-payment-method-option='' {$attributes}>" . $default_html . "</a></li>";
    }

    foreach ($cards as $card)
    {
        $card_id = (int) $card['id'];
        $items  .= "<li><a class='dropdown-item" . ($Value === $card_id ? ' active' : '') . "' href='#' data-payment-method-option='{$card_id}' {$attributes}>" . render_card_brand_html($card) . "</a></li>";
    }

    if (empty($items)) {
        $items = "<li><span class='dropdown-item-text text-muted'>Nenhum cartão salvo</span></li>";
    }

    $res.= input_label($Attr);

    $res.= "
    <div class='dropdown' data-payment-method-dropdown {$attributes}>
        <button {$disabled} class='btn btn-sm dropdown-toggle' type='button' data-bs-toggle='dropdown' aria-expanded='false' data-payment-method-toggle>
            {$toggle_label}
        </button>
        <ul class='dropdown-menu'>{$items}</ul>
        <input type='hidden' name='{$name}' value='{$Value}' id='{$input_id}' {$Required} data-payment-method-hidden>
    </div>";

    if (!$is_child) {
        $res = fieldset($type_form, $Attr, $res);
    }

    return $res;
}
