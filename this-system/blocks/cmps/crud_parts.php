<?php
if(!isset($seg)) exit;

/**
 * Generates a dropdown button with associated actions.
 *
 * Example usage:
 *   dropdown_button('Actions', [
 *      ['link' => 'edit.php?id=1', 'title' => 'update'],
 *      ['link' => 'delete.php?id=1', 'title' => 'Delete', 'attr' => 'data-confirm="Are you sure?"'],
 *      ['link' => 'view.php?id=1', 'title' => 'View']
 *   ]);
 *
 * @param string $name The name or label of the dropdown button.
 * @param array $hooks An array of action buttons to be displayed inside the dropdown.
 * @return void The function $res.=es the generated HTML content for the dropdown button and its associated actions.
 */
function dropdown_button(array $hook = [])
{
    if (!empty($hook))
    {
        $title    = $hook['title'] ?? '';
        $color    = $hook['color'] ?? '';
        $pre_icon = $hook['pre_icon'] ?? '';

        $res = "<div class='btn-group'>
        <button type='button' class='btn $color btn-sm dropdown-toggle' type='button' data-bs-toggle='dropdown' aria-expanded='false'>". icon($pre_icon) ." $title</button>
        <div class='dropdown-menu'>";
        foreach (array_filter($hook['options']) as $hook)
        {
            if (!empty($hook))
            {
                $attr = !empty($hook['attr']) ? parse_html_tag_attributes($hook['attr']) : null;
                $pre_icon = $hook['pre_icon'] ?? '';
                $title = $hook['title'] ?? null;

                $id = $hook['id'] ?? '';
                $type = $hook['type'] ?? '';
                $piece_id = $hook['piece_id'] ?? '';
                $url = $hook['url'] ?? '';
                if ($type == 'modal') {
                    $attr.= " open-crud-piece='$piece_id' item-id='$id'";
                }


                // ---- Render based on TYPE ----
                if ($type === 'button')
                {
                    $res.= "<button class='dropdown-item' {$attr} title='{$title}'>"
                        . icon($pre_icon)
                        . " <span>{$title}</span></button>";
                }

                else
                {
                    $res.= "<a class='dropdown-item' "
                        . check_pg_in_url($url)
                        . " {$attr} title='{$title}'>"
                        . icon($pre_icon)
                        . " <span>{$title}</span></a>";
                }
            }
        }
        $res .= '
        </div>
        </div>';

        return $res;
    }
}



function build_table_actions(array $data = [], $id = 0)
{
    $hooks_out = $hooks_in = [];
    if (!empty($data))
    {
        if (isset($data['order']['permission']) && $data['order']['permission'])
        $hooks_out[] = array_merge($data['order'], [
            'type' => 'url',
            'pre_icon' => 'fas fa-arrow-up',
            'url'  => rest_api_route_url("order-record?{$data['order']['url']}{$id}"),
            //'color' => 'btn-outline-info',
        ]);

        if (isset($data['duplicate']['permission']) && $data['duplicate']['permission'])
        $hooks_in[] = array_merge($data['duplicate'], [
            'type' => 'url',
            'pre_icon' => 'fas fa-copy',
            'title' => 'Duplicar',
            'url'  => rest_api_route_url("duplicate-record?{$data['duplicate']['url']}{$id}"),
            'attr'  => 'data-controller: (duplicate);',
            'color' => 'btn-outline-info',
        ]);

        if (isset($data['view']['permission']) && $data['view']['permission'])
        $hooks_in[] = array_merge($data['view'], [
            'type' => 'url',
            'pre_icon' => 'fas fa-eye',
            'title' => 'Visualizar',
            'url'  => ($data['view']['url']??'') .'?id='. $id,
            'color' => 'btn-outline-primary',
        ]);

        if (isset($data['edit']['permission']) && $data['edit']['permission'])
        $hooks_in[] = array_merge($data['edit'], [
            'type' => 'url',
            'pre_icon' => 'fas fa-pencil',
            'title' => 'Editar',
            'url'  => ($data['edit']['url']??'') .'?id='. $id,
            'color' => 'btn-outline-warning',
        ]);

        if (isset($data['delete']['permission']) && $data['delete']['permission'])
        $hooks_in[] = array_merge($data['delete'], [
            'type' => 'url',
            'pre_icon' => 'fas fa-trash',
            'title' => 'Apagar',
            'url'  => rest_api_route_url("delete-record?{$data['delete']['url']}{$id}"),
            'attr'  => 'data-controller: (delete);',
            'color' => 'btn-outline-danger',
        ]);

        $hooks_in = [
            'options' => $hooks_in,
            'title' => 'Ações',
            'color' => 'btn-st',
        ];

        return table_actions( $hooks_out, $hooks_in );
    }
}



