<?php
if(!isset($seg)) exit;

load_input();


function load_input(string $which_feature = 'all', string $action = 'index', bool $debug = false)
{
    global $seg, $inputs, $config;

    $inputs_folder   = FEATURES_ABSOLUTE_PATH .'/inputs';
    $plugins_folder  = PLUGINS_ABSOLUTE_PATH;
    $activated_plugins = $config['activated_plugins'] ?? [];

    $allowed_actions = [
        'process',
        'input',
        'index',
        'view',
        'structure-settings',
    ];

    if (!in_array($action, $allowed_actions, true)) {
        echo 'Load input action does not exist. You can do: ' . implode(', ', $allowed_actions);
        return false;
    }

    /**
     * Helper interno: tenta carregar um input específico (by dir).
     */
    $try_include_input = static function (string $baseDir, string $inputName, string $action, bool $debug) {
        $dir        = rtrim($baseDir, '/\\') . DIRECTORY_SEPARATOR . basename($inputName);
        $input_file = $dir . DIRECTORY_SEPARATOR . $action . '.php';

        if (is_dir($dir) && file_exists($input_file)) {
            if ($debug) {
                var_dump($input_file);
            }
            include_once $input_file;
            return true;
        }

        return false;
    };

    // --- MODO 1: input específico ------------------------------------------
    if ($which_feature !== 'all') {

        // 1) Tenta na pasta base de inputs
        if ($try_include_input($inputs_folder, $which_feature, $action, $debug)) {
            return true;
        }

        // 2) Tenta em cada plugin ativo, na pasta /inputs
        if (!empty($activated_plugins) && is_dir($plugins_folder)) {
            foreach ($activated_plugins as $plugin_slug) {
                $plugin_inputs_folder = $plugins_folder . DIRECTORY_SEPARATOR . $plugin_slug . DIRECTORY_SEPARATOR . 'inputs';
                if (!is_dir($plugin_inputs_folder)) {
                    continue;
                }

                if ($try_include_input($plugin_inputs_folder, $which_feature, $action, $debug)) {
                    return true;
                }
            }
        }

        if ($debug) {
            echo "Input '{$which_feature}' não encontrado para action '{$action}' em:\n";
            echo "- {$inputs_folder}\n";
            if (!empty($activated_plugins)) {
                foreach ($activated_plugins as $plugin_slug) {
                    echo "- {$plugins_folder}/{$plugin_slug}/inputs\n";
                }
            }
        }

        return false;
    }

    // --- MODO 2: carregar TODOS (which_feature = 'all') ---------------------

    // 1) Inputs da pasta base
    $input_dirs = [];
    if (is_dir($inputs_folder)) {
        $input_dirs = array_filter(glob($inputs_folder . '/*'), 'is_dir');
    }

    // 2) Inputs de plugins ativos
    if (!empty($activated_plugins) && is_dir($plugins_folder)) {
        foreach ($activated_plugins as $plugin_slug) {
            $plugin_inputs_folder = $plugins_folder . DIRECTORY_SEPARATOR . $plugin_slug . DIRECTORY_SEPARATOR . 'inputs';
            if (!is_dir($plugin_inputs_folder)) {
                continue; // plugin sem pasta inputs, ignora
            }

            $plugin_input_dirs = array_filter(glob($plugin_inputs_folder . '/*'), 'is_dir');
            if (!empty($plugin_input_dirs)) {
                // Mergia mantendo tudo (se quiser, pode evitar duplicados com array_unique + realpath)
                $input_dirs = array_merge($input_dirs, $plugin_input_dirs);
            }
        }
    }

    // 3) Loop em todos os diretórios de inputs encontrados (core + plugins ativos)
    foreach ($input_dirs as $dir) {
        $input_file = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $action . '.php';

        if (file_exists($input_file)) {
            if ($debug) {
                var_dump($input_file);
            }
            include_once $input_file;
        }
    }

    return true;
}



