<?php

function input_basic(string $type_form, array $Attr = [])
{
    extract($Attr);
    $res = '';

    $type = $type ?? 'text';
    $Value = format_text( ($_SESSION['FormData'][$name] ?? $Value), 'decode');

    /**
     * Type "price" is custom, then we have to turn into "text"
     */
    if ($type == 'price')
    {
        $attributes.= ' data-mask-money';
        $type = 'text';
    }

    if ($type == 'password') {
        $Value = '';
    }

    $input = "<input class='$class' type='$type' $attributes placeholder='$Placeholder' name='$name' id='$input_id' value=\"{$Value}\" $Required $disabled $readonly>";

    /**
     * Add floating label.
     */
    $label = input_label($Attr);
    if ($style == 'floating')
    {
        $input = "
        <div class='form-floating'>
            $input
            $label
        </div>";
    } else {
        $res.= $label;
    }

    /**
     * Attachment content
     */
    if (!empty($attachment['prepend']) OR !empty($attachment['append']) OR $type == 'password')
    {
        $res.= '<div class="input-group">';

        // Prepend
        if (!empty($attachment['prepend'])) {
            $res.= "<span class='input-group-text'>{$attachment['prepend']}</span>";
        }

        $res.= $input;

        if ($type == 'password')
        {
            $res.= "
            <button type='button' title='Mostrar senha' show-hide-password class='btn btn-st'>
                ".icon('fas fa-eye')."
            </button>";
        }

        // Append
        elseif (!empty($attachment['append'])) {
            $res.= "<span class='input-group-text'>{$attachment['append']}</span>";
        }

        $res.= "</div>";
    }

    else {
        $res.= $input;
    }

    if (!$is_child) {
        $res = fieldset($type_form, $Attr, $res);
    }

    return $res;
}