/**
 * Generates a CRUD (Create, Read, Update, Delete) panel with associated action buttons.
 *
 * Example usage:
 *   crud_panel(
 *      [
 *          ['link' => 'create.php', 'title' => 'Create', 'color' => 'primary'],
 *          ['link' => 'update.php?id=1', 'title' => 'Update', 'color' => 'info'],
 *          ['link' => 'delete.php?id=1', 'title' => 'Delete', 'color' => 'danger', 'attr' => 'data-confirm="Are you sure?"']
 *      ],
 *      [
 *          ['link' => 'view.php?id=1', 'title' => 'View'],
 *          ['link' => 'download.php?id=1', 'title' => 'Download']
 *      ]
 *   );
 *
 * @param array $hooks_out An array of action buttons to be displayed outside the dropdown.
 * @param array $hooks_in An array of action buttons to be displayed inside the dropdown.
 * @return void The function $res.= is the generated HTML content for the CRUD panel and its associated action buttons.
 */
function crud_panel(array $Attr = null)
{
    $hooks_out = $Attr['hooks_out'] ?? [];
    $hooks_in  = $Attr['hooks_in'] ?? [];
    $name      = !empty($Attr['show_name']) ? ($Attr['form_name']??'') : '';

    if (!empty($name) || !empty($hooks_out) || !empty($hooks_in['options']))
    {
        $res = "
        <nav class='col-12'>
        <div class='crud-panel'>
        <h2>$name</h2>
        <div class='options'>
        <div class='buttons'>";
        foreach (array_filter($hooks_out) as $hook_out)
        {
            if (!empty($hook_out))
            {
                $attr = !empty($hook_out['attr'])
                    ? parse_html_tag_attributes($hook_out['attr'])
                    : null;

                $pre_icon = $hook_out['pre_icon'] ?? '';
                $color = $hook_out['color'] ?? 'outline-info';
                $title = $hook_out['title'] ?? null;

                $id = $hook_out['id'] ?? '';
                $type = $hook_out['type'] ?? '';
                $url = $hook_out['url'] ?? '';
                $piece_id = $hook_out['piece_id'] ?? '';
                if ($type == 'modal') {
                    $attr.= " open-crud-piece='$piece_id' item-id='$id'";
                }

                // Decide which HTML tag to use
                if ($type === 'button')
                {
                    $res.= "<button $attr title='{$title}' class='btn btn-{$color} btn-sm'>"
                        . icon($pre_icon)
                        . " <span>{$title}</span></button>";
                }

                else
                {
                    $res.= "<a " . check_pg_in_url($url) . " $attr title='{$title}' class='btn btn-{$color} btn-sm'>"
                        . icon($pre_icon)
                        . " <span>{$title}</span></a>";
                }
            }
        }
        if (!empty($hooks_in['options'])) $res.= dropdown_button($hooks_in);

        $res.= "
        </div>
        </div>
        </div>
        </nav>";

        return $res;
    }
}


/**
 * Display a form with specified input fields.
 *
 * This function generates and displays an HTML form based on the provided attributes and input fields.
 *
 * @param array $Attr An array containing attributes for generating the form.
 *
 * @return void
 */