function render_field_repeater_block(string $type_form = 'insert', array $data = [])
{
    $name           = (string) ($data['name'] ?? '');
    $label          = $data['label'] ?? '';
    $content        = format_text($data['content'] ?? '');
    $values         = $data['Value'] ?? [];
    $childs         = $data['childs'] ?? [];
    $storage_mode   = $data['storage_mode'] ?? 'json';
    $table          = $data['table'] ?? '';
    $table_cols     = [];
    $min_rows       = $data['min_rows'] ?? null;
    $max_rows       = $data['max_rows'] ?? null;
    $add_btn_title  = $data['add_btn_title'] ?? 'Adicionar linha';
    $is_orderable   = (!empty($data['is_orderable']) && $data['is_orderable']);

    // Helper to build the final name of input
    $__build_child_name = function (string $childName, string $repeaterName, $index, string $storageMode, ?string $table)
    {
        if (!preg_match('/^([^\[]+)\[(.+)$/', $childName, $m)) return $childName;

        $root = $m[1];         // ex. "tb_info"
        $rest = $m[2];         // ex. "name]" ou "meta][name]"

        $segments = array_values(array_filter(
            explode('[', str_replace(']', '', $repeaterName)),
            'strlen'
        ));

        $insert = '';
        foreach ($segments as $seg) { $insert .= '[' . $seg . ']'; }

        if ($storageMode === 'table' && $table) { return $table . '[' . $index . ']' . '[' . $rest; }

        return $root . $insert . '[' . $index . ']' . '[' . $rest;
    };


    if ($storage_mode == 'table')
    {
        $table_cols = show_columns($table);
        if (!is_array($values) || array_values($values) !== $values) {
            $values = [$values];
        }
    }


    if ($min_rows !== null && count($values) < $min_rows) {
        while (count($values) < $min_rows) $values[] = [];
    }


    $header = (!empty($label)) ? "<div class='header'><h4>$label</h4></div>" : "";
    $content = (!empty($content)) ? "<div class='body'>$content</div>" : "";

    $extra_attrs = '';
    if ($min_rows !== null) $extra_attrs .= " data-min-rows='$min_rows'";
    if ($max_rows !== null) $extra_attrs .= " data-max-rows='$max_rows'";


    $res = "
    <section class='col-12 field-repeater-container' data-repeater='". htmlspecialchars($name) ."' data-repeater-to='". htmlspecialchars($table) ."' $extra_attrs>
    <div class='field-repeater'>
        $header
        $content
        <table class='repeater-rows'>
        <tbody>";

    $input_counter = $x = 0;
    foreach ($values as $row_data)
    {
        $res .= "
        <tr data-index='$x'>
        <td class='key handle'>
            <div class='repeater-controls'>";

        if ($is_orderable) {
            $res .= "
                <button type='button' class='btn-move up'>". icon('fas fa-arrow-up') ."</button>
                <span class='index-number'>". ($x + 1) ."</span>
                <button type='button' class='btn-move down'>". icon('fas fa-arrow-down') ."</button>";
        } else {
            $res .= "<span class='index-number'>". ($x + 1) ."</span>";
        }

        $res .= "
            </div>
        </td>
        <td>
        <div class='row'>";

        // Put a ID input
        if (!empty($row_data['id']) && $storage_mode == 'table')
        {
            $input['name'] = "{$table}[{$x}][id]";
            $input['Value'] = $row_data['id'];

            $res .= input('hidden', $type_form, $input);
        }

        // Build the row inputs
        foreach ($childs as $y => $child)
        {
            $register_id = $row_data['id'] ?? null;
            $child_name = $child['og_name'] ?? '';
            $child['Value'] = $row_data[$child_name] ?? '';

            // Treat the new name.
            $child['name'] = $__build_child_name($child['name'], $name, $x, $storage_mode, $table);
            $child['input_id'] = "{$child['name']}-{$input_counter}";

            if ($type_form == 'update' && $child['type_field'] == 'upload')
            {
                $child['Src'] = ($storage_mode == 'table')
                    ? "{$child['Src']}/{$register_id}"
                    : "{$child['Src']}/{$y}";
            }

            $res .= input($child['type_field'] ?? '', $type_form, $child);
            $input_counter++;
        }

        $res .= "
        <div class='col-12'>
            <div class='btn-group remove'>
            <button type='button' class='btn btn-sm btn-danger remove-repeater-row dropdown-toggle' data-bs-toggle='dropdown'>". icon('fas fa-trash') ."</button>
            <ul class='dropdown-menu'>
                <p>Deseja excluir esta linha?</p>
                <li><button class='dropdown-item' delete-row-from-repeater type='button'>Excluir</button></li>
                <li><button class='dropdown-item' type='button'>Cancelar</button></li>
            </ul>
            </div>
        </div>
        </div>
        </td>
        </tr>";
        $x++;
    }

    // Template de linha invisível
    $res .= "
    <tr class='template-row' data-index='__index__' data-template style='display: none !important'>
    <td class='key handle'>
        <div class='repeater-controls'>";

    if ($is_orderable) {
        $res .= "
            <button type='button' class='btn-move up'>". icon('fas fa-arrow-up') ."</button>
            <span class='index-number'>__index_number__</span>
            <button type='button' class='btn-move down'>". icon('fas fa-arrow-down') ."</button>";
    } else {
        $res .= "<span class='index-number'>__index_number__</span>";
    }

    $res .= "
        </div>
    </td>
    <td>
    <div class='row'>";

    if (in_array('id', $table_cols) && $storage_mode == 'table')
    {
        $input['name'] = "{$table}[__index__][id]";
        $input['Value'] = null;

        $res .= input('hidden', $type_form, $input);
    }

    foreach ($childs as $child)
    {
        // Treat the new name.
        $child['name'] = $__build_child_name($child['name'], $name, '__index__', $storage_mode, $table);
        $child['input_id'] = "{$child['name']}-__index_input__";

        if (isset($child['Required']) && $child['Required']) {
            $child['data_required'] = true;
            unset($child['Required']);
        }

        /**
         *
         * Apply data-required
         *
         */
        if (!empty($child['Options']) && (is_array($child['Options']) || is_object($child['Options'])))
        {
            $options = [];

            foreach ($child['Options'] as $key => $option)
            {
                $opt = is_array($option) ? $option : (array) $option;

                if (!empty($opt['required'])) {
                    $opt['attributes'] = ($opt['attributes'] ?? '') . 'data-required:();';
                    unset($opt['required']);
                }

                $options[$key] = $opt;
            }

            $child['Options'] = $options;
        }


        $res .= input($child['type_field'] ?? '', $type_form, $child);
    }

    $res .= "
    <div class='col-12'>
        <div class='btn-group remove'>
        <button type='button' class='btn btn-sm btn-danger remove-repeater-row dropdown-toggle' data-bs-toggle='dropdown'>". icon('fas fa-trash') ."</button>
        <ul class='dropdown-menu'>
            <p>Deseja excluir esta linha?</p>
            <li><button class='dropdown-item' delete-row-from-repeater type='button'>Excluir</button></li>
            <li><button class='dropdown-item' type='button'>Cancelar</button></li>
        </ul>
        </div>
    </div>
    </div>
    </td>
    </tr>
    </tbody>
    </table>

    <div class='add-container'>
        <button type='button' class='btn btn-sm btn-primary add-repeater-row'>{$add_btn_title} ". icon('fas fa-plus') ."</button>
    </div>
    </div>
    </section>";

    return $res;
}





