<?php

function input_selection_type(string $type_form, array $Attr = [])
{
    extract($Attr);
    $res = '';

    if ($is_option) return;

    $type                = $type ?? 'select';
    $variation           = $variation ?? 'original';
    // $Options             = $Options ?? [];
    // $options_resolver    = $options_resolver ?? '';

    // Treat by Options
    $by_options = is_json($Options)
        ? json_decode($Options)
        : string_to_array_selection_type($Options);
    $by_options = (array) $by_options;


    // Treat by Function
    $by_options_resolver = is_function_or_var($options_resolver);
    $by_options_resolver = is_array($by_options_resolver)
        ? $by_options_resolver
        : [];
    $by_options_resolver = normalize_type_select_input_options($by_options_resolver);


    // Treat by Query
    $by_query = !empty($Query)
        ? get_results($Query)
        : [];

    // Merge all the options
    $Options = array_filter(array_merge(
        $by_options,
        $by_query,
        $by_options_resolver)
    );

    if ($type == 'checkbox' || $type == 'radio' || $type == 'switch')
    {
        $type_class = $type;

        $type = ($type=='radio')
            ? 'radio'
            : 'checkbox';

        $Value = is_json($Value)
            ? json_decode($Value, true)
            : $Value;

        $isAssocValue = is_array($Value) && array_keys($Value) !== range(0, count($Value) - 1);

        // Selected set (legacy mode: array of values)
        $sel_values = $Value;
        if (is_array($Value) && !$isAssocValue)
        {
            foreach ($Value as $item)
            {
                if (!empty($item['value'])) break;
                $row['value'] = $item;
                $sel_values[] = $row;
            }
            $sel_values = array_column($sel_values, 'value');
        }


        if ($type != 'select' || $type != 'search') {
            unset($Attr['input_id']);
        }
        $res.= input_label($Attr);

        /**
         *
         * Open the group.
         *
         */
        $group_attr = "";

        if($variation == 'btn-group')
        $group_attr = "role='group' data-bs-toggle='buttons'";

        if($variation == 'balloons')
        $group_attr = "class='btn-balloons' role='group' data-bs-toggle='buttons'";


        $res.= "<div data-options $group_attr>";
        $Counter = 0;
        foreach ($Options as $option)
        {
            $option = (array) $option;

            $option_value = $option['value'] ?? '';
            if ($option_value === '' && $option_value !== 0 && $option_value !== '0') continue;

            // Priority: checked explicitly in the option
            $checked = array_key_exists('checked', $option)
                ? (bool) $option['checked']
                : null;

            $name      = !empty($option['name']) ? $option['name'] : $name;
            $option_id = "$name-{$option_value}";

            $attributes = !empty($option['attributes'])
                ? parse_html_tag_attributes($option['attributes'])
                : '';

            $display = !empty($option['display'])
                ? format_text($option['display'])
                : $option_value;

            if ($checked === true) {
                $check = 'checked';
            }

            else
            {
                if ($isAssocValue)
                {
                    // 1) try the og_name of the option
                    $flagKey = $option['og_name'] ?? null;

                    // 2) if there is none, use the last part of the option name
                    if ($flagKey === null || $flagKey === '')
                    {
                        // extract last part: e.g. tb_info[...][has_upper] -> has_upper
                        preg_match_all('/([^\[\]]+)/', $name, $mm);
                        $parts   = $mm[1] ?? [];
                        $flagKey = $parts ? end($parts) : null;
                    }

                    // 3) mark checked when Value[flagKey] is truthy
                    $check = ($flagKey !== null && !empty($Value[$flagKey])) ? 'checked' : '';
                }

                else
                {
                    // Legacy: list of selected values
                    $check = is_array($sel_values)
                        ? (in_array((string)$option_value, array_map('strval', $sel_values), true) ? 'checked' : '')
                        : (
                            ((isset($_SESSION['FormData'][$name]) && $_SESSION['FormData'][$name] == $Value) || ($option_value == $Value))
                                ? 'checked'
                                : ''
                        );
                }
            }

            if (!empty($option['disabled'])) {
                $attributes.= ' disabled';
            }

            if (!empty($option['required'])) {
                $attributes.= ' required';
            }

            if ($variation == 'original' OR $variation == 'inline')
            {
                $inline = ($variation=='inline')
                    ? 'form-check-inline'
                    : '';

                $res.= "
                <div class='form-check $inline form-{$type_class}'>
                <input $attributes type='$type' name='$name' id='$option_id' value='{$option_value}' $check>
                <label for='$option_id'>$display</label>
                </div>";
            }

            elseif ($variation == 'balloons' OR $variation == 'btn-group')
            {
                $res.= "
                <input $attributes type='$type' name='$name' id='$option_id' value='{$option_value}' $check>
                <label for='$option_id'>$display</label>";
            }

            elseif ($variation == 'block')
            {
                $highlight = !empty($option['highlight'])
                    ? "<div class='highlight'><span>{$option['highlight']}</span></div>"
                    : '';
                $description = !empty($option['description'])
                    ? "<p>{$option['description']}</p>"
                    : '';
                $small = !empty($option['small'])
                    ? "<small>{$option['small']}</small>"
                    : '';

                $res.= "
                <label class='block' for='$option_id'>
                {$highlight}
                <div class='content'>
                <input $attributes type='$type' name='$name' id='$option_id' value='{$option_value}' $check>
                <span class='description'>
                    <span class='title'>$display</span>
                    {$description}
                    {$small}
                </span>
                </div>
                </label>";
            }

            $Counter++;
        }

        // Close the group
        $res.= "</div>";
    }

    elseif ($type == 'select' OR $type == 'search')
    {
        $attributes.= ($type=='search')
            ? (($variation == 'multiple') ? ' data-search-multiple' : ' data-search')
            : '';

        if ($variation == 'multiple') {
            $attributes.= " multiple='multiple'";
        }

        $select = "
        <select $attributes name='$name' id='$input_id' $Required $disabled $readonly>
        <option value=''>Selecione</option>
            ". build_select_options($Options, $name, $Value) ."
        </select>";

        /**
         * Add floating label.
         */
        $label = input_label($Attr);
        if ($style == 'floating')
        {
            $select = "
            <div class='form-floating'>
                $select
                $label
            </div>";
        } else {
            $res.= $label;
        }

        $res.= $select;
    }

    if (!$is_child) {
        $res = fieldset($type_form, $Attr, $res);
    }

    return $res;
}