function view(array $Attr = [])
{
    if (!empty($Attr))
    {
        /**
         * Put the CRUD panel panel
         */
        $div_attributes = !empty($Attr['div_attributes'])
            ? parse_html_tag_attributes($Attr['div_attributes'])
            : 'class="col-12 crud crud-view"';

        $res = "
        <section $div_attributes >
        <div class='card'>";
        $res .= !empty($Attr['crud_panel']['show_panel'])
            ? crud_panel($Attr['crud_panel'] ?? [])
            : '';
        $res .= "<div class='crud-body'>";

        /**
         * Start the Table
         */
        foreach ($Attr['content'] as $key => $table)
        {
            $rows = isset($table[0]['title']) && isset($table[0]['value'])
                ? [ $table ]
                : $table;

            foreach ($rows as $index => $row)
            {
                $res .= "<table>";
                $res .= '<thead>';
                $res .= !empty($key)
                    ? "<tr><th colspan='2'>{$key}" . (count($rows) > 1 ? ' #' . ($index + 1) : '') . "</th></tr>"
                    : '';
                $res .= '</thead>';
                $res .= '<tbody>';
                foreach ($row as $content) {
                    $res .= formatRowViewPage($content['title'], $content['value']);
                }
                $res .= '</tbody>';
                $res .= '</table>';
            }
        }

        $res .= '
        </div>
        </div>
        </section>';

        return $res;
    }
}

function form(array $Attr = [])
{
    if (empty($Attr['contents']['inputs'])) return '';

    $inputs        = $Attr['contents']['inputs'];
    $data          = $Attr['contents']['data'] ?? [];
    $type_form     = $Attr['type_crud'] ?? '';
    $form_method   = $Attr['form_method'] ?? 'post';
    $view_mode     = $Attr['view_mode'] ?? 'post';
    $register_id   = $Attr['register_id'] ?? null;

    $form_action    = $Attr['form_action'] ?? '';
    if (is_array($form_action))
    {
        $form_action_action = $form_action['action'] ?? '';
        $form_action_type   = $form_action['type'] ?? 'external';

        if ($form_action_type == 'page') {
            $form_action = get_url_page($form_action_action, 'full');
        }

        elseif ($form_action_type == 'api') {
            $form_action = rest_api_route_url($form_action_action);
        }

        else {
            $form_action = $form_action_action;
        }
    }

    $form_action = !empty($form_action)
        ? "action='$form_action'"
        : '';

    $without_reload = (isset($Attr['without_reload']) && $Attr['without_reload'] == true)
        ? 'data-send-without-reload'
        : '';

    $div_attributes = !empty($Attr['div_attributes'])
        ? parse_html_tag_attributes($Attr['div_attributes'])
        : '';

    $attr_form = !empty($Attr['attributes'])
        ? parse_html_tag_attributes($Attr['attributes'])
        : '';

    $Attr['form_action']         = $form_action;
    $Attr['attributes']           = $attr_form;
    $Attr['without_reload']     = $without_reload;


    /**
     * Choose form view.
     */
    if ($view_mode == 'default' || $view_mode == 'only_fields' || $view_mode == 'only_form') {
        $res = default_form($Attr);
    }

    elseif ($view_mode == 'steps_form') {
        $res = steps_form($Attr);
    }

    elseif ($view_mode == 'tabs_form') {
        $res = tabs_form($Attr);
    }

    return $res;
}


/**
 * Display a form with specified input fields.
 *
 * This function generates and displays an HTML form based on the provided attributes and input fields.
 *
 * @param array $Attr An array containing attributes for generating the form.
 *
 * @return void
 */
