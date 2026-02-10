<?php

function input_range(string $type_form, array $Attr = [])
{
    extract($Attr);

    $Value = format_text(($_SESSION['FormData'][$name] ?? ($Value ?? null)), 'decode');

    // Configuração de limites
    $range_min  = isset($range_min)  ? (float) $range_min  : 0.0;
    $range_max  = isset($range_max)  ? (float) $range_max  : 100.0;
    $range_step = isset($range_step) ? (float) $range_step : 1.0;

    $prefix = $prefix ?? '';

    // Detecta se é range "entre" (dois valores) ou simples
    $is_between = false;
    $val_min = $range_min;
    $val_max = $range_max;

    if (is_array($Value))
    {
        if (isset($Value['min'])) {
            $val_min = (float) $Value['min'];
        }
        if (isset($Value['max'])) {
            $val_max = (float) $Value['max'];
        }
        $is_between = isset($Value['min']) || isset($Value['max']);
    }

    elseif (is_string($Value) && strpos($Value, ',') !== false)
    {
        [$v1, $v2] = array_map('trim', explode(',', $Value, 2));
        $val_min   = (float) $v1;
        $val_max   = (float) $v2;
        $is_between = true;
    }

    elseif ($Value !== null && $Value !== '') {
        $val_min = (float) $Value;
    }

    // Clamp nos limites
    $val_min = max($range_min, min($range_max, $val_min));
    $val_max = max($range_min, min($range_max, $val_max));
    if ($is_between && $val_min > $val_max) {
        [$val_min, $val_max] = [$val_max, $val_min];
    }


    $res = '';
    unset($Attr['input_id']);
    $res .= input_label($Attr);

    // Wrapper do componente
    $res .= "<div class='range-wrapper $class'>";

    if ($range_mode == 'between')
    {
        $res .= "
            <input
                type='hidden'
                class='input-range'
                name='{$name}[min]'
                id='{$name}[min]'
                data-range-min='$range_min'
                data-range-max='$range_max'
                data-range-step='$range_step'
                value='$val_min'
                $Required $disabled $readonly $attributes
            >

            <input
                type='hidden'
                class='input-range-max'
                name='{$name}[max]'
                id='{$name}[max]'
                value='$val_max'
                $Required $disabled $readonly
            >

            <div class='range'>
                <div class='range-track'>
                    <div class='range-selection'></div>
                </div>
                <div class='min-range-handle'></div>
                <div class='max-range-handle'></div>
            </div>
        ";
    }

    // ---------- Simple Range ----------
    else
    {
        $res .= "
            <input
                type='hidden'
                class='input-range'
                name='$name'
                id='$input_id'
                data-range-min='$range_min'
                data-range-max='$range_max'
                data-range-step='$range_step'
                value='$val_min'
                $Required $disabled $readonly $attributes
            >

            <div class='range'>
                <div class='range-track'>
                    <div class='range-selection'></div>
                </div>
                <div class='min-range-handle'></div>
            </div>
        ";
    }

    $res .= "</div>";

    // Numbers (opcional)
    if (!empty($show_numbers))
    {
        if ($range_mode == 'between') {
            $res .= "
                <div class='numbers'>
                    <p>$prefix <span>$val_min</span></p>
                    <p>$prefix <span>$val_max</span></p>
                </div>
            ";
        } else {
            $res .= "
                <div class='numbers'>
                    <p>$prefix <span>$val_min</span></p>
                </div>
            ";
        }
    }

    if (empty($is_child)) {
        $res = fieldset($type_form, $Attr, $res);
    }

    add_asset('footer', "<script src='".base_url."/dist/scripts/rangeInput.js' defer></script>");

    return $res;
}
