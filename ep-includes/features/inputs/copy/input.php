<?php

function input_copy(string $type_form, array $Attr = [])
{
    extract($Attr);

    $res = '';
    $type = $type ?? 'text';

    $Value = $_SESSION['FormData'][$name] ?? $Value;
    $Value = format_text($Value, 'decode');

    $input = "<input class='$class' type='$type' $attributes placeholder='$Placeholder' name='$name' id='$input_id' value=\"{$Value}\" disabled readonly>";

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

    $res.= '<div class="input-group">';
    $res.= $input;
    $res.= "
    <button type='button' title='Copy' copy-content class='btn btn-st'>
        ".icon('fas fa-copy')."
    </button>";
    if ($type == 'password')
    {
        $res.= "
        <button type='button' title='Mostrar senha' show-hide-password class='btn btn-st'>
            ".icon('fas fa-eye')."
        </button>";
    }
    $res.= "</div>";

    if (!$is_child) {
        $res = fieldset($type_form, $Attr, $res);
    }

    return $res;
}