function default_form(array $Attr = [])
{
    if (empty($Attr['contents']['inputs'])) return null;

    $inputs         = $Attr['contents']['inputs'];
    $data           = $Attr['contents']['data'] ?? [];
    $form_method    = $Attr['form_method'] ?? '';
    $register_id    = $Attr['register_id'] ?? null;
    $view_mode      = $Attr['view_mode'] ?? 'default';
    $without_reload = $Attr['without_reload'] ?? '';
    $form_action    = $Attr['form_action'] ?? '';
    $attr_form      = $Attr['attributes'] ?? '';
    $form_settings  = $Attr['form_settings'] ?? [];

    $delay = !empty($form_settings['delay'])
        ? "data-form-delay='{$form_settings['delay']}'"
        : '';

    /**
     * Put the CRUD panel.
     */
    $div_attributes = !empty($Attr['div_attributes'])
        ? parse_html_tag_attributes($Attr['div_attributes'])
        : '';

    /**
     *
     * Prepare inputs.
     *
     */
    $res = $formatted_inputs = '';
    foreach ($inputs as $input)
    {
        $input = array_filter($input);
        $name = $input['name'] ?? null;

        if ($input['type_field'] == 'divider') {
            continue;
        }

        if (
            $input['type_field'] == 'hr' ||
            $input['type_field'] == 'shortcode' ||
            $input['type_field'] == 'break_line'
        )
        {
            $formatted_inputs.= $input['type_field']($input);
            continue;
        }

        //  Do some actions to Form edits
        if ($Attr['type_crud'] == 'update' && $input['type_field'] != 'submit_button')
        {
            // Treat the 'Src' for upload inputs.
            if ($input['type_field'] == 'upload')
            {

                if (!empty($input['register_id'])) {
                    $input['Src'] = "{$input['Src']}/{$input['register_id']}";
                }

                elseif (!empty($register_id)) {
                    $input['Src'] = "{$input['Src']}/{$register_id}";
                }

                elseif (!empty($_GET['id'])) {
                    $input['Src'] = "{$input['Src']}/{$_GET['id']}";
                }
            }

            $input['Value'] = $data[$name] ?? null;
        }

        $formatted_inputs.= input($input['type_field'], $Attr['type_crud'], $input);
    }

    /**
     * Only form
     */
    if ($view_mode == 'only_form')
    {
        return "
        <form $delay $without_reload method='$form_method' $form_action $attr_form>
        <div class='form-row'>
            $formatted_inputs
        </div>
        </form>";
    }


    /**
     * Default (as a card)
     */
    if ($view_mode != 'only_fields')
    {
        $res.= "
        <section class='col-12 crud crud-form' $div_attributes>
        <div class='card'>
            ". (!empty($Attr['crud_panel']['show_panel']) ? crud_panel($Attr['crud_panel'] ?? []) : '')."
            <div class='crud-body'>
            <form $delay $without_reload method='$form_method' $form_action $attr_form>
            <div class='form-row'>";
    }

    $res.= $formatted_inputs;

    if ($view_mode != 'only_fields')
    {
        $res.= '
        </div>
        </form>
        </div>
        </div>
        </section>';                      // End section

        unset($_SESSION['FormData']);       // Delete answered field values
    }

    return $res;
}


