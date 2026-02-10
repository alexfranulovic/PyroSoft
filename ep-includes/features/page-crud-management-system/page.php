<?php
if(!isset($seg)) exit;

/*
 * Page functions in second.
 *
 * It is not recommended to you updating or custom this file.
 *
 */

/**
 * Get form setting options.
 *
 * @return array - An array of form setting options.
 */
function common_inputs_for_page(string $type_form, string $selector, $data = null, $counter = 1)
{
    if ($selector == 'id')
    return input(
        'hidden',
        $type_form,
        [
            'name' => "Modules[$counter][id]",
            'Value' => $data,
            'Required' => true,
        ]
    );

    if ($selector == 'TypeModule')
    return input('hidden', $type_form,
        [
            'name' => "Modules[$counter][TypeModule]",
            'input_id' => "TypeModule-$counter",
            'Value' => $data,
            'Required' => true
        ]
    );

    if ($selector == 'image_folder')
    return input('hidden', $type_form,
        [
            'name' => "Modules[$counter][image_folder]",
            'input_id' => "image_folder-$counter",
            'Value' => $data,
            'Required' => true
        ]
    );

    if ($selector == 'section_attributes')
    return input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Atributos da Section',
            'Placeholder' => 'data, ON events, etc.',
            'name' => "Modules[$counter][section_attributes]",
            'input_id' => "section_attributes-$counter",
            'Value' => $data,
        ]
    );

    if ($selector == 'class')
    return input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Classes da Section',
            'Placeholder' => 'data, ON events, etc.',
            'name' => "Modules[$counter][class]",
            'input_id' => "class-$counter",
            'Value' => $data,
        ]
    );

    if ($selector == 'title')
    return input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Título',
            'name' => "Modules[$counter][title]",
            'input_id' => "title-$counter",
            'Value' => $data,
        ]
    );

    if ($selector == 'subtitle')
    return input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Subtítulo',
            'name' => "Modules[$counter][subtitle]",
            'input_id' => "subtitle-$counter",
            'Value' => $data,
        ]
    );

    if ($selector == 'delay')
    return input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Delay',
            'name' => "Modules[$counter][delay]",
            'input_id' => "delay-$counter",
            'Value' => $data,
        ]
    );

    if ($selector == 'contents')
    return input('textarea', $type_form,
        [
            'size' => 'col-12',
            'label' => 'Conteúdo',
            'attributes' => 'rows:(4);',
            'name' => "Modules[$counter][contents]",
            'input_id' => "contents-$counter",
            'Value' => $data,
            'text_editor' => 1,
        ]
    );

    if ($selector == 'align')
    return input('selection_type', $type_form,
        [
           'size' => 'col-md-6 col-lg-4',
           'label' => 'Alinhamento de texto',
           'name' => "Modules[$counter][align]",
           'input_id' => "align-$counter",
           'Options' => "
                1|| text-left|| Esquerda;
                2|| text-center|| Centro;
                3|| text-right|| Direita;",
           'Value' => $data,
       ]
    );

    if ($selector == 'subscribers_only')
    return function_exists('signatures_version') ? input('selection_type', $type_form,
        [
            'type' => 'switch',
            'size' => 'col-md-6 col-lg-4',
            'name' => "Modules[$counter][subscribers_only]",
            'input_id' => "subscribers_only-$counter",
            'Options' => "2|| 1|| Apenas para assinantes;",
            'Value' => $data,
        ]
    ) : null;

    if ($selector == 'animations')
    return input('selection_type', $type_form,
        [
            'type' => 'switch',
            'size' => 'col-md-6 col-lg-4',
            'name' => "Modules[$counter][animations]",
            'input_id' => "animations-$counter",
            'Options' => "2|| 1|| Animações;",
            'Value' => $data,
        ]
    );

    if ($selector == 'container')
    return input('selection_type', $type_form,
        [
            'type' => 'switch',
            'size' => 'col-md-6 col-lg-4',
            'name' => "Modules[$counter][container]",
            'input_id' => "container-$counter",
            'Options' => "1|| 1|| Container;",
            'Value' => $data,
        ]
    );

    if ($selector == 'commands')
    return input('selection_type', $type_form,
        [
            'type' => 'switch',
            'size' => 'col-md-6 col-lg-4',
            'name' => "Modules[$counter][commands]",
            'input_id' => "commands-$counter",
            'Options' => "3|| 1|| Comandos;",
            'Value' => $data,
        ]
    );

    if ($selector == 'status_id')
    return input('status_selector', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'function_proccess' => 'general_status',
            'name' => "Modules[$counter][status_id]",
            'input_id' => "status_id-$counter",
            'Value' => $data,
            'Required' => true
        ]
    );

    if ($selector == 'image')
    {
        $data = (isset($data['value']) AND isset($data['src'])) ? $data : [ 'value' => null, 'src' => null ];
        return input('upload', $type_form,
            [
                'type' => 'images',
                'size' => 'col-md-6 col-lg-4',
                'label' => 'Imagem',
                'attributes' => 'accept:(image/*);',
                'name' => "Modules[$counter][image]",
                'input_id' => "image-$counter",
                'Value' => $data['value'],
                'Src' => $data['src'],
                'Required' => true,
            ]
        );
    }
}



/**
 * Get form setting options.
 *
 * @return array - An array of form setting options.
 */
function common_inputs_for_page_block(string $type_form, string $selector, $data = null, $counter = 1, $module_counter = 1)
{
    if ($selector == 'image_folder')
    return input('hidden', $type_form,
        [
            'name' => "Modules[$counter][contents][$module_counter][image_folder]",
            'input_id' => "image_folder-$counter-$module_counter",
            'Value' => $data,
            'Required' => true
        ]
    );

    if ($selector == 'title')
    return input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Título do item',
            'Placeholder' => 'Ex: Pergunta',
            'name' => "Modules[$counter][contents][$module_counter][title]",
            'input_id' => "title-$counter-$module_counter",
            'Value' => $data,
            //'Required' => true
        ]
    );

    if ($selector == 'subtitle')
    return input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Cargo ou origem do autor',
            'Placeholder' => 'Ex: CEO da SpaceX',
            'name' => "Modules[$counter][contents][$module_counter][subtitle]",
            'input_id' => "subtitle-$counter-$module_counter",
            'Value' => $data,
            'Required' => true
        ]
    );

    if ($selector == 'content')
    return input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Conteúdo do item',
            'Placeholder' => 'Ex: Resposta',
            'name' => "Modules[$counter][contents][$module_counter][content]",
            'input_id' => "content-$counter-$module_counter",
            'Value' => $data,
        ]
    );

    if ($selector == 'icon')
    return input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Ícone',
            'Placeholder' => 'Ex: Ícone que representa melhor o serviço',
            'name' => "Modules[$counter][contents][$module_counter][icon]",
            'input_id' => "icon-$counter-$module_counter",
            'Value' => $data,
        ]
    );

    if ($selector == 'url')
    return input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Link do botão',
            'type' => 'url',
            'Placeholder' => 'seulink.com',
            'name' => "Modules[$counter][contents][$module_counter][url]",
            'input_id' => "url-$counter-$module_counter",
            'Value' => $data,
        ]
    );

    if ($selector == 'image')
    {
        $data = (isset($data['value']) AND isset($data['src'])) ? $data : [ 'value' => null, 'src' => null ];
        return  input('upload', $type_form,
            [
                'type' => 'images',
                'size' => 'col-md-6 col-lg-4',
                'label' => 'Imagem',
                'attributes' => 'accept:(image/*);',
                'name' => "Modules[$counter][contents][$module_counter][image]",
                'input_id' => "image-$counter-$module_counter",
                'Value' => $data['value'],
                'Src' => $data['src'],
                // 'Required' => true,
            ]
        );
    }
}


/**
 * Generate HTML form inputs for configuring 'inputs_for_vertical_text'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'hidden'.
 */
function inputs_for_vertical_text(string $type_form, $counter, array $data = [])
{
    $TypeModule = 'vertical_text';

    $res = '<div class="form-row">';
    $res.= common_inputs_for_page($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'TypeModule', $TypeModule, $counter);
    $res.= input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Texto da esquerda',
            'Placeholder' => 'Ex: Nome da empresa',
            'name' => "Modules[$counter][left]",
            'input_id' => "left-$counter",
            'Value' => $data['left'] ?? '',
        ]
    );
    $res.= input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Texto direita',
            'Placeholder' => 'Ex: Ano de começo das atividades',
            'name' => "Modules[$counter][right]",
            'input_id' => "right-$counter",
            'Value' => $data['right'] ?? '',
        ]
    );
    $res.= common_inputs_for_page($type_form, 'status_id', $data['status_id'] ?? 1, $counter);
    $res.= "</div>";

    return $res;
}



/**
 * Generate HTML form inputs for configuring 'inputs_for_hero'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'hidden'.
 */