function normalize_type_select_input_options(array $options = []): array
{
    $res = [];

    if (isset($options[0]['value'])) {
        $res = $options;
    }

    else
    {
        foreach($options as $key => $option)
        {
            $res[] = [
                'value' => $key,
                'display' => $option
            ];
        }
    }

    return $res;
}


function build_select_options($options, $name = '', $Value = null)
{
    $res = '';

    foreach ($options as $key => $option)
    {
        $sel        = '';
        $option     = (array) $option;
        $attributes = $option['attributes'] ?? '';

        if (is_numeric($key) && is_array($option))
        {
            $option_value = $option['value'] ?? '';
            $disabled     = $option['disabled'] ?? null;
            $option['checked'] = $option['checked'] ?? null;

            $display = $option['display'] ?? $option_value;

            if (is_array($display)) {
                $label = $option_value ?: (string)$key;
                $res .= "<optgroup label='". htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ."'>"
                      . build_select_options($display, $name, $Value)
                      . "</optgroup>";
                continue;
            }

            if (
                isset($_SESSION['FormData'][$name]) &&
                $_SESSION['FormData'][$name] == $Value
            ) { $sel = 'selected'; }
            elseif ($option_value == $Value)  $sel = 'selected';
            elseif ($option['checked'] != null) $sel = 'selected';

            $res .= "<option {$attributes} value='". htmlspecialchars($option_value, ENT_QUOTES, 'UTF-8') ."' {$sel} {$disabled}>"
                  . htmlspecialchars((string)$display, ENT_QUOTES, 'UTF-8')
                  . "</option>";
        }
        elseif (is_array($option))
        {
            $res .= "<optgroup label='". htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8') ."'>"
                  . build_select_options($option, $name, $Value)
                  . "</optgroup>";
        }
    }

    return $res;
}



/**
 * Converts a string or array representation of type selects to an array format.
 *
 * @param mixed $options The string or array value to be converted.
 * @return array The converted array.
 */
function string_to_array_selection_type($options = null)
{
    $new_array = [];
    if (is_string($options))
    {
        $rows = array_filter( explode(";", $options) );
        if ($rows != NULL)
        {
            foreach ( $rows as $array )
            {
                $itens = explode( "||", $array );
                $new_array[] = [
                    'attributes' => !empty($itens[0]) ? trim($itens[0]) : '',
                    'value'      => !empty($itens[1]) ? trim($itens[1]) : '',
                    'display'    => !empty($itens[2]) ? trim($itens[2]) : '',
                    'checked'    => !empty($itens[3]) ? trim($itens[3]) : '',
                ];
            }
        }
    }

    elseif (is_array($options)) $new_array = $options;

    return $new_array;
}