function steps_form(array $Attr = [])
{
    if (empty($Attr['contents']['inputs'])) return '';

    // Tabs (each item has 'title' & 'childs')
    $steps          = $Attr['contents']['inputs'];
    $data           = $Attr['contents']['data'] ?? [];
    $type_form      = $Attr['type_crud'] ?? '';
    $register_id    = $Attr['register_id'] ?? null;
    $form_method    = $Attr['form_method'] ?? '';
    $without_reload = $Attr['without_reload'] ?? '';
    $form_action    = $Attr['form_action'] ?? '';
    $attr_form      = $Attr['attributes'] ?? '';
    $form_settings  = $Attr['form_settings'] ?? [];
    $steps_form     = $form_settings['steps_form'] ?? [];
    $container      = $form_settings['container'] ?? false;

    $delay = !empty($form_settings['delay'])
        ? "data-form-delay='{$form_settings['delay']}'"
        : '';


    /**
     * Define wich's the actual step. Can be from URL or $Attr.
     */
    $actual_step    = !empty($Attr['form_settings']['step'])
        ? $Attr['form_settings']['step']
        : (!empty($_GET['step']) ? $_GET['step'] : 1);

    $key_step       = $actual_step-1;
    $total_steps  = count(
        array_filter($steps, function($i) {
            return isset($i['type_field']) && $i['type_field'] === 'divider';
        })
    );


    /**
     *
     * Ensure the wished step.
     *
     */
    if ($key_step < 0) {
        $actual_step    = 1;
        $key_step       = 0;
    }

    if ($key_step > ($total_steps-1)) {
        $actual_step    = $total_steps;
        $key_step       = $total_steps-1;
    }


    /**
     *
     * Build steps
     *
     */
    $end  = '';
    $steps_contents = $about_steps = [];
    $first = true;
    foreach ($steps as $key => $element)
    {
        $type_field = $element['type_field'] ?? null;
        $inputs = $element['childs'] ?? [];

        $title          = $element['title'] ?? null;
        $icon           = $element['icon'] ?? null;
        $description    = $element['description'] ?? null;
        $description = !empty($description)
            ? "<p>{$description}</p>"
            : '';


        /**
         * Execptions
         */
        if (empty($inputs))
        {
            if ($type_field != 'submit_button') {
                $end.= input($type_field, $type_form, $element);
            }
            continue;
        }


        /**
         * Print the inputs
         */
        $fields = '';
        foreach ($inputs as $input)
        {
            if (!is_array($input)) continue;
            $input = array_filter($input);

            $type_field = $input['type_field'] ?? null;
            if (!$type_field) continue;

            // campos “diretos” (funções simples)
            if (in_array($type_field, ['hr', 'shortcode', 'break_line'], true)) {
                $fields .= $type_field($input);
                continue;
            }

            // preparar valores no modo update
            if ($type_form === 'update' && $type_field !== 'submit_button')
            {
                if ($type_field === 'upload')
                {
                    if (!empty($input['register_id'])) $input['Src'] = "{$input['Src']}/{$input['register_id']}";
                    elseif (!empty($register_id))      $input['Src'] = "{$input['Src']}/{$register_id}";
                    elseif (!empty($_GET['id']))       $input['Src'] = "{$input['Src']}/{$_GET['id']}";
                }

                $name = $input['name'] ?? null;
                if ($name !== null && array_key_exists($name, $data)) {
                    $input['Value'] = $data[$name];
                }
            }

            // render do input
            $fields .= input($type_field, $type_form, $input);
        }


        $step_number = $key+1;
        $active = (($key_step == $key))
            ? 'active'
            : '';

        $fields = "
        <div class='carousel-item $active' step='$step_number'>
        <div class='carousel-caption form-row'>
            <div class='col-12 step-description'>
                <h2>{$title}</h2>
                {$description}
            </div>
            {$fields}
        </div>
        </div>";

        $about_steps[] = [
            'icon' => $icon,
            'title' => $title,
        ];

        $steps_contents[] = [
            'title' => $title,
            'description' => $description,
            'fields' => $fields,
            'active' => $first,
        ];
        $first = false;
    }


    // var_dump($steps_form);

    $attr_form.= isset($steps_form['one_step_at_a_time'])
        ? 'one-step-at-a-time="true"'
        : '';

    $step_status = '';
    if (isset($steps_form['save_between_steps']))
    {
        $attr_form.= ' save-between-steps="true"';

        $step_status = "
        <div class='saving-step' style='display: none;'>
        <div class='spinner-border' style='width: .825rem; height: .825rem;' role='status'>
          <span class='visually-hidden'>Loading...</span>
        </div>
        <i>Salvando...</i>
        </div>

        <div class='step-saved' style='display: none;'><i>✔ Salvo automaticamente</i></div>";
    }


    /**
     *
     * Build form
     *
     */
    $html = '';
    $html.= "
    <section class='module steps-form col-12'>";
    if($container) $html.= "<div class='container-lg'>";

        $html.= "<form $delay step-form class='carousel-fade step-form' {$without_reload} method='{$form_method}' {$form_action} {$attr_form}>";

        $html.= input('hidden', $type_form, [
            'name' => 'actual_step',
            'Value' => $actual_step
        ]);

        $html.= input('hidden', $type_form, [
            'name' => 'total_steps',
            'Value' => $total_steps
        ]);

        $html.= "<button class='btn btn-link top-prev-button' data-bs-target='.step-form' data-bs-slide='prev'>". icon('fas fa-arrow-left') ."</button>";

        /**
         *
         * Progress.
         *
         */
        if (!empty($steps_form['show_progess']))
        {
            $html.= block('progress', [
                'class' => 'col-12',
                'part' => $actual_step,
                'whole' => $total_steps,
                'height' => '5px',
                'show_steps' => !empty($steps_form['show_steps']),
                'variation' => $steps_form['progess_style'] ?? 'progress_bar',
                'color' => $steps_form['progress_color'] ?? 'success',
                'steps' => $about_steps,
                'step_active' => $actual_step,
            ]);
        }

        // Step saving status
        $html.= $step_status;

        /**
         *
         * All steps go through here.
         *
         */
        $html.= "<div class='carousel-inner'>";
        foreach ($steps_contents as $key => $step) {
            $html.= $step['fields'];
        }
        $html.= $end;
        $html.= "</div>";


        /**
         *
         * Controls.
         *
         */
        $html.= break_line();
        $html.= "
        <div class='row step-controls'>";

            $disabled = ($actual_step == 1) ? 'disabled' : '';
            $html.= "<div class='col-lg pt-3 back'>
                <button class='btn' type='button' $disabled data-bs-target='.step-form' data-bs-slide='prev'>Voltar</button>
            </div>";

            // Next
            $html.= input('submit_button', 'insert', [
                'div_class' => (($actual_step < $total_steps) ? '' : 'd-none'). " next",
                'input_id' => 'next-btn',
                'size' => "col-lg pt-3",
                'class' => "btn btn-st btn-block",
                'attributes' => 'data-bs-target:(.step-form); data-bs-slide:(next);',
                'Value' => 'Continuar '. icon('fas fa-arrow-right')
            ]);

            // Send
            $html.= input('submit_button', 'insert', [
                'div_class' => (($actual_step == $total_steps) ? '' : 'd-none'),
                'input_id' => 'send-btn',
                'size' => "col-lg pt-3",
                'class' => "btn btn-st btn-block",
                'attributes' => 'send-button:();',
                'Value' => !empty($steps_form['button_name_send'])
                    ? icon('fas fa-lock') ." ". $steps_form['button_name_send']
                    : 'Enviar'
            ]);

        $html.= "</div>";

        // Step saving status
        $html.= $step_status;


        /**
         *
         * Form end.
         *
         */
        $html.= "</form>";

    if($container) $html.= "</div>";
    $html.= "
    </section>";

    return $html;
}