/**
 * Generates various types of input fields based on the specified type_input.
 *
 * @param string $type_input The type of input to generate. Possible values are:
 * @param string $type_form The type of form (e.g., "create", "update").
 * @param array $Attr An array of attributes for the input field.
 *
 * @return string The HTML markup for the generated input field.
 */
function input(string $type_input, string $type_form, $Attr = [])
{
    global
        $allowed_mime_types,
        $info,
        $inputs,
        $area,
        $config;

    /**
     * Special case: field_repeater is fully handled by its own renderer.
     */
    if ($type_input === 'field_repeater') {
        return render_field_repeater_block($type_form, $Attr);
    }

    $name = $Attr['name'] ?? null;


    /**
     * Default attributes (base config).
     */
    $defaults = [
        'id'                => null,
        'field_id'          => null,
        'Value'             => null,
        'style'             => $area['form']['input_style'] ?? 'floating',
        'Alert'             => null,
        'size'              => 'col-md-6',
        'weight'            => '',
        'div_class'         => '',
        'div_attributes'    => '',
        'label'             => '',
        'type'              => null,
        'class'             => '',
        'name'              => $name,
        'input_id'          => $name,
        'Placeholder'       => null,
        'is_array'          => false,
        'is_child'          => false,
        'is_option'         => false,
        'disabled'          => false,
        'readonly'          => false,
        'Required'          => false,
        'data_required'     => false,
        'text_editor'       => false,
        'Query'             => null,
        'options_resolver'  => '',
        'Options'           => [],
        'attachment'        => [],
        'attributes'        => '',
        'obs'               => '',
    ];


    // Merge user attributes over defaults
    $Attr = array_merge($defaults, $Attr);
    $Attr['field_id'] = $Attr['id'];

    // $Attr['style'] = 'normal';


    /**
     * Normalize flags / HTML attributes.
     */
    $Attr['is_array']       = !empty($Attr['is_array']);
    $Attr['disabled']       = !empty($Attr['disabled']) ? 'disabled' : '';
    $Attr['readonly']       = !empty($Attr['readonly']) ? 'readonly' : '';
    $Attr['Required']       = !empty($Attr['Required']) ? 'required' : '';
    $Attr['data_required']  = !empty($Attr['data_required']) ? 'data-required' : '';
    $Attr['text_editor']    = !empty($Attr['text_editor']) ? 'data-text-editor' : '';

    if (!empty($Attr['data_required'])) {
        $Attr['attributes'].= 'data-required:();';
    }


    // Extra HTML attributes
    $Attr['attributes'] = !empty($Attr['attributes'])
        ? parse_html_tag_attributes($Attr['attributes'])
        : '';


    $Attr['div_attributes'] = !empty($Attr['div_attributes'])
        ? parse_html_tag_attributes($Attr['div_attributes'])
        : '';


    // Normalize attachment (JSON | array | scalar)
    $Attr['attachment'] = (array) (is_json($Attr['attachment'])
        ? json_decode($Attr['attachment'], true)
        : $Attr['attachment']);


    // Compose final wrapper classes
    $Attr['div_class'] = trim("{$Attr['div_class']} {$Attr['weight']} {$Attr['size']}");


    /**
     * Allow inputs without name only for specific structural types.
     */
    if ($type_input != 'divider' || $type_input != 'shortcode')
    {
        $needs_name = $inputs[$type_input]['needs_name'] ?? false;
        if (empty($name) && $needs_name) {
            return "<p>You must apply a name to use this function.</p>";
        }
    }


    /**
     * Init the verification of Inputs
     */
    $res = "";
    load_input($type_input, 'input');

    $input_function = "input_{$type_input}";
    if (function_exists($input_function))
    {
        $input = $input_function($type_form, $Attr);
        if (!$Attr['is_child']) {
            $res.= $input;
        }
    }

    elseif (function_exists($input_function) && ($type_input != 'divider')) {
        return "<p>This input type ({$type_input}) does not exist.</p>";
    }

    return $res;
}


