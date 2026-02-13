<?php

function input_button(string $type_form, array $Attr = [])
{
    extract($Attr);
    $res = '';

    global $config;

    $Value = $Value ?? 'Enviar';
    $class = $class ?? 'btn-primary';

    $block = (!empty($block) && $block)
        ? 'block'
        : '';

    $btn_block = ($block == 'block') ? 'btn-block' : '';

    $res = "
    <button class='btn $class $btn_block' $attributes type='button'>
    <span>$Value</span>
    </button>";

    if (!$is_child) {
        $res = fieldset($type_form, $Attr, $res);
    }

    return $res;
}