/**
 * Display a form with specified input fields.
 *
 * This function generates and displays an HTML form based on the provided attributes and input fields.
 *
 * @param array $Attr An array containing attributes for generating the form.
 *
 * @return void
 */
function tabs_form(array $Attr = [])
{
    if (empty($Attr['contents']['inputs'])) return '';

    // Tabs (each item has 'title' & 'childs')
    $tabs          = $Attr['contents']['inputs'];
    $data          = $Attr['contents']['data'] ?? [];
    $type_form     = $Attr['type_crud'] ?? '';
    $register_id   = $Attr['register_id'] ?? null;
    $form_method    = $Attr['form_method'] ?? '';
    $without_reload = $Attr['without_reload'] ?? '';
    $form_action    = $Attr['form_action'] ?? '';
    $attr_form      = $Attr['attributes'] ?? '';
    $form_settings  = $Attr['form_settings'] ?? [];

    // monta os conteúdos das tabs para o componente navtabs()
    $end  = '';
    $nav_contents = [];
    $first = true;

    $delay = !empty($form_settings['delay'])
        ? "data-form-delay='{$form_settings['delay']}'"
        : '';

    foreach ($tabs as $element)
    {
        $title  = $element['title'] ?? 'Tab';
        $type_field = $element['type_field'] ?? null;
        $inputs = $element['childs'] ?? [];

        /**
         * Execptions
         */
        if (empty($inputs)) {
            $end.= input($type_field, $type_form, $element);
            continue;
        }

        /**
         * Print the inputs
         */
        $body_html = '';
        foreach ($inputs as $input)
        {
            if (!is_array($input)) continue;
            $input = array_filter($input);

            $type_field = $input['type_field'] ?? null;
            if (!$type_field) continue;

            // campos “diretos” (funções simples)
            if (in_array($type_field, ['hr', 'shortcode', 'break_line'], true)) {
                $body_html .= $type_field($input);
                continue;
            }

            // preparar valores no modo update
            if ($type_form === 'update' && $type_field !== 'submit_button')
            {
                if ($type_field === 'upload')
                {
                    if (!empty($input['register_id'])) $input['Src'] = "{$input['Src']}/{$input['register_id']}";
                    elseif (!empty($register_id))      $input['Src'] = "{$input['Src']}/{$register_id}";
                    elseif (!empty($_GET['id']))       $input['Src'] = "{$input['Src']}/{$_GET['id']}";
                }

                $name = $input['name'] ?? null;
                if ($name !== null && array_key_exists($name, $data)) {
                    $input['Value'] = $data[$name];
                }
            }

            // render do input
            $body_html .= input($type_field, $type_form, $input);
        }

        $nav_contents[] = [
            'title'  => $title,
            'body'   => "<div class='form-row'>{$body_html}</div>",
            'active' => $first,
        ];
        $first = false;
    }

    // classe opcional pra navtabs
    $navtabs_class = $Attr['navtabs_class'] ?? '';

    // output final
    $html  = "<form $delay {$without_reload} method='{$form_method}' {$form_action} {$attr_form}>";

    $html .= block('navtabs', [
        'variation' => 'navtabs_folder',
        'class'     => $navtabs_class,
        'contents'  => $nav_contents,
    ]);

    $html.= $end;
    $html .= '</form>';

    return $html;
}