function fieldset(string $type_form, $Attr = [], string $input = '')
{
    extract($Attr);

    return "
    <fieldset $div_attributes class='$div_class'>
        $input
        <small>$Alert</small>
    </fieldset>";
}


function fieldset_group(string $type_form, $Attr = [])
{
    extract($Attr);

    $childs = !empty($Attr['childs'])
        ? $Attr['childs']
        : [$Attr];

    $res = "<fieldset $div_attributes class='$div_class'>";
    foreach ($childs as $input){
        $res.= input($type_input, $type_form, $input);
    }
    $res.= "</fieldset>";

    return $res;
}



function input_label(array $Attr = [])
{
    extract($Attr);


    // Required mark
    $Required_mark = ($Required || $data_required)
        ? '<span class="required">*</span>'
        : '';

    // Obs tooltip icon
    $obs = !empty($obs)
        ? "<span class='obs' title='$obs'>" .icon('fas fa-question-circle') ."</span>"
        : '';

    $for = '';
    $tag = 'span class="label"';
    if (!empty($input_id)) {
        $for = " for='{$input_id}'";
        $tag = 'label';
    }

    // Label
    return !empty($label)
        ? "<{$tag}{$for}>$obs $Required_mark".format_text($label)."</{$tag}>"
        : '';
}