function inputs_for_hero(string $type_form, $counter, array $data = [])
{
    $TypeModule = 'hero';

    $res = '<div class="form-row">';
    $res.= common_inputs_for_page($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'TypeModule', $TypeModule, $counter);
    $res.= common_inputs_for_page($type_form, 'image_folder', 'modules', $counter);
    $res.= common_inputs_for_page($type_form, 'class', $data['class'] ?? "", $counter);
    $res.= common_inputs_for_page($type_form, 'section_attributes', $data['section_attributes'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'status_id', $data['status_id'] ?? 1, $counter);
    $res.= common_inputs_for_page($type_form, 'title', $data['title'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'subtitle', $data['subtitle'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'align', $data['align'] ?? '', $counter);
    $res.= input('selection_type', $type_form,
        [
           'size' => 'col-md-6 col-lg-4',
           'label' => 'Variação',
           'name' => "Modules[$counter][variation]",
           'input_id' => "variation-$counter",
           'Options' => [
                [ 'value' => 'hero_1', 'display' => 'Hero 1' ],
                [ 'value' => 'hero_2', 'display' => 'Hero 2' ],
                [ 'value' => 'hero_3', 'display' => 'Hero 3' ],
                [ 'value' => 'hero_4', 'display' => 'Hero 4' ],
                [ 'value' => 'hero_5', 'display' => 'Hero 5' ],
           ],
           'Value' => $data['variation'] ?? '',
       ]
    );
    $res.= common_inputs_for_page($type_form, 'subscribers_only', $data['subscribers_only'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'container', $data['container'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'animations', $data['animations'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'contents', $data['contents'] ?? '', $counter);
    //$res.= common_inputs_for_page($type_form, 'vertical_text', $data['vertical_text'] ?? '', $counter);
    $res.= background_settings($type_form, $data['background'] ?? null, $counter);
    $res.= "</div>";

    $contents = common_inputs_for_page($type_form, 'image',  [ 'value' => $data['image'] ?? '', 'src' => 'modules' ], $counter);
    $contents.= input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Efeito do background',
            'Placeholder' => 'Subtítulo do Módulo',
            'name' => "Modules[$counter][effect]",
            'input_id' => "effect-$counter",
            'Value' => $data['effect'] ?? '',
        ]
    );

    // $res.= dropdown_content( ['size' => 'col-md-6','title' => 'Conteúdo', 'content' => $contents] );

    return $res;
}


/**
 * Generate HTML form inputs for configuring 'inputs_for_custom'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'hidden'.
 */
function inputs_for_custom(string $type_form, $counter, array $data = [])
{
    $TypeModule = 'custom';

    $res = '<div class="form-row">';
    $res.= common_inputs_for_page($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'TypeModule', $TypeModule, $counter);
    $res.= common_inputs_for_page($type_form, 'class', $data['class'] ?? "", $counter);
    $res.= common_inputs_for_page($type_form, 'section_attributes', $data['section_attributes'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'status_id', $data['status_id'] ?? 1, $counter);
    $res.= common_inputs_for_page($type_form, 'subscribers_only', $data['subscribers_only'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'container', $data['container'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'animations', $data['animations'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'contents', $data['contents'] ?? '', $counter);
    $res.= sizes_selector($type_form, 'Modules', $data['size'] ?? '', $counter);
    $res.= background_settings($type_form, $data['background'] ?? null, $counter);
    $res.= footer_settings($type_form, $data['footer'] ?? null, $counter);
    $res.= "</div>";

    return $res;
}


/**
 * Generate HTML form inputs for configuring 'inputs_for_crud'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'hidden'.
 */
function inputs_for_crud(string $type_form, $counter, array $data = [])
{
    $TypeModule = 'crud';

    $res = '<div class="form-row">';
    $res.= common_inputs_for_page($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'TypeModule', $TypeModule, $counter);
    $res.= common_inputs_for_page($type_form, 'class', $data['class'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'section_attributes', $data['section_attributes'] ?? '', $counter);
    $res.= input('selection_type', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Widget de CRUD',
            'name' => "Modules[$counter][crud_id]",
            'input_id' => "crud_id-$counter",
            'Query' => "SELECT id as value, piece_name as display FROM tb_cruds WHERE status_id != 2 ORDER BY piece_name",
            'Value' => $data['crud_id'] ?? '',
            'Required' => true
        ]
    );
    $res.= common_inputs_for_page($type_form, 'status_id', $data['status_id'] ?? 1, $counter);
    $res.= common_inputs_for_page($type_form, 'subscribers_only', $data['subscribers_only'] ?? '', $counter);
    $res.= sizes_selector($type_form, 'Modules', $data['size'] ?? '', $counter);
    $res.= background_settings($type_form, $data['background'] ?? null, $counter);
    $res.= "</div>";

    return $res;
}


/**
* Generate HTML form inputs for configuring 'inputs_for_carousel'.
*
* @param string $type_form - The type of form, e.g., 'insert' or 'update'.
* @param mixed $counter - A counter or identifier for the input fields.
* @param array $data - An array containing default values for input attributes (optional).
*
* @return string - A string containing HTML form inputs and select boxes for configuring 'hidden'.
*/
function inputs_for_carousel(string $type_form, $counter, array $data = [])
{
   $TypeModule = 'carousel';

   $res = '<div class="form-row">';
   $res.= common_inputs_for_page($type_form, 'id', $data['id'] ?? '', $counter);
   $res.= common_inputs_for_page($type_form, 'TypeModule', $TypeModule, $counter);
   $res.= common_inputs_for_page($type_form, 'class', $data['class'] ?? '', $counter);
   $res.= common_inputs_for_page($type_form, 'section_attributes', $data['section_attributes'] ?? '', $counter);
   $res.= common_inputs_for_page($type_form, 'status_id', $data['status_id'] ?? 1, $counter);
   $res.= common_inputs_for_page($type_form, 'title', $data['title'] ?? '', $counter);
   $res.= common_inputs_for_page($type_form, 'subtitle', $data['subtitle'] ?? '', $counter);
   $res.= common_inputs_for_page($type_form, 'align', $data['align'] ?? '', $counter);
   $res.= common_inputs_for_page($type_form, 'subscribers_only', $data['subscribers_only'] ?? '', $counter);
   $res.= common_inputs_for_page($type_form, 'container', $data['container'] ?? '', $counter);
   $res.= common_inputs_for_page($type_form, 'animations', $data['animations'] ?? '', $counter);
   $res.= common_inputs_for_page($type_form, 'commands', $data['commands'] ?? '', $counter);
   $res.= sizes_selector($type_form, 'Modules', $data['size'] ?? '', $counter);
   $res.= background_settings($type_form, $data['background'] ?? null, $counter);
   $res.= footer_settings($type_form, $data['footer'] ?? null, $counter);
   $res.= "</div>";

   $res.= addable_content([ 'title' => 'Conteúdo', 'type_form' => $type_form, 'counter' => $counter, 'function' => "inputs_for_{$TypeModule}_content", 'content' => $data['contents'] ?? '', 'type' => $TypeModule ]);

   return $res;
}

function inputs_for_carousel_content(string $type_form, $counter, $module_counter, array $data = [])
{
   $res = '<div class="form-row">';
   $res.= common_inputs_for_page_block($type_form, 'image_folder', 'modules', $counter, $module_counter);
   $res.= common_inputs_for_page_block($type_form, 'image', [ 'value' => $data['image'] ?? '', 'src' => 'modules' ], $counter, $module_counter);
   $res.= common_inputs_for_page_block($type_form, 'url', $data['url'] ?? '', $counter, $module_counter);
   $res.= "</div>";

   return addable_content_input_group([ 'title' => 'Opção', 'content' => $res, 'counter' => $counter, 'module_counter' => $module_counter ]);
}


/**
 * Generate HTML form inputs for configuring 'inputs_for_accordion'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'hidden'.
 */
function inputs_for_accordion(string $type_form, $counter, array $data = [])
{
    $TypeModule = 'accordion';

    $res = '<div class="form-row">';
    $res.= common_inputs_for_page($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'TypeModule', $TypeModule, $counter);
    $res.= common_inputs_for_page($type_form, 'class', $data['class'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'section_attributes', $data['section_attributes'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'status_id', $data['status_id'] ?? 1, $counter);
    $res.= common_inputs_for_page($type_form, 'title', $data['title'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'subtitle', $data['subtitle'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'align', $data['align'] ?? '', $counter);
    $res.= input('selection_type', $type_form,
        [
           'size' => 'col-md-6 col-lg-4',
           'label' => 'Variação',
           'name' => "Modules[$counter][variation]",
           'input_id' => "variation-$counter",
           'Options' => "
                1|| accordion-default|| Accordion Default;
                2|| accordion-square|| Accordion Square;
                3|| accordion-more-less|| Accordion More Less;",
           'Value' => $data['variation'] ?? '',
       ]
    );
    $res.= common_inputs_for_page($type_form, 'subscribers_only', $data['subscribers_only'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'container', $data['container'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'animations', $data['animations'] ?? '', $counter);
    $res.= sizes_selector($type_form, 'Modules', $data['size'] ?? '', $counter);
    $res.= background_settings($type_form, $data['background'] ?? null, $counter);
    $res.= footer_settings($type_form, $data['footer'] ?? null, $counter);
    $res.= "</div>";

    $res.= addable_content([ 'title' => 'Conteúdo', 'type_form' => $type_form, 'counter' => $counter, 'function' => "inputs_for_{$TypeModule}_content", 'content' => $data['contents'] ?? '', 'type' => $TypeModule ]);

    return $res;
}

function inputs_for_accordion_content(string $type_form, $counter, $module_counter, array $data = [])
{
    $res = '<div class="form-row">';
    $res.= common_inputs_for_page_block($type_form, 'title', $data['title'] ?? '', $counter, $module_counter);
    $res.= common_inputs_for_page_block($type_form, 'content', $data['content'] ?? '', $counter, $module_counter);
    $res.= "</div>";

    return addable_content_input_group([ 'title' => 'Opção', 'content' => $res, 'counter' => $counter, 'module_counter' => $module_counter ]);
}


 /**
 * Generate HTML form inputs for configuring 'inputs_for_horizontal_list'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'hidden'.
 */
function inputs_for_horizontal_list(string $type_form, $counter, array $data = [])
{
    $TypeModule = 'horizontal_list';

    $res = '<div class="form-row">';
    $res.= common_inputs_for_page($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'TypeModule', $TypeModule, $counter);
    $res.= common_inputs_for_page($type_form, 'class', $data['class'] ?? "", $counter);
    $res.= common_inputs_for_page($type_form, 'section_attributes', $data['section_attributes'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'status_id', $data['status_id'] ?? 1, $counter);
    $res.= common_inputs_for_page($type_form, 'title', $data['title'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'subtitle', $data['subtitle'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'align', $data['align'] ?? '', $counter);
    $res.= input('selection_type', $type_form,
        [
           'size' => 'col-md-6 col-lg-4',
           'label' => 'Variação',
           'name' => "Modules[$counter][variation]",
           'input_id' => "variation-$counter",
           'Options' => "
                1|| card_default|| Card default;
                2|| card_no_border|| Card no border;
                4|| card_subject|| Card subject;
                5|| badge|| Etiqueta;",
           'Value' => $data['variation'] ?? '',
       ]
    );
    $res.= common_inputs_for_page($type_form, 'subscribers_only', $data['subscribers_only'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'container', $data['container'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'animations', $data['animations'] ?? '', $counter);
    $res.= sizes_selector($type_form, 'Modules', $data['size'] ?? '', $counter);
    $res.= background_settings($type_form, $data['background'] ?? null, $counter);
    $res.= footer_settings($type_form, $data['footer'] ?? null, $counter);
    $res.= "</div>";

    $res.= addable_content([ 'title' => 'Conteúdo', 'type_form' => $type_form, 'counter' => $counter, 'function' => "inputs_for_{$TypeModule}_content", 'content' => $data['contents'] ?? '', 'type' => $TypeModule ]);

    return $res;
}

function inputs_for_horizontal_list_content(string $type_form, $counter, $module_counter, array $data = [])
{
    $res = '<div class="form-row">';
    $res.= common_inputs_for_page_block($type_form, 'image_folder', 'modules', $counter, $module_counter);
    $res.= common_inputs_for_page_block($type_form, 'icon', $data['icon'] ?? '', $counter, $module_counter);
    $res.= common_inputs_for_page_block($type_form, 'title', $data['title'] ?? '', $counter, $module_counter);
    $res.= common_inputs_for_page_block($type_form, 'content', $data['content'] ?? '', $counter, $module_counter);
    $res.= common_inputs_for_page_block($type_form, 'image', [ 'value' => $data['image'] ?? '', 'src' => 'modules' ], $counter, $module_counter);
    $res.= input('selection_type', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Cor do botão',
            'name' => "Modules[$counter][contents][$module_counter][color]",
            'input_id' => "color-$module_counter",
            'Options' => theme_colors(true),
            'Value' => $data['color'] ?? '',
            'Required' => true
        ]
    );
    $res.= input('selection_type', $type_form,
        [
            'type' => 'switch',
            'size' => 'col-md-6 col-lg-4',
            'name' => "Modules[$counter][contents][$module_counter][radius]",
            'input_id' => "radius-$module_counter",
            'Options' => [ ['value' => '1', 'display' => 'Borda arredondada'] ],
            'Value' => $data['radius'] ?? '',
            'Required' => true
        ]
    );
    $res.= common_inputs_for_page_block($type_form, 'url', $data['url'] ?? '', $counter, $module_counter);
    //$res.= common_inputs_for_page_block($type_form, 'button_title', $data['button_title'] ?? '', $counter, $module_counter);
    $res.= sizes_selector($type_form, 'Modules', $data['size'] ?? '', $counter, $module_counter);
    $res.= "</div>";

    return addable_content_input_group([ 'title' => 'Opção', 'content' => $res, 'counter' => $counter, 'module_counter' => $module_counter ]);
}


/**
 * Generate HTML form inputs for configuring 'inputs_for_huge_button'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'hidden'.
 */
function inputs_for_huge_button(string $type_form, $counter, array $data = [])
{
    $TypeModule = 'huge_button';

    $res = '<div class="form-row">';
    $res.= common_inputs_for_page($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'TypeModule', $TypeModule, $counter);
    $res.= common_inputs_for_page($type_form, 'class', $data['class'] ?? "", $counter);
    $res.= common_inputs_for_page($type_form, 'section_attributes', $data['section_attributes'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'status_id', $data['status_id'] ?? 1, $counter);
    $res.= common_inputs_for_page($type_form, 'title', $data['title'] ?? '', $counter);
    $res.= input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Link do botão',
            'type' => 'url',
            'Placeholder' => 'seulink.com',
            'name' => "Modules[$counter][url]",
            'input_id' => "url-$counter",
            'Value' => !empty($data['url']) ? $data['url'] : '',
        ]
    );
    $res.= common_inputs_for_page($type_form, 'subscribers_only', $data['subscribers_only'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'container', $data['container'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'animations', $data['animations'] ?? '', $counter);
    $res.= sizes_selector($type_form, 'Modules', $data['size'] ?? '', $counter);
    $res.= background_settings($type_form, $data['background'] ?? null, $counter);
    $res.= "</div>";

    return $res;
}



/**
 * Generate HTML form inputs for configuring 'inputs_for_vertical_list'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'hidden'.
 */
function inputs_for_vertical_list(string $type_form, $counter, array $data = [])
{
    $TypeModule = 'vertical_list';

    $res = '<div class="form-row">';
    $res.= common_inputs_for_page($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'TypeModule', $TypeModule, $counter);
    $res.= common_inputs_for_page($type_form, 'class', $data['class'] ?? "", $counter);
    $res.= common_inputs_for_page($type_form, 'section_attributes', $data['section_attributes'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'status_id', $data['status_id'] ?? 1, $counter);
    $res.= common_inputs_for_page($type_form, 'title', $data['title'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'subtitle', $data['subtitle'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'align', $data['align'] ?? '', $counter);
    $res.= input('selection_type', $type_form,
        [
           'size' => 'col-md-6 col-lg-4',
           'label' => 'Variação',
           'name' => "Modules[$counter][variation]",
           'input_id' => "variation-$counter",
           'Options' => [
                [ 'value' => 'dot', 'display' => 'Dot' ],
                [ 'value' => 'numbered', 'display' => 'Numerada' ],
                [ 'value' => 'roman', 'display' => 'Numerada (romano)' ],
                [ 'value' => 'literate', 'display' => 'Alfabeto' ],
                [ 'value' => 'lock-list', 'display' => 'Cadeados' ],
                [ 'value' => 'verified-list', 'display' => 'Verificados' ],
            ],
           'Value' => $data['variation'] ?? '',
       ]
    );
    $res.= common_inputs_for_page($type_form, 'subscribers_only', $data['subscribers_only'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'container', $data['container'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'animations', $data['animations'] ?? '', $counter);
    $res.= sizes_selector($type_form, 'Modules', $data['size'] ?? '', $counter);
    $res.= background_settings($type_form, $data['background'] ?? null, $counter);
    $res.= footer_settings($type_form, $data['footer'] ?? null, $counter);
    $res.= "</div>";

    $res.= addable_content([ 'title' => 'Conteúdo', 'type_form' => $type_form, 'counter' => $counter, 'function' => "inputs_for_{$TypeModule}_content", 'content' => $data['contents'] ?? '', 'type' => $TypeModule ]);

    return $res;
}

function inputs_for_vertical_list_content(string $type_form, $counter, $module_counter, array $data = [])
{
    $res = '<div class="form-row">';
    $res.= common_inputs_for_page_block($type_form, 'content', $data['content'] ?? '', $counter, $module_counter);
    $res.= common_inputs_for_page_block($type_form, 'url', $data['url'] ?? '', $counter, $module_counter);
    $res.= "</div>";

    return addable_content_input_group([ 'title' => 'Opção', 'content' => $res, 'counter' => $counter, 'module_counter' => $module_counter ]);
}



/**
 * Generate HTML form inputs for configuring 'inputs_for_regressive_counter'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'hidden'.
 */
function inputs_for_regressive_counter(string $type_form, $counter, array $data = [])
{
    $TypeModule = 'regressive_counter';

    if ($type_form == 'insert')
    {
        $data['final_moment'] = [ 'date' => '', 'time' => '' ];
    }

    $res = '<div class="form-row">';
    $res.= common_inputs_for_page($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'TypeModule', $TypeModule, $counter);
    $res.= common_inputs_for_page($type_form, 'class', $data['class'] ?? "regressive-counter", $counter);
    $res.= common_inputs_for_page($type_form, 'section_attributes', $data['section_attributes'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'status_id', $data['status_id'] ?? 1, $counter);
    $res.= common_inputs_for_page($type_form, 'title', $data['title'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'subtitle', $data['subtitle'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'align', $data['align'] ?? '', $counter);
    $res.= input('basic', $type_form,
        [
            'size' => 'col-6 col-xl-3"',
            'label' => 'Data do término',
            'type' => 'date',
            'Placeholder' => 'Ícone que representa a URL',
            'name' => "Modules[$counter][final_moment][date]",
            'input_id' => "final_moment-$counter",
            'Value' => !empty($data['final_moment']['date']) ? $data['final_moment']['date'] : '',
            'Required' => true,
        ]
    );
    $res.= input('basic', $type_form,
        [
            'size' => 'col-6 col-xl-3"',
            'label' => 'Horário do término',
            'type' => 'time',
            'Placeholder' => 'Ícone que representa a URL',
            'name' => "Modules[$counter][final_moment][time]",
            'input_id' => "final_moment-$counter",
            'Value' => !empty($data['final_moment']['time']) ? $data['final_moment']['time'] : '',
            'Required' => true,
        ]
    );
    $res.= common_inputs_for_page($type_form, 'subscribers_only', $data['subscribers_only'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'container', $data['container'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'animations', $data['animations'] ?? '', $counter);
    $res.= sizes_selector($type_form, 'Modules', $data['size'] ?? '', $counter);
    $res.= background_settings($type_form, $data['background'] ?? null, $counter);
    $res.= footer_settings($type_form, $data['footer'] ?? null, $counter);
    $res.= "</div>";

    return $res;
}



/**
 * Generate HTML form inputs for configuring 'inputs_for_cards'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'hidden'.
 */
function inputs_for_cards(string $type_form, $counter, array $data = [])
{
    $TypeModule = 'cards';

    $res = '<div class="form-row">';
    $res.= common_inputs_for_page($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'TypeModule', $TypeModule, $counter);
    $res.= common_inputs_for_page($type_form, 'class', $data['class'] ?? "", $counter);
    $res.= common_inputs_for_page($type_form, 'section_attributes', $data['section_attributes'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'status_id', $data['status_id'] ?? 1, $counter);
    $res.= common_inputs_for_page($type_form, 'title', $data['title'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'subtitle', $data['subtitle'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'align', $data['align'] ?? '', $counter);
    $res.= input('selection_type', $type_form,
        [
           'size' => 'col-md-6 col-lg-4',
           'label' => 'Variação',
           'name' => "Modules[$counter][variation]",
           'input_id' => "variation-$counter",
           'Options' => "
                1|| card_default|| Card default;
                2|| card_no_border|| Card no border;
                4|| card_subject|| Card subject;",
           'Value' => $data['variation'] ?? '',
       ]
    );
    $res.= common_inputs_for_page($type_form, 'subscribers_only', $data['subscribers_only'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'container', $data['container'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'animations', $data['animations'] ?? '', $counter);
    $res.= sizes_selector($type_form, 'Modules', $data['size'] ?? '', $counter);
    $res.= background_settings($type_form, $data['background'] ?? null, $counter);
    $res.= footer_settings($type_form, $data['footer'] ?? null, $counter);
    $res.= "</div>";

    $res.= addable_content([ 'title' => 'Conteúdo', 'type_form' => $type_form, 'counter' => $counter, 'function' => "inputs_for_{$TypeModule}_content", 'content' => $data['contents'] ?? '', 'type' => $TypeModule ]);

    return $res;
}

function inputs_for_cards_content(string $type_form, $counter, $module_counter, array $data = [])
{
    $res = '<div class="form-row">';
    $res.= common_inputs_for_page_block($type_form, 'image_folder', 'modules', $counter, $module_counter);
    $res.= common_inputs_for_page_block($type_form, 'icon', $data['icon'] ?? '', $counter, $module_counter);
    $res.= common_inputs_for_page_block($type_form, 'title', $data['title'] ?? '', $counter, $module_counter);
    $res.= common_inputs_for_page_block($type_form, 'content', $data['content'] ?? '', $counter, $module_counter);
    $res.= common_inputs_for_page_block($type_form, 'image', [ 'value' => $data['image'] ?? '', 'src' => 'modules' ], $counter, $module_counter);
    $res.= common_inputs_for_page_block($type_form, 'url', $data['url'] ?? '', $counter, $module_counter);
    $res.= sizes_selector($type_form, 'Modules', $data['size'] ?? '', $counter, $module_counter);

    $link_button = "<div class='row pt-3'>";
    $link_button.= button_settings($type_form, "Modules[$counter][contents][$module_counter]", $data['link_button'] ?? '', $counter, $module_counter);
    $link_button.= "</div>";

    $res.= dropdown_content( ['size' => 'col-md-6','title' => 'Link/Botão', 'content' => $link_button] );
    $res.= background_settings_content($type_form, $data ?? null, $counter, $module_counter);
    $res.= "</div>";

    return addable_content_input_group([ 'title' => 'Opção', 'content' => $res, 'counter' => $counter, 'module_counter' => $module_counter ]);
}



/**
 * Generate HTML form inputs for configuring 'inputs_for_floating_card'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'hidden'.
 */
function inputs_for_floating_card(string $type_form, $counter, array $data = [])
{
    $TypeModule = 'floating_card';

    $res = '<div class="form-row">';
    $res.= common_inputs_for_page($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'image_folder', 'modules', $counter);
    $res.= common_inputs_for_page($type_form, 'TypeModule', $TypeModule, $counter);
    $res.= input('selection_type', $type_form,
        [
           'size' => 'col-md-6 col-lg-4"',
           'label' => 'Variação',
           'name' => "Modules[$counter][variation]",
           'input_id' => "variation-$counter",
           'Options' => "
                1|| card_default|| Card default;",
           'Value' => $data['variation'] ?? '',
           'Required' => true
       ]
    );
    $res.= input('selection_type', $type_form,
        [
           'size' => 'col-md-6 col-lg-4"',
           'label' => 'Variação',
           'name' => "Modules[$counter][position]",
           'input_id' => "position-$counter",
           'Options' => [
                [ 'value' => 'fixed-top-start',    'display' => 'Superior esquerdo' ],
                [ 'value' => 'fixed-top-end',      'display' => 'Superior direito'  ],
                [ 'value' => 'fixed-center-start', 'display' => 'Meio esquerdo'     ],
                [ 'value' => 'fixed-center-end',   'display' => 'Meio direito'      ],
                [ 'value' => 'fixed-bottom-start', 'display' => 'Inferior esquerdo' ],
                [ 'value' => 'fixed-bottom-end',   'display' => 'Inferior direito'  ],
           ],
           'Value' => $data['position'] ?? 'fixed-bottom-start',
           'Required' => true
       ]
    );
    $res.= common_inputs_for_page($type_form, 'delay', $data['delay'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'status_id', $data['status_id'] ?? 1, $counter);
    $res.= common_inputs_for_page($type_form, 'title', $data['title'] ?? '', $counter);
    $res.= input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Ícone',
            'Placeholder' => 'Ex: Ícone que representa melhor o serviço',
            'name' => "Modules[$counter][icon]",
            'input_id' => "icon-$counter",
            'Value' => $data['icon'] ?? '',
        ]
    );
    $res.= input('textarea', $type_form,
        [
            'size' => 'col-12',
            'label' => 'Conteúdo',
            'attributes' => 'rows(4);',
            'text_editor' => 1,
            'name' => "Modules[$counter][content]",
            'input_id' => "content-$counter",
            'Value' => $data['content'] ?? '',
        ]
    );
    $res.= input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Link do botão',
            'type' => 'url',
            'Placeholder' => 'seulink.com',
            'name' => "Modules[$counter][url]",
            'input_id' => "url-$counter",
            'Value' => $data['url'] ?? '',
        ]
    );
    $res.= input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Texto do botão',
            'Placeholder' => "Pré-definido como 'Ver mais'",
            'name' => "Modules[$counter][button_title]",
            'input_id' => "button_title-$counter",
            'Value' => $data['button_title'] ?? '',
        ]
    );
    $res.= input('upload', $type_form,
        [
            'type' => 'images',
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Imagem',
            'attributes' => 'accept:(image/*);',
            'name' => "Modules[$counter][image]",
            'input_id' => "image-$counter",
            'Value' => $data['image'] ?? '',
            'Src' => 'modules',
        ]
        );
    $res.= common_inputs_for_page($type_form, 'subscribers_only', $data['subscribers_only'] ?? '', $counter);
    $res.= "</div>";

    return $res;
}



/**
 * Generate HTML form inputs for configuring 'inputs_for_gallery'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'hidden'.
 */
function inputs_for_gallery(string $type_form, $counter, array $data = [])
{
    $TypeModule = 'gallery';

    $res = '<div class="form-row">';
    $res.= common_inputs_for_page($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'TypeModule', $TypeModule, $counter);
    $res.= common_inputs_for_page($type_form, 'class', $data['class'] ?? "", $counter);
    $res.= common_inputs_for_page($type_form, 'section_attributes', $data['section_attributes'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'status_id', $data['status_id'] ?? 1, $counter);
    $res.= common_inputs_for_page($type_form, 'title', $data['title'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'subtitle', $data['subtitle'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'align', $data['align'] ?? '', $counter);
    $res.= input('selection_type', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Variação',
            'name' => "Modules[$counter][variation]",
            'input_id' => "variation-$counter",
            'Options' => [
                [ 'value' => 'gallery_default', 'display' => 'Galeria default' ],
                [ 'value' => 'gallery_timeline', 'display' => 'Galeria timeline' ],
            ],
            'Value' => $data['variation'] ?? '',
       ]
    );
    $res.= common_inputs_for_page($type_form, 'subscribers_only', $data['subscribers_only'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'container', $data['container'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'animations', $data['animations'] ?? '', $counter);
    $res.= sizes_selector($type_form, 'Modules', $data['size'] ?? '', $counter);
    $res.= background_settings($type_form, $data['background'] ?? null, $counter);
    $res.= footer_settings($type_form, $data['footer'] ?? null, $counter);
    $res.= "</div>";


    $res.= addable_content([ 'title' => 'Conteúdo', 'type_form' => $type_form, 'counter' => $counter, 'function' => "inputs_for_{$TypeModule}_content", 'content' => $data['contents'] ?? '', 'type' => $TypeModule ]);

    return $res;
}

function inputs_for_gallery_content(string $type_form, $counter, $module_counter, array $data = [])
{
    $res = '<div class="form-row">';
    $res.= common_inputs_for_page_block($type_form, 'image', [ 'value' => $data['image'] ?? '', 'src' => 'modules' ], $counter, $module_counter);
    $res.= common_inputs_for_page_block($type_form, 'image_folder', 'modules', $counter, $module_counter);
    $res.= common_inputs_for_page_block($type_form, 'title', $data['title'] ?? '', $counter, $module_counter);
    $res.= common_inputs_for_page_block($type_form, 'content', $data['content'] ?? '', $counter, $module_counter);

    $res.= "</div>";

    $link_button = "<div class='row pt-3'>";
    $link_button.= button_settings($type_form, "Modules[$counter][contents][$module_counter]", $data['link_button'] ?? '', $counter, $module_counter);
    $link_button.= "</div>";

    $res.= sizes_selector($type_form, 'Modules', $data['size'] ?? '', $counter);

    $res.= background_settings($type_form, $data['background'] ?? null, $counter);

    return addable_content_input_group([ 'title' => 'Opção', 'content' => $res, 'counter' => $counter, 'module_counter' => $module_counter ]);
}


/**
 * Generate HTML form inputs for configuring 'inputs_for_modal'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'hidden'.
 */
function inputs_for_modal(string $type_form, $counter, array $data = [])
{
    $TypeModule = 'modal';

    $res = '<div class="form-row">';
    $res.= common_inputs_for_page($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'TypeModule', $TypeModule, $counter);
    $res.= input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Atributos',
            'Placeholder' => 'data, ON events, etc.',
            'name' => "Modules[$counter][attributes]",
            'input_id' => "attributes-$counter",
            'Value' => $data['attributes'] ?? "data-modal",
        ]
    );
    $res.= common_inputs_for_page($type_form, 'status_id', $data['status_id'] ?? 1, $counter);
    $res.= common_inputs_for_page($type_form, 'title', $data['title'] ?? '', $counter);
    $res.= input('selection_type', $type_form,
        [
           'size' => 'col-md-6 col-lg-4',
           'label' => 'Tamanho',
           'name' => "Modules[$counter][size]",
           'input_id' => "size-$counter",
           'Options' => "
                1|| sm|| Pequeno;
                2|| md|| Médio;
                3|| lg|| Grande;",
           'Value' => $data['size'] ?? '',
           'Required' => true,
       ]
    );
    $res.= common_inputs_for_page($type_form, 'delay', $data['delay'] ?? '', $counter);
    $res.= input('selection_type', $type_form,
        [
            'type' => 'switch',
            'size' => 'col-md-6 col-lg-4',
            'name' => "Modules[$counter][close_button]",
            'input_id' => "close_button-$counter",
            'Options' => "3|| 1|| Botão de fechamento;",
            'Value' => $data['close_button'] ?? '',
        ]
    );
    $res.= common_inputs_for_page($type_form, 'subscribers_only', $data['subscribers_only'] ?? '', $counter);
    $res.= input('textarea', $type_form,
        [
            'size' => 'col-12',
            'label' => 'Body',
            'attributes' => 'rows(4);',
            'text_editor' => 1,
            'name' => "Modules[$counter][body]",
            'input_id' => "body-$counter",
            'Value' => $data['body'] ?? '',
        ]
    );
    $res.= input('textarea', $type_form,
        [
            'size' => 'col-12',
            'label' => 'Footer',
            'attributes' => 'rows(4);',
            'text_editor' => 1,
            'name' => "Modules[$counter][footer]",
            'input_id' => "footer-$counter",
            'Value' => $data['footer'] ?? '',
        ]
    );
    $res.= "</div>";

    return $res;
}


/**
 * Generate HTML form inputs for configuring 'inputs_for_fixed_message'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'hidden'.
 */
function inputs_for_fixed_message(string $type_form, $counter, array $data = [])
{
    $TypeModule = 'fixed_message';

    $res = '<div class="form-row">';
    $res.= common_inputs_for_page($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'TypeModule', $TypeModule, $counter);
    $res.= common_inputs_for_page($type_form, 'status_id', $data['status_id'] ?? 1, $counter);
    $res.= input('selection_type', $type_form,
        [
           'size' => 'col-md-6 col-lg-4',
           'label' => 'Posição',
           'name' => "Modules[$counter][position]",
           'input_id' => "position-$counter",
           'Options' => "
                1|| fixed-top|| Topo;
                2|| fixed-bottom|| Rodapé;",
           'Value' => $data['position'] ?? 'fixed-bottom',
           'Required' => true,
       ]
    );
    $res.= input('textarea', $type_form,
        [
            'size' => 'col-12',
            'label' => 'Body',
            'attributes' => 'rows(4);',
            'text_editor' => 1,
            'name' => "Modules[$counter][body]",
            'input_id' => "body-$counter",
            'Value' => $data['body'] ?? '',
        ]
    );
    $res.= common_inputs_for_page($type_form, 'subscribers_only', $data['subscribers_only'] ?? '', $counter);
    $res.= "</div>";

    return $res;
}


/**
 * Generate HTML form inputs for configuring 'inputs_for_toast'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'hidden'.
 */
function inputs_for_toast(string $type_form, $counter, array $data = [])
{
    $TypeModule = 'toast';

    $data['color'] = (isset($data['color']) AND is_json($data['color'])) ? (array) json_decode($data['color']) : ['text' => '', 'background' => ''];

    $res = '<div class="form-row">';
    $res.= common_inputs_for_page($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'TypeModule', $TypeModule, $counter);
    $res.= common_inputs_for_page($type_form, 'title', $data['title'] ?? '', $counter);
    $res.= input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Atributos',
            'Placeholder' => 'data, ON events, etc.',
            'name' => "Modules[$counter][attributes]",
            'input_id' => "attributes-$counter",
            'Value' => $data['attributes'] ?? "data-toast",
        ]
    );
    $res.= input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Tempo de exibição',
            'Placeholder' => 'Origem do Post',
            'name' => "Modules[$counter][time]",
            'input_id' => "time-$counter",
            'Value' => $data['time'] ?? '10000',
            'Required' => true
        ]
    );
    $res.= input('selection_type', $type_form,
        [
           'size' => 'col-md-6 col-lg-4',
           'label' => 'Cor do texto',
           'name' => "Modules[$counter][color][text]",
           'input_id' => "text-$counter",
           'Options' => theme_colors(true),
           'Value' => $data['color']['text'] ?? 'white',
       ]
    );
    $res.= input('selection_type', $type_form,
        [
           'size' => 'col-md-6 col-lg-4',
           'label' => 'Cor do fundo',
           'name' => "Modules[$counter][color][background]",
           'input_id' => "background-$counter",
           'Options' => theme_colors(true),
           'Value' => $data['color']['background'] ?? 'info',
       ]
    );
    $res.= common_inputs_for_page($type_form, 'delay', $data['delay'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'status_id', $data['status_id'] ?? 1, $counter);
    $res.= input('textarea', $type_form,
        [
            'size' => 'col-12',
            'label' => 'Conteúdo',
            'attributes' => 'rows(4);',
            'text_editor' => 1,
            'name' => "Modules[$counter][content]",
            'input_id' => "content-$counter",
            'Value' => $data['content'] ?? '',
        ]
    );
    $res.= input('selection_type', $type_form,
        [
            'type' => 'switch',
            'size' => 'col-md-6 col-lg-4',
            'name' => "Modules[$counter][close_button]",
            'input_id' => "close_button-$counter",
            'Options' => "3|| 1|| Botão de fechamento;",
            'Value' => $data['close_button'] ?? '',
        ]
    );
    $res.= common_inputs_for_page($type_form, 'subscribers_only', $data['subscribers_only'] ?? '', $counter);
    $res.= "</div>";

    return $res;
}



/**
 * Generate HTML form inputs for configuring 'inputs_for_posts_showcase'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'hidden'.
 */
function inputs_for_posts_showcase(string $type_form, $counter, array $data = [])
{
    $TypeModule = 'posts_showcase';

    $res = '<div class="form-row">';
    $res.= common_inputs_for_page($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'TypeModule', $TypeModule, $counter);
    $res.= common_inputs_for_page($type_form, 'class', $data['class'] ?? "", $counter);
    $res.= common_inputs_for_page($type_form, 'section_attributes', $data['section_attributes'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'status_id', $data['status_id'] ?? 1, $counter);
    $res.= common_inputs_for_page($type_form, 'title', $data['title'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'subtitle', $data['subtitle'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'align', $data['align'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'subscribers_only', $data['subscribers_only'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'container', $data['container'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'animations', $data['animations'] ?? '', $counter);
    $res.= sizes_selector($type_form, 'Modules', $data['size'] ?? '', $counter);
    $res.= background_settings($type_form, $data['background'] ?? null, $counter);
    $res.= footer_settings($type_form, $data['footer'] ?? null, $counter);
    $res.= "</div>";

    $res.= addable_content([ 'title' => 'Conteúdo', 'type_form' => $type_form, 'counter' => $counter, 'function' => "inputs_for_{$TypeModule}_content", 'content' => $data['contents'] ?? '', 'type' => $TypeModule ]);

    return $res;
}

function inputs_for_posts_showcase_content(string $type_form, $counter, $module_counter, array $data = [])
{
    $res = '<div class="form-row">';
    $res.= common_inputs_for_page_block($type_form, 'image_folder', 'modules', $counter, $module_counter);
    $res.= common_inputs_for_page_block($type_form, 'title', $data['title'] ?? '', $counter, $module_counter);
    $res.= common_inputs_for_page_block($type_form, 'content', $data['content'] ?? '', $counter, $module_counter);
    $res.= common_inputs_for_page_block($type_form, 'url', $data['url'] ?? '', $counter, $module_counter);
    $res.= input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Autor',
            'Placeholder' => 'Origem do Post',
            'name' => "Modules[$counter][contents][$module_counter][author]",
            'input_id' => "author-$counter-$module_counter",
            'Value' => $data['author'] ?? '',
            'Required' => true
        ]
    );
    $res.= input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Nº de Comentários',
            'Placeholder' => 'Ex: 12',
            'name' => "Modules[$counter][contents][$module_counter][total_comments]",
            'input_id' => "total_comments-$counter-$module_counter",
            'Value' => $data['total_comments'] ?? '',
            'Required' => false
        ]
    );
    $res.= input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Data de postagem',
            'type' => 'date',
            'Placeholder' => 'Quantidade de comentários',
            'name' => "Modules[$counter][contents][$module_counter][date]",
            'input_id' => "date-$counter-$module_counter",
            'Value' => $data['date'] ?? '',
            'Required' => false
        ]
    );
    $res.= common_inputs_for_page_block($type_form, 'image', [ 'value' => $data['image'] ?? '', 'src' => 'modules' ], $counter, $module_counter);
    $res.= sizes_selector($type_form, 'Modules', $data['size'] ?? '', $counter, $module_counter);
    $res.= "</div>";

    return addable_content_input_group([ 'title' => 'Opção', 'content' => $res, 'counter' => $counter, 'module_counter' => $module_counter ]);
}



/**
 * Generate HTML form inputs for configuring 'inputs_for_numbers'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'hidden'.
 */
function inputs_for_numbers(string $type_form, $counter, array $data = [])
{
    $TypeModule = 'numbers';

    $res = '<div class="form-row">';
    $res.= common_inputs_for_page($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'TypeModule', $TypeModule, $counter);
    $res.= common_inputs_for_page($type_form, 'class', $data['class'] ?? "", $counter);
    $res.= common_inputs_for_page($type_form, 'section_attributes', $data['section_attributes'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'status_id', $data['status_id'] ?? 1, $counter);
    $res.= common_inputs_for_page($type_form, 'title', $data['title'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'subtitle', $data['subtitle'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'align', $data['align'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'subscribers_only', $data['subscribers_only'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'container', $data['container'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'animations', $data['animations'] ?? '', $counter);
    $res.= sizes_selector($type_form, 'Modules', $data['size'] ?? '', $counter);
    $res.= background_settings($type_form, $data['background'] ?? null, $counter);
    $res.= footer_settings($type_form, $data['footer'] ?? null, $counter);
    $res.= "</div>";

    $res.= addable_content([ 'title' => 'Conteúdo', 'type_form' => $type_form, 'counter' => $counter, 'function' => "inputs_for_{$TypeModule}_content", 'content' => $data['contents'] ?? '', 'type' => $TypeModule ]);

    return $res;
}

function inputs_for_numbers_content(string $type_form, $counter, $module_counter, array $data = [])
{
    $res = '<div class="form-row">';
    $res.= common_inputs_for_page_block($type_form, 'title', $data['title'] ?? '', $counter, $module_counter);

    $res.= input('basic', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Número real',
            'Placeholder' => 'Ex: 150+',
            'name' => "Modules[$counter][contents][$module_counter][content]",
            'input_id' => "content-$counter-$module_counter",
            'Value' => $data['content'] ?? '',
            'Required' => true
        ]
    );
    $res.= sizes_selector($type_form, 'Modules', $data['size'] ?? '', $counter, $module_counter);
    $res.= "</div>";

    return addable_content_input_group([ 'title' => 'Opção', 'content' => $res, 'counter' => $counter, 'module_counter' => $module_counter ]);
}



/**
 * Generate HTML form inputs for configuring 'inputs_for_videos'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'hidden'.
 */
function inputs_for_videos(string $type_form, $counter, array $data = [])
{
    $TypeModule = 'videos';

    $res = '<div class="form-row">';
    $res.= common_inputs_for_page($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'TypeModule', $TypeModule, $counter);
    $res.= common_inputs_for_page($type_form, 'class', $data['class'] ?? "", $counter);
    $res.= common_inputs_for_page($type_form, 'section_attributes', $data['section_attributes'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'status_id', $data['status_id'] ?? 1, $counter);
    $res.= common_inputs_for_page($type_form, 'title', $data['title'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'subtitle', $data['subtitle'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'align', $data['align'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'subscribers_only', $data['subscribers_only'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'container', $data['container'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'animations', $data['animations'] ?? '', $counter);
    $res.= sizes_selector($type_form, 'Modules', $data['size'] ?? '', $counter);
    $res.= background_settings($type_form, $data['background'] ?? null, $counter);
    $res.= footer_settings($type_form, $data['footer'] ?? null, $counter);
    $res.= "</div>";

    $res.= addable_content([ 'title' => 'Conteúdo', 'type_form' => $type_form, 'counter' => $counter, 'function' => "inputs_for_{$TypeModule}_content", 'content' => $data['contents'] ?? '', 'type' => $TypeModule ]);

    return $res;
}

function inputs_for_videos_content(string $type_form, $counter, $module_counter, array $data = [])
{
    $res = '<div class="form-row">';
    $res.= input('textarea', $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Iframe do vídeo',
            'attributes' => 'rows(1);',
            'Placeholder' => 'Iframe da plataforma que o vídeo está hospedado',
            'name' => "Modules[$counter][contents][$module_counter][url]",
            'input_id' => "url-$counter-$module_counter",
            'Value' => $data['url'] ?? '',
            'Required' => true,
        ]
    );
    $res.= sizes_selector($type_form, 'Modules', $data['size'] ?? '', $counter, $module_counter);
    $res.= "</div>";

    return addable_content_input_group([ 'title' => 'Opção', 'content' => $res, 'counter' => $counter, 'module_counter' => $module_counter ]);
}


/**
 * Generate HTML form inputs for configuring 'inputs_for_comments'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'hidden'.
 */
function inputs_for_comments(string $type_form, $counter, array $data = [])
{
    $TypeModule = 'comments';

    $res = '<div class="form-row">';
    $res.= common_inputs_for_page($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'TypeModule', $TypeModule, $counter);
    $res.= common_inputs_for_page($type_form, 'class', $data['class'] ?? "", $counter);
    $res.= common_inputs_for_page($type_form, 'section_attributes', $data['section_attributes'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'status_id', $data['status_id'] ?? 1, $counter);
    $res.= common_inputs_for_page($type_form, 'title', $data['title'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'subtitle', $data['subtitle'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'align', $data['align'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'subscribers_only', $data['subscribers_only'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'container', $data['container'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'animations', $data['animations'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'commands', $data['commands'] ?? '', $counter);
    $res.= sizes_selector($type_form, 'Modules', $data['size'] ?? '', $counter);
    $res.= background_settings($type_form, $data['background'] ?? null, $counter);
    $res.= footer_settings($type_form, $data['footer'] ?? null, $counter);
    $res.= "</div>";

    $res.= addable_content([ 'title' => 'Conteúdo', 'type_form' => $type_form, 'counter' => $counter, 'function' => "inputs_for_{$TypeModule}_content", 'content' => $data['contents'] ?? '', 'type' => $TypeModule ]);

    return $res;
}

function inputs_for_comments_content(string $type_form, $counter, $module_counter, array $data = [])
{
    $res = '<div class="form-row">';
    $res.= common_inputs_for_page_block($type_form, 'image_folder', 'modules', $counter, $module_counter);
    $res.= common_inputs_for_page_block($type_form, 'title', $data['title'] ?? '', $counter, $module_counter);
    $res.= common_inputs_for_page_block($type_form, 'subtitle', $data['subtitle'] ?? '', $counter, $module_counter);
    $res.= common_inputs_for_page_block($type_form, 'content', $data['content'] ?? '', $counter, $module_counter);
    $res.= common_inputs_for_page_block($type_form, 'image', [ 'value' => $data['image'] ?? '', 'src' => 'modules' ], $counter, $module_counter);
    $res.= "</div>";

    return addable_content_input_group([ 'title' => 'Opção', 'content' => $res, 'counter' => $counter, 'module_counter' => $module_counter ]);
}



/**
 * Generate HTML form inputs for configuring 'inputs_for_breadcrumbs'.
 *
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return string - A string containing HTML form inputs and select boxes for configuring 'hidden'.
 */
function inputs_for_breadcrumbs(string $type_form, $counter, array $data = [])
{
    $TypeModule = 'breadcrumbs';

    $res = '<div class="form-row">';
    $res.= common_inputs_for_page($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'TypeModule', $TypeModule, $counter);
    $res.= common_inputs_for_page($type_form, 'class', $data['class'] ?? "", $counter);
    $res.= common_inputs_for_page($type_form, 'section_attributes', $data['section_attributes'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'status_id', $data['status_id'] ?? 1, $counter);
    $res.= common_inputs_for_page($type_form, 'container', $data['container'] ?? '', $counter);
    $res.= common_inputs_for_page($type_form, 'animations', $data['animations'] ?? '', $counter);
    $res.= "</div>";

    return $res;
}


/**
 * Generate a PAGE management form for a content management system.
 *
 * @param string $type_form - The type of form, either 'insert' for creating a new record or 'update' for editing an existing one.
 * @param int $counter - An optional counter used for form fields (default is 1).
 *
 * @return int - The updated counter value.
 */
function manage_page_form(string $type_form = 'insert', int $counter = 1)
{
    global $config, $type_items;

    $id = id_by_get();

    $page = get_result("SELECT * FROM tb_pages WHERE id = '{$id}'");

    $type_form = $id
        ? 'update'
        : 'insert';

    if (count($page) == 0) {
        $type_form = 'insert';
    }

    $panel['hooks_out'][] = [
        'title' => 'Listar',
        'url'  => get_url_page('listar-paginas', 'full'),
        'color' => 'outline-info',
        'pre_icon' => 'fas fa-list',
    ];

    if ($type_form == 'update')
    {
        $panel['hooks_out'][] = [
            'title' => 'Duplicar',
            'url'  => rest_api_route_url("duplicate-record?id={$id}&table=tb_pages&foreign_key=page_id"),
            'attr'  => 'data-controller: (duplicate);',
            'color' => 'outline-info',
            'pre_icon' => 'fas fa-copy',
        ];

        $panel['hooks_out'][] = [
            'title' => 'Apagar',
            'url'  => rest_api_route_url("delete-record?id={$id}&table=tb_pages&foreign_key=page_id"),
            'attr'  => 'data-controller: (delete);',
            'color' => 'outline-danger',
            'pre_icon' => 'fas fa-trash',
        ];
    }

    echo crud_panel( $panel ?? [] );
    ?>

    <form method="POST" data-send-ctrl-s data-send-without-reload action="<?= rest_api_route_url("manage-page-system?mode={$type_form}") ?>" enctype="multipart/form-data">
    <div class="row">

        <div class="col-md-7">

        <?php
        // Load the CRUD fields.
        $boxes = '';
        if ($type_form == 'update')
        {
            $modules = get_results("SELECT * FROM tb_page_content WHERE page_id = '{$id}' AND TypeModule IS NOT Null ORDER BY order_reg ASC");
            foreach ($modules as $module)
            {
                $module = (array) $module['settings'] + $module;
                unset($module['settings']);

                // var_dump($module);

                $questions = inputs_select_type($module['TypeModule'], 'edit', "$counter", $module);

                $boxes.= field_content_card(
                    'update',
                    [
                        'delete' => true,
                        'move' => true,
                        'label' => $module['TypeModule'],
                        'type' => $module['TypeModule'],
                        'counter' => $counter,
                        'questions' => $questions,
                        'id' => $module['id'],
                    ]
                );

                $counter++;
            }
        }

        // Load the models
        $models_options = "<div class='list-group'>";
        $models = get_results("SELECT * FROM tb_page_content WHERE is_model = 1 AND TypeModule IS NOT null ORDER BY order_reg ASC");
        if (count($models) > 0)
        {
            foreach ($models as $module)
            {
                $module = (array) $module['settings'] + $module;
                unset($module['settings']);

                $models_options.= "
                <button type='button' data-insert-model-id='{$module['id']}'>
                    <span class='name'>{$module['name']}</span>
                    <span class='type'>{$module['TypeModule']}</span>
                </button>";

                $counter++;
            }
        }
        else {
            $models_options.= 'Você não tem modelos salvos.';
        }
        $models_options.= "</div>";


        //Load the options
        $options = '';
        foreach ($type_items['page'] as $optgroup => $option)
        {
            $options.= "<optgroup label='$optgroup'>";
            foreach ($option as $value => $info) $options.= "<option value='$value'>{$info['name']}</option>";
            $options.= '</optgroup>';
        }
        $options2 = '';
        foreach ($type_items['page'] as $optgroup => $option)
        {
            $options2.= "
            <section class='content-options'>
            <h6>". ($optgroup ?? '') ."</h6>
            <div class='d-grid gap-2'>";
            foreach ($option as $value => $info)
            {
                $options2.= "
                <button type='button' data-insert-model='$value' value='$value'>
                    ". icon($info['icon'] ?? '' ) ."
                    <p>{$info['name']}</p>
                </button>";
            }
            $options2.= '
            </div>
            </section>';
        }

        $modal_body = block('navtabs', [
            'id' => 'items-tab',
            'variation' => 'navtabs_pills',
            'class' => 'options',
            'contents' => [
                [
                    'id' => 'models',
                    'title' => 'Comuns',
                    'body' => $options2,
                    'active' => true,
                ],
                [
                    'id' => 'itens',
                    'title' => 'Salvos',
                    'body' => $models_options,
                ],
            ],
        ]);


        $modal_body = "
        <div class='row'>

        <div class='col-md-4 col-lg-3 col-xl-2 options'>
        $modal_body
        </div>

        <div class='col-md-8 col-lg-9 col-xl-10'>
            <div class='draggable-column' id='field-container-card'>$boxes</div>
        </div>

        </div>";



        $modal_footer = "
        <button type='button' class='btn btn-link' data-history='undo' title='Desfazer (Ctrl+Z)' aria-label='Desfazer'>
            ". icon('fas fa-rotate-left') ."
        </button>
        <button type='button' class='btn btn-link' data-history='redo' title='Refazer (Ctrl+Y ou Ctrl+Shift+Z)' aria-label='Refazer'>
            ". icon('fas fa-rotate-right') ."
        </button>".
        input('submit_button', 'update', [ 'class' => 'btn btn-success btn-lg', 'Value' => 'Editar' ]);
        ?>

        <div class="card box-fields">
        <div class="card-body">
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#edit-modules">Gerenciar campos</button>
        </div>

        </div>

        <?= SEO_form($type_form, [
            'access_count' => ($type_form=='update') ? $page['access_count'] : 0,
            'value' => $page['seo'] ?? [],
            'mode' => 'content',
        ]) ?>


        <div class="card box-fields piece-controls">
        <div class="card-header">
            <h6 class="mb-0">Estrutura</h6>
        </div>
        <div class="card-body">
        <div class="form-row">
            <?php
            echo block('modal', [
                'id' => 'edit-modules',
                'size' => 'fullscreen',
                'title' => 'Gerenciar componentes',
                'close_button' => true,
                'body' => $modal_body,
                'footer' => $modal_footer
            ]) .
            input('selection_type', $type_form,
                [
                    'type' => 'radio',
                    'size' => 'col-md-6',
                    'label' => 'Formato do Navbar',
                    'name' => "page_settings[navbar][format]",
                    'input_id' => "navbar-format",
                    'Options' => [
                        [ 'value' => 'full', 'display' => 'Completo' ],
                        [ 'value' => 'medium', 'display' => 'Resumido' ],
                        [ 'value' => 'none', 'display' => 'Não exibir' ],
                    ],
                    'Value' => $page['page_settings']['navbar']['format'] ?? ($config['branding']['navbar']['format'] ?? ''),
                ]
            ) .
            input('selection_type', $type_form,
                [
                    'type' => 'radio',
                    'size' => 'col-md-6',
                    'label' => 'Estilo do Navbar',
                    'name' => "page_settings[navbar][style]",
                    'input_id' => "navbar-style",
                    'Options' => [
                        [ 'value' => 'transparent-scroll', 'display' => 'Transparente com scroll' ],
                        [ 'value' => 'transparent-absolute', 'display' => 'Transparente e posição absoluta' ],
                        [ 'value' => 'absolute', 'display' => 'Posição absoluta' ],
                        [ 'value' => 'fixed', 'display' => 'Fixo no topo' ],
                    ],
                    'Value' => $page['page_settings']['navbar']['style'] ?? ($config['branding']['navbar']['style'] ?? ''),
                ]
            )

            .hr().

            input('selection_type', $type_form,
                [
                    'type' => 'radio',
                    'size' => 'col-md-6 col-lg-4',
                    'label' => 'Formato do Footer',
                    'name' => "page_settings[footer][format]",
                    'input_id' => "footer-format",
                    'Options' => [
                        [ 'value' => 'full', 'display' => 'Completo' ],
                        [ 'value' => 'medium', 'display' => 'Resumido' ],
                        [ 'value' => 'none', 'display' => 'Não exibir' ],
                    ],
                    'Value' => $page['page_settings']['footer']['format'] ?? ($config['branding']['footer']['format'] ?? ''),
                ]
            );
            ?>
        </div>
        </div>
        </div>



    </div><!--.col-lg-->

    <div class="col-md-5">

        <div class="card box-fields piece-controls">
        <div class="card-header">
            <h6 class="mb-0">Informações</h6>
        </div>
        <div class="card-body">
        <div class="form-row">
            <?php
                $group = input('submit_button', $type_form, [
                'size' => 'col-12',
                'class' => 'btn btn-st',
                'block' => true,
                'Value' => ($type_form == 'update') ? 'Editar' : 'Cadastrar'
            ]);

            $group.= input('basic', $type_form,
                [
                    'size' => 'col-12',
                    'label' => 'Título da página',
                    'Placeholder' => 'Ex: Home',
                    'name' => 'title',
                    'Value' => ($type_form=='update') ? $page['title'] : '',
                    'Required' => true
                ]
            );
            $group.= input('basic', $type_form,
                [
                    'size' => 'col-12',
                    'label' => 'URL da página',
                    'Placeholder' => 'Ex: checkout',
                    'name' => 'slug',
                    'Value' => ($type_form=='update') ? $page['slug'] : '',
                    'Required' => true
                ]
            );

            $group.= input('selection_type', $type_form,
                [
                    'size' => 'col-sm-6',
                    'label' => 'Tipo',
                    'name' => 'page_area',
                    'Options' => page_areas(true),
                    'Value' => ($type_form=='update') ? $page['page_area'] : '',
                    'Required' => true
                ]
            );

            $group.= input('selection_type', $type_form,
                [
                    'type' => 'search',
                    'size' => 'col-sm-6 hide-content-select-file" id="model-div"',
                    'label' => 'Usar modelo',
                    'name' => 'page_template',
                    'Options' => load_area_custom_pages(),
                    'Value' => ($type_form=='update') ? $page['page_template'] : '',
                ]
            );

            $group.= input('status_selector', $type_form,
                [
                    'size' => 'col-sm-6',
                    'function_proccess' => 'general_status',
                    'name' => 'status_page_id',
                    'input_id' => 'status_page_id',
                    'Value' => ($type_form=='update') ? $page['status_id'] : '4',
                    'Required' => true
                ]
            );

            $group.= input('selection_type', $type_form,
                [
                    'size' => 'col-sm-6',
                    'type' => 'search',
                    'label' => 'Página Dependente',
                    'name' => 'parent_page_id',
                    'input_id' => 'parent_page_id',
                    'Query' => "SELECT id as value, CONCAT(title, ' - ', page_area) as display FROM tb_pages ORDER BY page_area, title ASC",
                    'Options' => [['value' => 0, 'display' => 'Não depende de outra página']],
                    'Value' => ($type_form=='update') ? $page['parent_page_id'] : '',
                ]
            );

            $group.= '<div class="form-group col-12"><button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#pages-custom">URLs customizadas</button></div>';

            echo $group;
            ?>
        </div>
        </div>
        </div>


        <div class="card box-fields piece-controls">
        <div class="card-header">
            <h6 class="mb-0">Permissões</h6>
        </div>
        <div class="card-body">
        <div class="form-row">
            <?php
            $group = input('selection_type', $type_form,
                [
                    'type' => 'select',
                    'size' => 'col-12"',
                    'label' => 'Tipo de página',
                    'name' => 'page_type',
                    'Value' => ($type_form=='update') ? $page['page_type'] : 'not_essential',
                    'Required' => true,
                    'Options' => [
                        [ 'value' => 'essential', 'display' => 'Essencial' ],
                        [ 'value' => 'not_essential', 'display' => 'Não essencial' ],
                        [ 'value' => 'article', 'display' => 'Artigo' ],
                        [ 'value' => 'landingpage', 'display' => 'Landing page' ],
                    ],
                ]
            );

            $group.= input('selection_type', $type_form,
                [
                    'type' => 'radio',
                    'size' => 'col-12',
                    'name' => 'permission_type',
                    'Options' => [
                        [ 'value' => 'only_these', 'display' => 'Liberado apenas para esses' ],
                        [ 'value' => 'except_these', 'display' => 'Exclua apenas esses' ],
                    ],
                    'Value' => ($type_form=='update') ? $page['permission_type'] : 'except_these',
                ]
            );
            $group.= input('selection_type', $type_form,
                [
                    'type' => 'checkbox',
                    'size' => 'col-md-6',
                    'label' => 'Liberar para:',
                    'name' => 'allowed[]',
                    'Query' => 'SELECT id as value, name as display FROM tb_user_roles',
                    'Value' => ($type_form=='update') ? get_results("SELECT role_id as value FROM tb_user_role_permissions WHERE page_id = '{$id}' AND allowed = 1") : null,
                    'Required' => true
                ]
            );


            if (($type_form=='update') AND ($page['page_area'] == 'admin'))
            {
                $group.= input('hidden', $type_form,
                    [
                        'name' => 'is_public',
                        'Value' => ($type_form=='update') ? $page['is_public'] : ''
                    ]
                );
            }

            else
            {
                $group.= input('selection_type', $type_form,
                    [
                        'type' => 'switch',
                        'size' => 'col-md-6"',
                        'label' => 'É público',
                        'name' => 'is_public',
                        'Options' => [[ 'value' => 1, 'display' => 'Sim' ]],
                        'Value' => ($type_form=='update') ? $page['is_public'] : '0',
                        'Required' => true
                    ]
                );
            }

            echo $group;
            ?>
        </div>
        </div>
        </div>


        <?= block('modal', [
            'id' => 'pages-custom',
            'size' => 'lg',
            'title' => 'URLs customizadas',
            'close_button' => true,
            'body' => addable_content(
                [
                    'title' => 'Adicione',
                    'type_form' => $type_form,
                    'counter' => $counter,
                    'function' => "inputs_for_custom_url",
                    'content' => ($type_form=='update') ? $page['custom_urls'] : null,
                    'type' => 'custom_url'
                ]
            ),
        ]) ?>

    </div><!--/.col-lg-3 -->


    <?php
    if ($type_form == 'update') {
        echo input('hidden', $type_form, [ 'name' => 'id', 'input_id' => 'id', 'Value' => $id ]);
    }

    echo input('hidden', $type_form, [ 'name' => 'delete_items' ]);
    ?>

    </div>
    </form>

    <?php

    return $counter;

    unset($_SESSION['FormData']);
}



/**
 * Manage page data in a content management system.
 *
 * @param array $data - An array containing page data.
 * @param string $mode - The mode of operation, either 'insert' or 'update'.
 * @param bool $debug - A flag for enabling debugging (default is false).
 *
 * @return array - An array containing status information.
 */
function manage_page_system(array $data, string $mode, bool $debug = false)
{
    $error        = false;
    $msg          = '';
    $valid_data   = $data;

    $msg_type = 'toast';


    /*
     * Define the verifyer function.
     */
    if     ($mode == 'insert') $verifyer = 'inserted_id';
    elseif ($mode == 'update') $verifyer = 'affected_rows';
    else                       $error    = true;

    // Verify If there's an error
    if ($error) :
        $_SESSION['FormData'] = $data;

    // Else do the routine
    else :

        if (!isset($valid_data['page_area']))       $valid_data['page_area'] = 'admin';
        if ($valid_data['page_area'] == 'admin')    $valid_data['is_public'] = 0;

        if (!empty($valid_data['seo']['image']))
        {
            $seo_image = $valid_data['seo']['image'];

            if (is_json($seo_image)) {
                $fileName = json_decode($seo_image, true);
                $fileName = !empty($fileName) ? $fileName[0] : null;
            }
            elseif (is_array($seo_image)) $fileName = $seo_image[0];
            else $fileName = $seo_image;

            $valid_data['seo']['image'] = $fileName ?? null;
        }

        $args = [
            'title'           => $valid_data['title'] ?? '',
            'slug'            => !empty($valid_data['slug']) ? sanitize_string($valid_data['slug']) : '',
            'page_settings'   => $valid_data['page_settings'] ?? [],
            'seo'             => $valid_data['seo'] ?? [],
            'page_type'       => $valid_data['page_type'] ?? '',
            'is_public'       => $valid_data['is_public'] ?? 0,
            'parent_page_id'       => $valid_data['parent_page_id'] ?? 0,
            'page_area'       => $valid_data['page_area'] ?? '',
            'custom_urls'      => $valid_data['custom_urls'] ?? '',
            'page_template'   => $valid_data['page_template'] ?? 'common.php',
            'access_count'    => $valid_data['access_count'] ?? 0,
            'status_id'       => $valid_data['status_page_id'] ?? 3,
            'permission_type' => $valid_data['permission_type'] ?? '',
        ];

        if ($mode == 'insert') {
            $args['created_at'] = 'NOW()';
        }
        else if ($mode == 'update')
        {
            $args['updated_at'] = 'NOW()';
            $args['data']  = $args;
            $args['where'] = where_equal_id($valid_data['id']);
        }

        // Lights, camera & action.
        $mode('tb_pages', $args, $debug);


        /**
         *
         * Verify if inserted/updated correctaly.
         *
         */
        if ($verifyer()) :

            $page_id = ($mode == 'insert') ? inserted_id() : $valid_data['id'];
            unset($_SESSION['FormData']);


            /**
             *
             * Upload the background image.
             *
             */
            if (!empty($args['seo']['image']))
            {
                $image = is_json($args['seo']['image'])
                    ? json_decode($args['seo']['image'], true)
                    : $args['seo']['image'];

                $image = (is_array($image) && isset($image[0]))
                    ? $image[0]
                    : $image;


                if (!empty($image))
                {
                    $args['seo']['image'] = $image;

                    $pending_moves[] =
                    [
                        'temp_dir'   => TEMP_FILES_FOLDER,
                        'dest_base'  => 'uploads/images/seo/',
                        'files'      => [$image],
                        'is_update'  => ($mode === 'update'),
                        'field'      => 'image',
                        'type'       => 'images',
                    ];
                }

                else $args['seo']['image'] = null;
            }


            /**
             *
             * Run the routine of the modules
             *
             */
            $Modules   = isset($valid_data['Modules']) ? $valid_data['Modules'] : [];
            $order_reg = 1;

            $updated_order = [];
            foreach ($Modules as $Module)
            {
                $args_bd = [];


                /*
                 * Treatment of TypeModule
                 */
                $TypeModule = $Module['TypeModule'];
                unset($Module['TypeModule']);


                /**
                 *
                 * Run the module content routine
                 *
                 */
                if (isset($Module['contents']) && is_array($Module['contents']))
                {
                    foreach ($Module['contents'] as $arr_key => $Content)
                    {
                        $args_module = [];


                        if (!empty($Content['image']))
                        {
                            $image = is_json($Content['image'])
                                ? json_decode($Content['image'], true)
                                : $Content['image'];

                            $image = (is_array($image) && isset($image[0]))
                                ? $image[0]
                                : $image;


                            if (!empty($image))
                            {
                                $Content['image'] = $image;

                                $pending_moves[] =
                                [
                                    'temp_dir'   => TEMP_FILES_FOLDER,
                                    'dest_base'  => 'uploads/images/modules/',
                                    'files'      => [$image],
                                    'is_update'  => ($mode === 'update'),
                                    'field'      => 'image',
                                    'type'       => 'images',
                                ];
                            }

                            else $Content['image'] = null;
                        }


                        /**
                         *
                         * This compile the content's datas.
                         *
                         */
                        foreach ($Content as $label => $value)
                        {
                            if ($label == 'size' AND is_array($value))
                            {
                                $args_module[$label] = implode(' ', $value);
                                continue;
                            }

                            $args_module[$label] = (is_array($value) OR is_object($value))
                                ? json_encode($value)
                                : $value;
                        }

                        $args_bd['contents'][] = filter_empty_values($args_module);
                    }

                    // Encode the Module content
                    $contents = json_encode($args_bd['contents']);
                }

                // Depending of the context, just put the content straight.
                else $contents = isset($Module['contents']) ? $Module['contents'] : '';


                /**
                 *
                 * Upload the background image.
                 *
                 */
                if (!empty($Module['background']['image']))
                {
                    $image = is_json($Module['background']['image'])
                        ? json_decode($Module['background']['image'], true)
                        : $Module['background']['image'];

                    $image = (is_array($image) && isset($image[0]))
                        ? $image[0]
                        : $image;


                    if (!empty($image))
                    {
                        $Module['background']['image'] = $image;

                        $pending_moves[] =
                        [
                            'temp_dir'   => TEMP_FILES_FOLDER,
                            'dest_base'  => 'uploads/images/modules/',
                            'files'      => [$image],
                            'is_update'  => ($mode === 'update'),
                            'field'      => 'image',
                            'type'       => 'images',
                        ];
                    }

                    else $Module['background']['image'] = null;
                }


                /**
                 *
                 * This compile the module's datas.
                 *
                 */
                foreach ($Module as $key => $value)
                {
                    if ($key == 'status_id' OR (isset($upload_bd) AND $key == 'image')) continue;
                    if ($key == 'size' AND is_array($value))
                    {
                        $args_bd[$key] = implode(' ', $value);
                        continue;
                    }
                    $args_bd[$key] = (is_array($value) OR is_object($value)) ? json_encode($value) : $value;
                }


                /**
                 *
                 * Group by settings.
                 *
                 */
                $res = filter_empty_values($args_bd);
                unset($res['contents'], $args_bd);
                unset($res['id']);


                /**
                 *
                 * Add the args.
                 *
                 */
                $args_bd['contents']         = $contents;
                $args_bd['settings']         = json_encode($res);
                $args_bd['page_id']          = $page_id;
                $args_bd['crud_id']          = $res['crud_id'] ?? null;
                $args_bd['order_reg']        = $order_reg;
                $args_bd['TypeModule']       = $TypeModule;
                $args_bd['subscribers_only'] = $Module['subscribers_only'] ?? '';
                $args_bd['status_id']        = $Module['status_id'] ?? 1;
                unset($res['crud_id']);

                // print_r($args_bd);
                // exit;

                /**
                 *
                 * Verify If insert a new module.
                 *
                 */
                if ($mode == 'insert'
                    OR empty($Module['id'])
                ) {
                    insert('tb_page_content', $args_bd, $debug);
                    $current_id = inserted_id();
                }

                /**
                 *
                 * OR edit an existent.
                 *
                 */
                else
                {
                    $args_bd['data']       = $args_bd;
                    $args_bd['where']      = where_equal_id($Module['id']);

                    update('tb_page_content', $args_bd, true, false);
                        // print_r($args_bd);
                    $current_id = $Module['id'];
                }

                $updated_order[] = ['id' => $current_id, 'depth' => $depth ?? 0];


                /**
                 *
                 * Move images
                 *
                 */
                if (!empty($pending_moves))
                {
                    foreach ($pending_moves as $mv)
                    {
                        if (empty($mv['files'])) continue;

                        $dest = rtrim($mv['dest_base'], '/').'/';

                        if (!is_dir($dest)) mkdir($dest, 0755, true);

                        foreach ($mv['files'] as $fname)
                        {
                            if (!$fname) continue;

                            // SEM subpastas no temp — origem direta:
                            $origin = rtrim($mv['temp_dir'], '/')."/{$fname}";
                            $target = $dest . $fname;

                            if (file_exists($origin)) {
                                @rename($origin, $target);
                            }
                        }
                    }
                }

                $order_reg++;
            }


            /**
             *
             * Insert the allowed levels
             *
             */
            feature('permissions-management');
            update_permissions([
                'allowed' => $valid_data['allowed'] ?? [],
                'parent_id' => $page_id,
                'type' => 'page',
            ], false);


            /**
             *
             * Delete the wished modules if is updating.
             *
             */
            if ($mode == 'update' AND !empty($valid_data['delete_items']))
            {
                $delete_items = explode( '-', $valid_data['delete_items'] );
                foreach ($delete_items as $delete_field) {
                    query_it("DELETE FROM tb_page_content WHERE id = '{$delete_field}'");
                }
            }

            /*
             * Regenerate the sitemap.xml
             */
            generate_sitemap();

            $msg .= alert_message("SC_TO_". strtoupper($mode), $msg_type);

        else :
            $_SESSION['FormData'] = $data;
            $msg .= alert_message("ER_TO_". strtoupper($mode), $msg_type);
        endif;

    endif;

    $res = [
        'code' => !$error ? 'success' : 'error',
        'detail' => [
            'type' => $msg_type,
            'msg' => $msg,
        ],
    ];

    $res['updated_items_order'] = $updated_order ?? [];

    return $res;
}