/**
 * Display a table with specified headers and rows.
 *
 * This function generates and displays an HTML table based on the provided attributes.
 *
 * @param array $Attr An array containing attributes for generating the table.
 *
 * @return void
 */
function table(array $Attr = [])
{
    if (!empty($Attr))
    {
        $crud_id        = $Attr['crud_id'] ?? [];
        $data_table     = $Attr['data_table'] ?? false;
        $settings       = $Attr['settings'] ?? [];


        /**
         * Put the CRUD panel panel
         */
        $div_attributes = !empty($Attr['div_attributes'])
            ? parse_html_tag_attributes($Attr['div_attributes'])
            : 'class="col-12 crud crud-table"';


        $data_table_settings = '';
        if (in_array('data_table_async', $settings) && $data_table) {
            $data_table_settings = "data-table-async data-crud-id='{$crud_id}'";
        }

        elseif ($data_table) {
            $data_table_settings.= "data-table";
        }


        /**
         *
         * Start the table.
         *
         */
        $table = "<table $data_table_settings >";

        /**
         * Define the columns
         */
        $table.= "<thead>";
        $table.= "<tr>";
        foreach ($Attr['head'] as $head)
        {
            $table.= "<th>$head</th>";
        }
        $table.= "</tr>";
        $table.= "</thead>";        // End of Table head

        /**
         * Define the rows
         */
        $table.= '<tbody>';
        if (count($Attr['body']) == 0 AND empty($Attr['data_table']))
        {
            $total_head = count($Attr['head']);
            $table.= "<tr><td colspan='$total_head'>Nenhum registro encontrado.</td></tr>";
        }

        else
        {
            foreach ($Attr['body'] as $body)
            {
                $table.= "<tr>";
                foreach ($body as $content) $table.= "<td>". (!empty($content) && !is_array($content) ? $content : '-') ."</td>";
                $table.= "</tr>";
            }
        }
        $table.= '</tbody>';        // End of Table body
        $table.= '</table>';        // End Table


        /**
         *
         * Start the structure
         *
         */
        $res = "<section $div_attributes >";
        $res.= "<div class='card'>";
        $res.= !empty($Attr['crud_panel']['show_panel'])
            ? crud_panel($Attr['crud_panel'] ?? [])
            : '';
        $res.= "<div class='crud-body'>";

        $res.= "<div class='collapse bulk-edit'>Aqui virá um formulário de edição rápida de registros<hr></div>";

        // Show a loader if table is async
        if (in_array('data_table_async', $settings)) {
            $res.= '<div data-table-loader style="display: none;"><p>Carregando...</p></div>';
        }

        $res.= $table;

        $res.= '</div>';
        $res.= '</div>';          // End div
        $res.= '</section>';      // End section

        add_asset('footer', "<script src='".base_url."/dist/scripts/filesPreviewer.js' defer></script>");

        return $res;
    }
}


