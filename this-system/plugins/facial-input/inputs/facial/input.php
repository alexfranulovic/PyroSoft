<?php

function input_facial(string $type_form, array $Attr = [])
{
    extract($Attr);
    $res = '';

    $Value = $_SESSION['FormData'][$name] ?? $Value;
    $Value = format_text($Value, 'decode');

    $textarea = "
    <textarea class='$class' $attributes name='$name' $text_editor placeholder='$Placeholder' id='$input_id' $Required $disabled $readonly>{$Value}</textarea>";

    $label = input_label($Attr);
    if ($style == 'floating')
    {
        $textarea = "
        <div class='form-floating'>
            $textarea
            $label
        </div>";
    } else {
        $res.= $label;
    }

    $res.= $textarea;

    if (!$is_child) {
        $res = fieldset($type_form, $Attr, $res);
    }

    return $res;
}