/**
 * Generates a table cell containing action buttons for a data row in a table.
 *
 * Example usage:
 *   table_actions(
 *      [
 *          ['link' => 'edit.php?id=1', 'title' => 'update', 'color' => 'primary'],
 *          ['link' => 'delete.php?id=1', 'title' => 'Delete', 'color' => 'danger', 'attr' => 'data-confirm="Are you sure?"']
 *      ],
 *      [
 *          ['link' => 'download.php?id=1', 'title' => 'Download'],
 *          ['link' => 'preview.php?id=1', 'title' => 'Preview']
 *      ]
 *   );
 *
 * @param array $hooks_out An array of action buttons to be displayed outside the dropdown.
 * @param array $hooks_in An array of action buttons to be displayed inside a dropdown.
 * @return void The function $res.=es the generated HTML content for the action buttons within the table cell.
 */
function table_actions(array $hooks_out = [], array $hooks_in = [])
{
    if (empty($hooks_out) && empty($hooks_in)) return '';

    $res = '<div class="table-actions">';

    foreach (array_filter($hooks_out) as $hook_out) {
        if (empty($hook_out)) {
            continue;
        }

        $attr      = !empty($hook_out['attr']) ? parse_html_tag_attributes($hook_out['attr']) : '';
        $pre_icon  = $hook_out['pre_icon'] ?? '';
        $title     = $hook_out['title'] ?? '';
        $color     = $hook_out['color'] ?? '';
        $url       = $hook_out['url'] ?? '';
        $id        = $hook_out['id'] ?? '';
        $type      = $hook_out['type'] ?? 'url'; // default: url
        $piece_id  = $hook_out['piece_id'] ?? '';

        // Modal-specific attributes
        if ($type === 'modal') {
            $attr .= " open-crud-piece='{$piece_id}' item-id='{$id}'";
        }

        // Render based on "type"
        if ($type === 'button')
        {
            $res.= "<button class='btn {$color} btn-sm' {$attr} title='{$title}'>"
                . icon($pre_icon)
                . " <span>{$title}</span></button>";
        }

        else
        {
            $res.= "<a " . check_pg_in_url($url)
                . " class='btn {$color} btn-sm' {$attr} title='{$title}'>"
                . icon($pre_icon)
                . " <span>{$title}</span></a>";
        }
    }

    if (!empty($hooks_in['options'])) {
        $res .= dropdown_button($hooks_in);
    }

    $res .= '</div>';

    return $res;
}



/**
 * Generates a formatted row for a view page.
 *
 * Example usage:
 *   formatRowViewPage('Name', 'John Doe');
 *   formatRowViewPage('Email', 'john@example.com', 0, 'N/A');
 *
 * @param string $title The title or label of the row.
 * @param mixed $content The content to be displayed in the row.
 * @param int $validation A validation flag to determine if the content should be displayed or not. Default is null.
 * @param string $placeholder The placeholder to be displayed if the content is empty or if validation fails. Default is null.
 * @return void The function $res.=es the generated HTML content for the formatted row.
 */
function formatRowViewPage($title, $content = null, $validation = null, $placeholder = null)
{
    if (!isset($placeholder)) $placeholder = '-';     // Verify and set an ordnary placeholder

    // Verify is empty and add a placeholder
    if (isset($validation)) {
      if ($validation == 0) $content = $placeholder;
    } else {
      if (empty($content)) $content = $placeholder;
    }

    if (is_array($content))
    {
        $content = "<pre>". json_encode($content, JSON_PRETTY_PRINT) ."</pre>";
    }

    return "
    <tr>
      <td class='title'>$title</td>
      <td class='content'>$content</td>
    </tr>";
}
