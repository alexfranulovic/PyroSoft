<?php
if(!isset($seg)) exit;

/**
 * This file contains functions for generating and managing pages, as well as functions for generating and managing CRUD operations.
 *
 * Functions related to pages include the creation of banners, modules, content, etc. These functions aim to facilitate
 * the generation and management of various page elements within coding.
 *
 * Functions related to CRUD operations are involved in management of CRUDs.
 * These functions provide essential functionality for interacting with a database or data storage, allowing for seamless
 * management of records and entities.
 *
 */


/*
 * Common functions.
 *
 * It is not recommended to you updating or custom this file.
 *
 */
global $allowed_mime_types,
       $inputs,
       $type_items,
       $routes_to_choose,
       $pages_to_choose;

feature('fields-card');


/**
 *
 * APIs to choose.
 *
 */
load_rest_api_options();

$routes_to_choose = [];
foreach (all_rest_api_routes_available() as $routes)
{
    $routes_to_choose[] = [ 'value' => $routes, 'display' => $routes ];
}


/**
 *
 * Pages to choose.
 *
 */
$pages_to_choose = get_pages_for_select();


/**
 *
 * Load inputs
 *
 */
// load_input();
load_input('all', 'structure-settings');

$type_items = [
    'crud' =>
    [
        '' => $inputs,
        'Visual' => [
            'hr' => [ 'icon' => 'fas fa-divide', 'name' => 'Linha divisória' ],
            'break_line' => [ 'icon' => 'fas fa-arrow-turn-down', 'name' => 'Quebra de linha' ],
            'field_repeater' => [ 'icon' => 'fas fa-list-ul', 'name' => 'Repetidor de campos' ],
            'shortcode' => [ 'icon' => 'fas fa-code', 'name' => 'Shortcode' ],
            'divider' => [ 'icon' => 'fas fa-shoe-prints', 'name' => 'Divisória' ],
        ],
    ],
    'page' =>
    [
        'Componentes' => [
            'vertical_text' => [ 'icon' => 'fas fa-grip-lines-vertical', 'name' => 'Texto vertical' ],
            'floating_card' => [ 'icon' => 'fas fa-ghost', 'name' => 'Card flutuante', 'w_text_editor' => true ],
            'modal' => [ 'icon' => 'fas fa-comments', 'name' => 'Modal', 'w_text_editor' => true],
            'fixed_message' => [ 'icon' => 'fas fa-sticky-note', 'name' => 'Mensagem fixa', 'w_text_editor' => true ],
            'toast' => [ 'icon' => 'fas fa-comments', 'name' => 'Toast', 'w_text_editor' => true],
        ],
        'Módulos' => [
            'custom' => [ 'icon' => 'fas fa-edit', 'name' => 'Editor de texto', 'w_text_editor' => true ],
            'carousel' => [ 'icon' => 'fas fa-images', 'name' => 'Carousel', 'w_content' => true ],
            'accordion' => [ 'icon' => 'fas fa-bars', 'name' => 'Accordion', 'w_content' => true ],
            'breadcrumbs' => [ 'icon' => 'fas fa-route', 'name' => 'Barra de navegação' ],
            'huge_button' => [ 'icon' => 'fas fa-mouse', 'name' => 'Botão Grande' ],
            'hero' => [ 'icon' => 'fas fa-mask', 'name' => 'Banner Tela Cheia', 'w_text_editor' => true ],
            'videos' => [ 'icon' => 'fas fa-play', 'name' => 'Vídeos', 'w_content' => true ],
            'crud' => [ 'icon' => 'fas fa-th-list', 'name' => 'Fragmento de CRUD' ],
            'cards' => [ 'icon' => 'fas fa-file-alt', 'name' => 'Cards', 'w_content' => true ],
            'gallery' => [ 'icon' => 'fas fa-photo-video', 'name' => 'Galeria', 'w_content' => true ],
            'regressive_counter' => [ 'icon' => 'fas fa-stopwatch', 'name' => 'Contador Regressivo' ],
            'posts_showcase' => [ 'icon' => 'fas fa-newspaper', 'name' => 'Postagens e artigos', 'w_content' => true ],
            'comments' => [ 'icon' => 'fas fa-comments', 'name' => 'Comentários', 'w_content' => true ],
            'numbers' => [ 'icon' => 'fas fa-medal', 'name' => 'Números', 'w_content' => true ],
            'horizontal_list' => [ 'icon' => 'fas fa-grip-horizontal', 'name' => 'Lista Horizontal', 'w_content' => true ],
            'vertical_list' => [ 'icon' => 'fas fa-list-ul', 'name' => 'Lista Vertical', 'w_content' => true ],
        ],
    ],
];


/**
 * Select and call the appropriate function to generate inputs based on the type of field.
 *
 * @param string $type_item - The type of field for which inputs need to be generated.
 * @param string $type_form - The type of form, e.g., 'insert' or 'update'.
 * @param mixed $counter - A counter or identifier for the input fields.
 * @param array $data - An array containing default values for input attributes (optional).
 *
 * @return mixed - The result of the specific input-generating function called based on the $type_item.
 */
function inputs_select_type(string $type_item, string $type_form, $counter = 1, array $data = [])
{
    if (empty($type_item)) return 'No selected type.';

    $function = "inputs_for_$type_item";
    return $function($type_form, $counter, $data);
}


function add_field_model($Attr = [])
{
    if (empty($Attr)) return '';

    $counter = $Attr['counter'] ?? 0;
    $item_id = $Attr['item_id'];
    $mode = $Attr['mode'];

    if ($mode == 'crud')
    {
        $item = get_result("SELECT * FROM tb_cruds_fields WHERE id = '{$item_id}'");

        $item = (array) $item['settings'] + $item;
        unset($item['id'], $item['settings']);

        $questions = inputs_select_type($item['type_field'], 'update', $counter, $item);

        $box = field_content_card(
            'update',
            [
                'delete' => true,
                'move' => true,
                'label' => $item['name'] ?? $item['type_field'],
                'type' => $item['type_field'],
                'counter' => $counter,
                'questions' => $questions,
                'id' => $item['id'] ?? null,
            ]
        );
    }

    elseif ($mode == 'page')
    {
        $item = get_result("SELECT * FROM tb_page_content WHERE id = '{$item_id}'");

        $item = (array) $item['settings'] + $item;
        unset($item['id'], $item['settings']);

        $questions = inputs_select_type($item['TypeModule'], 'update', $counter, $item);

        $box = field_content_card(
            'update',
            [
                'delete' => true,
                'move' => true,
                'label' => $item['name'] ?? $item['TypeModule'],
                'type' => $item['TypeModule'],
                'counter' => $counter,
                'questions' => $questions,
                'id' => $item['id'] ?? null,
            ]
        );
    }

    return $box ?? '';
}


function dropdown_content(array $Attr)
{
    $attr = $Attr['attr'] ?? '';
    $color = $Attr['color'] ?? 'outline-secondary';
    $size = $Attr['size'] ?? 'col-lg';

    return "
    <fieldset class='$size' $attr>
    <div class='dropdown'>
    <button class='btn btn-$color dropdown-toggle' type='button' data-bs-toggle='dropdown' aria-expanded='false' data-bs-auto-close='outside'>
        {$Attr['title']}
    </button>
    <div class='dropdown-menu'>
        {$Attr['content']}
    </div>
    </div>
    </fieldset>";
}


function sizes_options(string $size = 'lg')
{
    return [
        [ 'value' => "col-$size", 'display' => 'AUTO' ],
        [ 'value' => "col-$size-3", 'display' => '3' ],
        [ 'value' => "col-$size-4", 'display' => '4' ],
        [ 'value' => "col-$size-5", 'display' => '5' ],
        [ 'value' => "col-$size-6", 'display' => '6' ],
        [ 'value' => "col-$size-7", 'display' => '7' ],
        [ 'value' => "col-$size-8", 'display' => '8' ],
        [ 'value' => "col-$size-9", 'display' => '9' ],
        [ 'value' => "col-$size-12", 'display' => 'FULL' ],
        [ 'value' => "hidden-only-$size", 'display' => 'HIDE' ],
    ];
}


/**
 * Get form setting options.
 *
 * @return array - An array of form setting options.
 */
function background_settings(string $type_form, $data = null, $counter = 1, $module_counter = null)
{
    $is_array = ($module_counter != null) ? "[contents][$module_counter]" : '';
    $name     = "Modules[{$counter}]{$is_array}[background]";

    // Selector
    $res = "<div class='row p-3'>";
    $res.= input(
        'selection_type',
        $type_form,
        [
            'type' => 'radio',
            'size' => 'col-md',
            'label' => 'Tipo:',
            'name' => "{$name}[type]",
            'input_id' => "type-$counter",
            'Value' => $data['type'] ?? '',
            'Required' => true,
            'Options' => [
                [
                    'value' => 'image',
                    'attributes' => "data-background-custom:($counter);",
                    'display' => 'Imagem',
                ],
                [
                    'value' => 'color',
                    'attributes' => "data-background-custom:($counter);",
                    'display' => 'Cor fixa',
                ],
            ],
        ]
    );
    $res.= input(
        'basic',
        $type_form,
        [
            'size' => 'col-md',
            'label' => 'Efeito do background',
            'Placeholder' => 'Subtítulo do Módulo',
            'name' => "{$name}[effect]",
            'input_id' => "effect-$counter",
            'Value' => $data['effect'] ?? '',
        ]
    );
    $res.= "</div>";


    // For: Color
    $res.= "<div class='row p-3'>";
    $res.= input(
        'selection_type',
         $type_form,
        [
            'size' => 'col-md',
            'label' => 'Cor do background',
            'name' => "{$name}[color]",
            'input_id' => "color-$counter",
            'Options' => theme_background_colors(true),
            'Value' => $data['color'] ?? '',
        ]
    );
    $res.= "</div>";


    // For: Image
    $res.= "<div class='row p-3'>";
    $res.= common_inputs_for_page($type_form, 'image_folder', 'modules', $counter);
    $res.= input(
        'upload',
        $type_form,
        [
            'type' => 'images',
            'size' => 'col-md',
            'label' => 'Imagem',
            'attributes' => 'accept:(image/*);',
            'name' => "{$name}[image]",
            'input_id' => "image-$counter",
            'Value' => $data['image'] ?? '',
            'Src' => 'modules',
            // 'Required' => true,
        ]
    );
    $res.= input(
        'selection_type',
         $type_form,
        [
            'size' => 'col-md',
            'label' => 'Modo conteúdo',
            'name' => "{$name}[mode]",
            'input_id' => "mode-$counter",
            'Options' => [
                [ 'display' => 'Branco', 'value' => 'light' ],
                [ 'display' => 'Cinza', 'value' => 'dark' ],
            ],
            'Value' => $data['mode'] ?? '',
        ]
    );
    $res.= "</div>";

    $counter  = "background-$counter". ($module_counter != null ? "-$module_counter" : '');

    return dropdown_content( ['size' => 'col-md-6','title' => 'Background', 'content' => $res] );
}



/**
 * Get form setting options.
 *
 * @return array - An array of form setting options.
 */
function background_settings_content(string $type_form, $data = null, $counter = 1, $module_counter = null)
{
    $is_array = ($module_counter != null) ? "[contents][$module_counter]" : '';
    $name     = "Modules[{$counter}]{$is_array}";

    $res = "<div class='row pt-3'>";
    $res.= input(
        'selection_type',
         $type_form,
        [
            'size' => 'col-md',
            'label' => 'Cor do background',
            'name' => "{$name}[color]",
            'input_id' => "color-$counter",
            'Options' => theme_background_colors(true),
            'Value' => $data['color'] ?? '',
        ]
    );
    $res.= input(
        'basic',
        $type_form,
        [
            'size' => 'col-md',
            'label' => 'Efeito do background',
            'Placeholder' => 'Subtítulo do Módulo',
            'name' => "{$name}[effect]",
            'input_id' => "effect-$counter",
            'Value' => $data['effect'] ?? '',
        ]
    );
    $res.= "</div>";

    $counter  = "background-$counter". ($module_counter != null ? "-$module_counter" : '');

    return dropdown_content( ['size' => 'col-md-6','title' => 'Background do conteúdo', 'content' => $res] );
}


/**
 * Offer settings for the footer.
 *
 * @return array - An array of form setting options.
 */
function footer_settings(string $type_form, $data = null, $counter = 1, $module_counter = null)
{
    $is_array = ($module_counter != null) ? "[contents][$module_counter]" : '';
    $name     = "Modules[{$counter}]{$is_array}[footer]";

    $counter  = "button-$counter". ($module_counter != null ? "-$module_counter" : '');

    $res = "<div class='row p-3'>";
    $res.= input(
        'selection_type',
        $type_form,
        [
            'type' => 'radio',
            'size' => 'col-md-6',
            'label' => 'Modo conteúdo',
            'name' => "{$name}[mode]",
            'input_id' => "mode-$counter",
            'Value' => $data['mode'] ?? '',
            'Required' => true,
            'Options' => [
                [ 'value' => 'link_button', 'display' => 'Link/Botão' ],
                [ 'value' => 'custom', 'display' => 'Custom' ],
            ],
        ]
    );
    $res.= input(
        'selection_type',
        $type_form,
        [
           'size' => 'col-md-6',
           'label' => 'Alinhamento',
           'name' => "{$name}[align]",
           'input_id' => "align-$counter",
           'Options' => "
                1|| start|| Esquerda;
                2|| center|| Centro;
                3|| end|| Direita;",
           'Value' => $data['align'] ?? '',
       ]
    );
    $res.= "</div>";

    $res.= "<div class='row p-3'>";
    $res.= input(
        'textarea',
        $type_form,
        [
            'size' => 'col-12',
            'label' => 'Conteúdo',
            'attributes' => 'rows:(4);',
            'name' => "{$name}[content]",
            'input_id' => "content-$counter",
            'Value' => $data['content'] ?? '',
            'text_editor' => 1,
        ]
    );
    $res.= "
    </div>";

    $res.= "<div class='row p-3'>";
    $res.= button_settings($type_form, $name, $data['link_button'] ?? '', $counter, $module_counter );
    $res.= "</div>";

    return dropdown_content( ['title' => 'Footer', 'content' => $res] );
}



/**
 * Offer settings for the button.
 *
 * @return array - An array of form setting options.
 */
function button_settings(string $type_form, string $name, $data = null, $counter = 1, $module_counter = null)
{
    $counter  = "button-$counter". ($module_counter != null ? "-$module_counter" : '');

    $res = input(
        'selection_type',
        $type_form,
        [
            'type' => 'radio',
            'size' => 'col-md-6 col-xl-4',
            'label' => 'Tipo do link:',
            'name' => "{$name}[link_button][type]",
            'input_id' => "type-$counter",
            'Value' => $data['type'] ?? '',
            'Required' => true,
            'Options' => [
                [
                    'value' => 'page',
                    'attributes' => "data-url-custom:($module_counter); data-name:({$name}[link_button]);'",
                    'display' => 'Página própria',
                ],
                [
                    'value' => 'custom',
                    'attributes' => "data-url-custom:($module_counter); data-name:({$name}[link_button]);'",
                    'display' => 'Peronalizado',
                ],
            ],
        ]
    );
    $res.= "<div class='form-group col-md-6 col-xl-4' data-link>". common_inputs_for_custom_urls($type_form, $data['type'] ?? '', $data['url'] ?? '', $module_counter, "{$name}[link_button]") ."</div>";
    $res.= input(
        'basic',
        $type_form,
        [
            'size' => 'col-md-6 col-xl-4',
            'label' => 'Atributos',
            'Placeholder' => 'Use [id] para puxar o ID via GET ou variável',
            'name' => "{$name}[link_button][attr]",
            'input_id' => "attr-$module_counter",
            'Value' => $data['attr'] ?? '',
        ]
    );
    $res.= hr();
    $res.= input(
        'selection_type',
        $type_form,
        [
            'type' => 'radio',
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Estilo do botão',
            'name' => "{$name}[link_button][style]",
            'input_id' => "style-$counter",
            'Value' => $data['style'] ?? '',
            'Options' => [
                [ 'value' => 'link', 'display' => 'Link' ],
                [ 'value' => 'button', 'display' => 'Botão' ],
            ],
        ]
    );
    $res.= input(
        'basic',
        $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Texto do botão',
            'Placeholder' => "Pré-definido como 'Ver mais'",
            'name' => "{$name}[link_button][title]",
            'input_id' => "title-$counter",
            'Value' => $data['title'] ?? '',
        ]
    );
    /*$res.= input(
        'basic',
        $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Icone do botão',
            'Placeholder' => "Escolha o ìcone do botão",
            'name' => "{$name}[link_button][icon]",
            'input_id' => "icon-$counter",
            'Value' => $data['icon'] ?? '',
        ]
    );
    /*$res.= input(
        'basic',
        $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Link do botão',
            'type' => 'url',
            'Placeholder' => 'seulink.com',
            'name' => "{$name}[link_button][url]",
            'input_id' => "url-$counter",
            'Value' => $data['url'] ?? '',
        ]
    );*/

    $res.= input(
        'selection_type',
         $type_form,
        [
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Cor do botão',
            'name' => "{$name}[link_button][color]",
            'input_id' => "color-$counter",
            'Options' => theme_button_colors(true),
            'Value' => $data['color'] ?? '',
        ]
    );
    $res.= input(
        'selection_type',
        $type_form,
        [
            'type' => 'switch',
            'size' => 'col-md-6 col-lg-4',
            'name' => "{$name}[link_button][animations]",
            'input_id' => "animations-$counter",
            'Options' => "2|| 1|| Animações;",
            'Value' => $data['animations'] ?? '',
        ]
    );

    return $res;
}



/**
 * Get form setting options.
 *
 * @return array - An array of form setting options.
 */
function sizes_selector(string $type_form, string $name = 'Fields', $data = null, $counter = 1, $module_counter = null)
{
    $data     = explode(' ', $data);
    $is_array = ($module_counter != null) ? "[contents][$module_counter]" : '';
    $name     = "{$name}[{$counter}]{$is_array}[size]";

    $res = "<div class='row p-3'>";
    $res.= input(
        'selection_type',
        $type_form,
        [
            'variation' => 'btn-group',
            'type' => 'radio',
            'size' => 'col-md-6',
            'label' => 'XS',
            'name' => "{$name}[xs]",
            'input_id' => "size-$counter",
            'Options' => sizes_options('xs'),
            'Value' => $data,
        ]
    );
    $res.= input(
        'selection_type',
        $type_form,
        [
            'variation' => 'btn-group',
            'type' => 'radio',
            'size' => 'col-md-6',
            'label' => 'SM',
            'name' => "{$name}[sm]",
            'input_id' => "size-$counter",
            'Options' => sizes_options('sm'),
            'Value' => $data,
        ]
    );
    $res.= input(
        'selection_type',
        $type_form,
        [
            'variation' => 'btn-group',
            'type' => 'radio',
            'size' => 'col-md-6',
            'label' => 'MD',
            'name' => "{$name}[md]",
            'input_id' => "size-$counter",
            'Options' => sizes_options('md'),
            'Value' => $data,
        ]
    );
    $res.= input(
        'selection_type',
        $type_form,
        [
            'variation' => 'btn-group',
            'type' => 'radio',
            'size' => 'col-md-6',
            'label' => 'LG',
            'name' => "{$name}[lg]",
            'input_id' => "size-$counter",
            'Options' => sizes_options('lg'),
            'Value' => $data,
        ]
    );
    $res.= input(
        'selection_type',
        $type_form,
        [
            'variation' => 'btn-group',
            'type' => 'radio',
            'size' => 'col-md-6',
            'label' => 'XL',
            'name' => "{$name}[xl]",
            'input_id' => "size-$counter",
            'Options' => sizes_options('xl'),
            'Value' => $data,
        ]
    );
    $res.= input(
        'selection_type',
        $type_form,
        [
            'variation' => 'btn-group',
            'type' => 'radio',
            'size' => 'col-md-6',
            'label' => 'XXL',
            'name' => "{$name}[xxl]",
            'input_id' => "size-$counter",
            'Options' => sizes_options('xxl'),
            'Value' => $data,
        ]
    );
    $res.= "</div>";

    $counter = "size-$counter". ($module_counter != null ? "-$module_counter" : '');

    return dropdown_content( ['size' => 'col-md-6','title' => 'Tamanhos e exibição', 'content' => $res] );
}


/**
 * Get inputs for custom pages.
 *
 * @return array - An array of custom pages.
 */
function common_inputs_for_custom_urls(string $type_form, string $selector, $data = null, $module_counter = 1, string $name = '')
{
    $name = !empty($name) ? $name : "custom_urls[$module_counter]";

    if ($selector == 'custom')
    return input(
        'basic',
        $type_form,
        [
            'div_attributes' => 'class:();',
            'label' => 'URL',
            'type' => 'url',
            'Placeholder' => 'Ex: Home',
            'name' => "{$name}[url]",
            'input_id' => "url-$module_counter",
            'Value' => $data,
            'Required' => true,
            'attachment' => [ 'prepend' => 'https://' ]
        ]
    );

    if ($selector == 'page')
    return input(
        'selection_type',
        $type_form,
        [
            'div_attributes' => 'class:();',
            'label' => 'Usar modelo',
            'name' => "{$name}[url]",
            'input_id' => "url-$module_counter",
            'Value' => $data,
            'Query' => "SELECT id as value, CONCAT(title, ' - ', page_area) as display FROM tb_pages ORDER BY page_area, title ASC",
            'Required' => true
        ]
    );
}


function inputs_for_custom_url(string $type_form, $counter, $module_counter, array $data = [])
{
    if ($type_form == 'insert') $data = [ 'link' => null, 'type' => '' ];

    $res = '<div class="form-row">';
    $res.= input(
        'basic',
        $type_form,
        [
            'size' => 'col-md-6',
            'label' => 'Texto da URL',
            'Placeholder' => 'Ex: Home',
            'name' => "custom_urls[$module_counter][title]",
            'input_id' => "title-$module_counter",
            'Value' => !empty($data['title']) ? $data['title'] : '',
            'Required' => true
        ]
    );
    /*$res.= input(
        'basic',
        $type_form,
        [
            'size' => 'col-md-6',
            'label' => 'Ícone da URL',
            'Placeholder' => 'Ícone que representa a URL',
            'name' => "custom_urls[$module_counter][icon]",
            'input_id' => "icon-$module_counter",
            'Value' => !empty($data['icon']) ? $data['icon'] : '',
        ]
    );*/
    $res.= input(
        'selection_type',
         $type_form,
        [
            'size' => 'col-md-6',
            'label' => 'Cor do botão',
            'name' => "custom_urls[$module_counter][color]",
            'input_id' => "color-$module_counter",
            'Options' => theme_colors(true),
            'Value' => !empty($data['color']) ? $data['color'] : '',
            'Required' => true
        ]
    );
    $res.= input(
        'selection_type',
         $type_form,
        [
            'size' => 'col-md-6',
            'label' => 'Posição',
            'name' => "custom_urls[$module_counter][where]",
            'input_id' => "where-$module_counter",
            'Value' => !empty($data['where']) ? $data['where'] : '',
            'Required' => true,
            'Options' => [
                [ 'value' => 'in-panel',    'display' => 'Interna (painel)' ],
                [ 'value' => 'in-content',  'display' => 'Interna (conteudo)' ],
                [ 'value' => 'out-panel',   'display' => 'Externa (painel)' ],
                [ 'value' => 'out-content', 'display' => 'Externa (conteudo)' ],
            ],
        ]
    );
    $res.= input(
        'selection_type',
        $type_form,
        [
            'type' => 'radio',
            'size' => 'col-md-6 col-xl-4',
            'label' => 'Tipo do link:',
            'name' => "custom_urls[$module_counter][type]",
            'input_id' => "type-$module_counter",
            'Value' => $data['type'] ?? '',
            'Required' => true,
            'Options' => [
                [
                    'value' => 'page',
                    'attributes' => "data-url-custom:($module_counter); data-name:(custom_urls[$module_counter]);",
                    'display' => 'Página própria',
                ],
                [
                    'value' => 'custom',
                    'attributes' => "data-url-custom:($module_counter); data-name:(custom_urls[$module_counter]);",
                    'display' => 'Peronalizado',
                ],
            ],
        ]
    );
    $res.= "<div class='form-group col-md-6 col-xl-4' data-link>". common_inputs_for_custom_urls($type_form, $data['type'] ?? '', $data['url'] ?? '', $module_counter) ."</div>";
    $res.= input(
        'basic',
        $type_form,
        [
            'size' => 'col-md-6 col-xl-4',
            'label' => 'Atributos',
            'Placeholder' => 'Use [id] para puxar o ID via GET ou variável',
            'name' => "custom_urls[$module_counter][attr]",
            'input_id' => "attr-$module_counter",
            'Value' => $data['attr'] ?? '',
        ]
    );

    $res.= "</div>";

    return addable_content_input_group([ 'title' => 'URL', 'content' => $res, 'counter' => $counter, 'module_counter' => $module_counter ]);
}


/**
 * Output JavaScript for dynamically managing form fields and user interactions.
 *
 * @param $counter - An optional counter for form fields.
 */
function js_call_inputs(string $mode = '', $counter = 1)
{
    global $type_items,
           $routes_to_choose,
           $allowed_mime_types,
           $pages_to_choose;

    $allowed_mime_types_audios = implode(', ', $allowed_mime_types['audios']);
    $allowed_mime_types_archives = implode(', ', $allowed_mime_types['archives']);
    $allowed_mime_types_images = implode(', ', $allowed_mime_types['images']);
    $allowed_mime_types_videos = implode(', ', $allowed_mime_types['videos']);


    // Start a JavaScript block
    ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>


    <script>
    <?php
    if (empty($mode))
    {
        echo 'alert("No mode was selected in JS.");';
        return;
    }
    ?>

    const BASE_URL = window.BASE_URL;
    const REST_API_BASE_ROUTE = window.REST_API_BASE_ROUTE;


    // --------- Eventos globais do modal dinâmico (#edit-fields) ----------
    // Resetamos o histórico quando o modal abre/fecha (sem alterar open_message)
    (function bindGlobalModalHistoryResets(){
      document.addEventListener('shown.bs.modal', function(e){
        if (e.target && e.target.id === 'edit-fields' && typeof window.resetFieldsHistory === 'function') {
          window.resetFieldsHistory();
        }
      }, true);
      document.addEventListener('hidden.bs.modal', function(e){
        if (e.target && e.target.id === 'edit-fields' && typeof window.resetFieldsHistory === 'function') {
          window.resetFieldsHistory();
        }
      }, true);
    })();


    $(document).ready(function ()
    {
       const maxDepth = 2;

       <?php if ($mode == 'crud') : ?>
       var counter = <?= $counter ?>;
       <?php elseif ($mode == 'page') : ?>
       var counter = <?= $counter ?>;
       <?php endif; ?>

       /* ============================================
          UNDO / REDO - Snapshots do container
          (singleton global para evitar escopo quebrado)
          ============================================ */
       // Cria/recupera um namespace global para o histórico
       window.__fieldsHistory = window.__fieldsHistory || {
         undoStack: [],
         redoStack: []
       };
       const undoStack = window.__fieldsHistory.undoStack;
       const redoStack = window.__fieldsHistory.redoStack;

       function isTypingTarget(t) {
         const tag = t?.tagName?.toLowerCase?.() || '';
         const isFormEl = tag === 'input' || tag === 'textarea' || tag === 'select';
         const isCE = t && (t.isContentEditable || $(t).closest('[contenteditable="true"]').length > 0);
         return isFormEl || isCE;
       }

       function getActiveCardId() {
         const $active = $('.field-content-card.active');
         if (!$active.length) return null;
         const $idInput = $active.find('input[name$="[id]"]').first();
         if ($idInput.length && $idInput.val()) return `id:${$idInput.val()}`;
         const idx = $active.index();
         return (idx >= 0) ? `idx:${idx}` : null;
       }

       function restoreActiveByKey(key) {
         if (!key) return;
         let $target = $();
         if (key.startsWith('id:')) {
           const idVal = key.slice(3);
           $target = $('.field-content-card')
             .filter((_, el) => $(el).find('input[name$="[id]"]').first().val() == idVal)
             .first();
         } else if (key.startsWith('idx:')) {
           const idx = parseInt(key.slice(4), 10);
           if (!Number.isNaN(idx) && idx >= 0) $target = $('.field-content-card').eq(idx);
         }
         if ($target.length) {
           $('.field-content-card').removeClass('active');
           $target.addClass('active');
           setTimeout(() => $target[0]?.scrollIntoView({ behavior: 'smooth', block: 'center' }), 0);
         }
       }

       function snapshot(reason = '') {
         const $container = $('#field-container-card');
         return {
           reason,
           html: $container.html(),
           activeKey: getActiveCardId(),
           scrollTop: $container.scrollTop(),
           counter: typeof counter !== 'undefined' ? counter : null,
           deleteItems: $('#delete_items').val?.() ?? null
         };
       }

       function applySnapshot(snap) {
         const $container = $('#field-container-card');
         $container.html(snap.html);

         if (typeof counter !== 'undefined' && snap.counter !== null) {
           counter = snap.counter;
         }
         if ($('#delete_items').length && snap.deleteItems !== null) {
           $('#delete_items').val(snap.deleteItems);
         }

         restoreActiveByKey(snap.activeKey);
         $container.scrollTop(snap.scrollTop);

         updateHistoryButtons();
       }

       function pushUndo(snap) {
         undoStack.push(snap);
         // limite de histórico
         const HISTORY_LIMIT = 50;
         if (undoStack.length > HISTORY_LIMIT) undoStack.shift();
       }

       function commitChange(reason = '') {
         pushUndo(snapshot(reason));
         redoStack.length = 0;
         updateHistoryButtons();
       }

       function undo() {
         if (!undoStack.length) return;
         const current = snapshot('current');
         const prev = undoStack.pop();
         redoStack.push(current);
         applySnapshot(prev);
         updateHistoryButtons();
       }

       function redo() {
         if (!redoStack.length) return;
         const current = snapshot('current');
         const next = redoStack.pop();
         pushUndo(current);
         applySnapshot(next);
         updateHistoryButtons();
       }

       function updateHistoryButtons() {
         const $undoBtns = $('[data-history="undo"]');
         const $redoBtns = $('[data-history="redo"]');
         const undoDisabled = undoStack.length === 0;
         const redoDisabled = redoStack.length === 0;
         $undoBtns.prop('disabled', undoDisabled).attr('aria-disabled', undoDisabled ? 'true' : 'false');
         $redoBtns.prop('disabled', redoDisabled).attr('aria-disabled', redoDisabled ? 'true' : 'false');
       }

       function resetHistory() {
         undoStack.length = 0;
         redoStack.length = 0;
         updateHistoryButtons();
       }

       // expõe um reset global para os listeners fora do escopo
       window.resetFieldsHistory = resetHistory;

       // Atalhos globais Ctrl+Z / Ctrl+Y / Ctrl+Shift+Z
       $(document).on('keydown', function(e) {
         if (isTypingTarget(e.target)) return;
         const ctrl = e.ctrlKey || e.metaKey;
         if (!ctrl) return;

         if (e.key === 'z' || e.key === 'Z') {
           e.preventDefault();
           if (e.shiftKey) redo();
           else undo();
         } else if (e.key === 'y' || e.key === 'Y') {
           e.preventDefault();
           redo();
         }
       });

       // Botões de histórico
       $(document).on('click', '[data-history="undo"]', function(e) {
         e.preventDefault();
         if ($(this).prop('disabled')) return;
         undo();
       });
       $(document).on('click', '[data-history="redo"]', function(e) {
         e.preventDefault();
         if ($(this).prop('disabled')) return;
         redo();
       });

       // Estado inicial dos botões
       updateHistoryButtons();


       // ========== Add item ==========
       $(document).on('click', '[data-insert-model]', function(event)
       {
           var TypeItem = $(this).val();
           if (!TypeItem) return;

           <?php
           $if_counter = 0;
           foreach ($type_items[$mode] as $optgroup => $option)
           {
               foreach ($option as $value => $info)
               {
                   $function = "inputs_for_$value";
                   $res = "// {$info['name']}\n";
                   $res.= ($if_counter > 0) ? 'else ' : '';
                   $res.= "if (TypeItem == '$value') var questions = `". $function('insert', '`+counter+`') ."`; \n\n";
                   echo $res;
                   $if_counter++;
               }
           }
           ?>

           // label = `<font id='LabelName-` + counter + `'></font>`;
           label = ``;

           const $container = $('#field-container-card');

           // snapshot antes de inserir
           commitChange('insert:model');

           // monta como objeto para capturar o nó real inserido
           const markup = `
             <?= field_content_card(
               'insert',
               [
                 'delete'    => true,
                 'move'      => true,
                 'label'     => '`+label+`',
                 'type'      => '`+TypeItem+`',
                 'counter'   => '`+counter+`',
                 'questions' => '`+questions+`',
               ]
             ) ?>
           `;
           const $nodes = $(markup);

           // insere no DOM
           $container.append($nodes);

           // (mantém sua eventual inicialização de editor aqui)
           <?php
           // Setting the itens that require text editor and initializing them.
           foreach ($type_items[$mode] as $optgroup => $option)
           {
               foreach ($option as $value => $info)
               {
                   if (!isset($info['w_text_editor'])) continue;
                   $w_text_editor[] = $value;
                   $if_counter++;
               }
           }
           if (isset($w_text_editor))
           {
               $if_counter = 0;
               $if_text_editor = "if (";
               foreach ($w_text_editor as $value)
               {
                   $if_text_editor.= ($if_counter==0) ? "TypeItem == '$value'" : " || TypeItem == '$value'";
                   $if_counter++;
               }
               $if_text_editor.= ") text_editor();";
               echo $if_text_editor;
           }
           ?>

           function activateAndScroll() {
             const $newCard =
               $nodes.filter('.field-content-card').add($nodes.find('.field-content-card')).last();
             if (!$newCard.length) return;
             $('.field-content-card').removeClass('active');
             $newCard.addClass('active');
             const $firstInput = $newCard.find('input, select, textarea').filter(':visible:enabled').first();
             if ($firstInput.length) $firstInput.trigger('focus');
             $newCard[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
           }
           setTimeout(activateAndScroll, 0);
           setTimeout(activateAndScroll, 60);

           counter++;
       });


       // ========== Add module content ==========
       $(document).on('click', '.add-module-content', function(event)
       {
           var val      = $(this).val().split('-|-');
           var TypeItem = val[0];
           var counter  = val[1];

           if (!TypeItem) return;

           var module_form = $(this).parent().next().next().attr("id");
           var module_counter = $('#' + module_form).children('article').last().attr("id");

           if (module_counter == undefined) module_counter = 1;
           else
           {
               module_counter = module_counter.split('-');
               if (module_counter[3] == undefined && module_counter[2] != undefined) {
                   module_counter = parseInt(module_counter[2]) + 1;
               } else {
                   module_counter = parseInt(module_counter[3]) + 1;
               }
           }

           <?php
           $if_counter = 0;
           foreach ($type_items[$mode] as $optgroup => $option)
           {
               foreach ($option as $value => $info)
               {
                   if (isset($info['w_content']))
                   {
                       $function = "inputs_for_{$value}_content";
                       $res = "// {$info['name']}\n";
                       $res.= ($if_counter > 0) ? 'else ' : '';
                       $res.= "if (TypeItem == '$value') var content = `". $function('insert', '`+counter+`', '`+module_counter+`') ."`; \n\n";
                       echo $res;
                       $if_counter++;
                   }
               }
           }
           ?>

           // Custom URLs
           else if (TypeItem == 'custom_url') var content = `<?= inputs_for_custom_url('insert', '`+counter+`', '`+module_counter+`') ?>`;
           else var content = 'This content does not exist.';

           // snapshot antes de inserir conteúdo do módulo
           commitChange('insert:module-content');

           $('#'+ module_form).append(content);
           module_counter++;
       });


       <?php if ($mode == 'crud') : ?>

       // ========== Add model via AJAX ==========
       $(document).on('click', '[data-insert-model-id]', function(event)
       {
           let item = $(this);
           let item_id = item.attr('data-insert-model-id');

           $.ajax({
               url: `${BASE_URL}/${REST_API_BASE_ROUTE}/add-field-model`,
               type: 'POST',
               data: {
                   'item_id': item_id,
                   'counter': counter,
                   'mode': '<?= $mode ?>',
               },
               success: function (response) {
                   commitChange('insert:model-id');

                   const $container = $('#field-container-card');
                   $container.append(response);

                   const $newCard = $container.find('.field-content-card').last();
                   $('.field-content-card').removeClass('active');
                   $newCard.addClass('active');

                   setTimeout(() => {
                     const $firstInput = $newCard.find('input, select, textarea').filter(':visible:enabled').first();
                     if ($firstInput.length) $firstInput.trigger('focus');
                     $newCard[0]?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                   }, 0);
               }
           });

           counter++;
       });


       // ========== Load crud piece ==========
       $(document).on('click', '[load-crud-piece]', function(event)
       {
           let item = $(this);
           let piece_id = item.attr('load-crud-piece') ?? '';
           let mode = item.attr('data-mode');
           let crud_id = $('#id').val();

           const piece_name = $('[data-insert-crud-piece="piece_name"]').val();
           const type_crud = $('[data-insert-crud-piece="type_crud"]').val();

           $('#crud-piece-list').html('<p class="placeholder-glow"><span class="placeholder col-12"></span></p>');

           $.ajax(
           {
               url: `${BASE_URL}/${REST_API_BASE_ROUTE}/load-crud-piece`,
               type: 'POST',
               data: {
                   'crud_id': crud_id,
                   'piece_id': piece_id,
                   'mode': mode,
                   'piece_name': piece_name,
                   'type_crud': type_crud,
               },
               success: function (response)
               {
                   if (response.detail?.msg) {
                       open_message(response.detail.type, response.detail.msg ?? '');
                   }

                   if (response?.crud_piece_actions) {
                       commitChange('load:crud-piece');
                       $('#crud-piece-list').html(response.crud_piece_actions);
                   }

                   counter = $('#field-container-card').data('counter-fields');

                   $('[data-insert-crud-piece="piece_name"]').val('');
                   $('[data-insert-crud-piece="type_crud"]').val('Tipo');
               }
           });

           return true;
       });

       <?php endif; ?>


       // ========== Upload types (accepted extensions preset) ==========
       $(document).on('click', '[data-upload-input-is]', function(event)
       {
           let input = $(this);
           let input_is = input.attr('data-upload-input-is');

           let accept_audios = "<?= $allowed_mime_types_audios ?>";
           let accept_archives = "<?= $allowed_mime_types_archives ?>";
           let accept_images = "<?= $allowed_mime_types_images ?>";
           let accept_videos = "<?= $allowed_mime_types_videos ?>";

           let container = input.closest('.form-row');
           let acceptedInput = container.find('input[name*=\"[accepted_extensions]\"]');

           let value = '';
           if (input_is === 'audios') value = accept_audios;
           else if (input_is === 'archives') value = accept_archives;
           else if (input_is === 'images') value = accept_images;
           else if (input_is === 'videos') value = accept_videos;

           if (acceptedInput.length) acceptedInput.val(value);
       });


       // ========== Page settings/attachments/selectors ==========
       $('form').on('change', '[data-page]', function ()
       {
           if (this.value == 'manual') {
               $('[data-redirect-manual]').show();
               $('[data-redirect-automatic]').hide();
           } else {
               $('[data-redirect-manual]').hide();
               $('[data-redirect-automatic]').show();
           }
       });

       $('form').on('change', '[data-attachments]', function ()
       {
           let data = $(this);
           let value_selected = data.val();
           let path = data.parent().parent().nextAll('article').first();

           if (value_selected == 'price') {
               path.find('input[data-prepend]').val('$');
               path.find('input[data-append]').val('');
           }
           else if (value_selected == 'email') {
               path.find('input[data-prepend]').val('');
               path.find('input[data-append]').val('@email.com');
           }
           else if (value_selected == 'url') {
               path.find('input[data-prepend]').val('https://');
               path.find('input[data-append]').val('');
           }
           else {
               path.find('input[data-prepend]').val('');
               path.find('input[data-append]').val('');
           }
       });

       // [data-content-view]
       // $(document).on('change', 'select[name="type_crud"]', function (event)
       // {
       //     let showForm = false;
       //     let showList = false;

       //     $('select[name="type_crud"]').each(function ()
       //     {
       //         let type = $(this).val();
       //         if (!type) return;
       //         if (type === 'insert' || type === 'update') showForm = true;
       //         else if (type === 'list') showList = true;
       //     });

       //     $('[data-content-form]').toggle(showForm);
       //     $('[data-content-list]').toggle(showList);
       // });

       $(document).on('change', '[name="form_settings[]"]', function ()
       {
         let showStepForm = false;
         $('input[name="form_settings[]"]:checked, select[name="form_settings[]"] option:selected').each(function () {
           const val = $.trim(this.value || '');
           if (val === 'steps_form') showStepForm = true;
         });
         $('[steps-form-settings]').toggle(showStepForm);
       });


       // ========== Form endpoint ==========
       $(document).on('click', '[form-action-trigger]', function(event)
       {
           $('[form-action-trigger]').each(function ()
           {
               var type = $(this).val();
               if (!$(this).is(':checked')) return;

               if (type == 'external')
               {
                   $('#form-action-div').replaceWith(`
                   <?= input(
                       'basic',
                       'insert',
                       [
                           'type' => 'url',
                           'div_attributes' => 'id:(form-action-div);',
                           'class' => 'hide-content-action-form',
                           'size' => 'col-12',
                           'label' => 'Ação do formulário',
                           'Placeholder' => 'Coloque uma URL ou uma ação',
                           'name' => 'form_action[action]',
                           'input_id' => 'form_action',
                           'attachment' => [ 'prepend' => 'https' ]
                       ]
                   )
                   ?>`);
               }

               else if (type == 'page')
               {
                   $('#form-action-div').replaceWith(`
                   <?= input(
                       'selection_type',
                       'insert',
                       [
                           'div_attributes' => 'id:(form-action-div);',
                           'class' => 'hide-content-action-form',
                           'type' => 'search',
                           'size' => 'col-12',
                           'label' => 'Ação do formulário',
                           'name' => 'form_action[action]',
                           'input_id' => 'form_action',
                           'Options' => $pages_to_choose,
                       ]
                   )
                   ?>`);
               }

               else if (type == 'api')
               {
                   $('#form-action-div').replaceWith(`
                   <?= input(
                       'selection_type',
                       'insert',
                       [
                           'div_attributes' => 'id:(form-action-div);',
                           'class' => 'hide-content-action-form',
                           'type' => 'search',
                           'size' => 'col-12',
                           'label' => 'Ação do formulário',
                           'name' => 'form_action[action]',
                           'input_id' => 'form_action',
                           'Options' => $routes_to_choose,
                       ]
                   )
                   ?>`);
               }
           });
       });


       // ========== Remoções ==========
       $(document).on('click', '[remove-item]', function ()
       {
           commitChange('remove:item');

           var button_id = $(this).attr('id');
           var data = $(this).attr('remove-item');

           if ( $('#delete_items').val() == '' ) {
               $('#delete_items').val(data);
           } else if ( data != undefined ) {
               $('#delete_items').val( $('#delete_items').val() + '-' + data );
           }

           $('#form-item-' + button_id).fadeOut(250, function() {
               $(this).remove();
           });
       });

       $(document).on('click', '.remove-content', function ()
       {
           commitChange('remove:module-content');
           var button_id = $(this).attr('id');
           $('#module_content-' + button_id).fadeOut(250, function() {
              $(this).remove();
           });
       });

       $(document).on('input', 'input[update-title]', function() {
           var newTitle = $(this).val();
           var $card = $(this).closest('.field-content-card');
           $card.find('.accordion-button .name').text(newTitle);
       });


       // ========== Ajax success para IDs atualizados ==========
       saveItemsOrder();

       // --- Global hook para fetch, sem quebrar send_form() ---
       (function () {
         if (window.__updatedOrderFetchPatched) return;
         window.__updatedOrderFetchPatched = true;

         const nativeFetch = window.fetch;

         // Aplica updated_items_order nos inputs [id] e replica [name] -> [old_name]
         function applyUpdatedItemsOrder(updated, root) {
           if (!updated) return;
           if (typeof updated === 'string') { try { updated = JSON.parse(updated); } catch {} }
           if (!Array.isArray(updated) || !updated.length) return;

           // Escopo: tenta primeiro um container “clássico”, depois root, depois document
           const scope =
             document.querySelector('#field-container-card') ||
             root ||
             document;

           // Aguarda inputs renderizados (se vierem por render assíncrono)
           const tryApply = () => {
             const idInputs = scope.querySelectorAll('input[name$="[id]"]');
             if (!idInputs.length) { setTimeout(tryApply, 50); return; }

             idInputs.forEach((input, i) => {
               const rec = updated[i];
               if (!rec) return;

               if (rec.id != null) input.value = rec.id;

               // Ex.: Fields[3][id] => base "Fields[3]"
               const base = input.name.replace(/\[id\]$/, '');
               const nameInput    = scope.querySelector(`[name="${base}[name]"]`);
               const oldNameInput = scope.querySelector(`[name="${base}[old_name]"]`);
               if (nameInput && oldNameInput) oldNameInput.value = nameInput.value;
             });
           };

           tryApply();
         }

         // Wrapper do fetch: lê o clone da resposta e aplica updated_items_order, se houver
         window.fetch = async function (...args) {
           const res = await nativeFetch.apply(this, args);

           // Leia o clone de forma “out-of-band” e não bloqueie o fluxo do caller
           try {
             res.clone().text().then((txt) => {
               try {
                 const json = JSON.parse(txt);
                 if (json && json.updated_items_order) {
                   applyUpdatedItemsOrder(json.updated_items_order, document);
                 }
               } catch {
                 // resposta não-JSON? ignora
               }
             }).catch(() => {});
           } catch {
             // alguns ambientes podem não permitir .clone(); ignora
           }

           return res; // devolve a Response intacta para quem chamou (ex.: send_form)
         };
       })();



       // ========== Seleção ativa ==========
       $(document).click(function(e)
       {
           if (!$(e.target).closest('.field-content-card').length) {
               $('.field-content-card').removeClass('active');
           }
           saveItemsOrder();
       });

       $(document).on('click', '.field-content-card', function()
       {
           $('.field-content-card').removeClass('active');
           $(this).addClass('active');
           saveItemsOrder();
       });


       // ========== Helpers de profundidade ==========
       function getDepthClass($item)
       {
         let match = $item.attr('class').match(/depth-(\d+)/);
         return match ? parseInt(match[1]) : 0;
       }

       function setDepthClass($item, depth)
       {
           // Divider nunca afunda
           if ($item.hasClass('fields-for-divider')) depth = 0;
           if ($item.index() === 0) depth = 0;

           // Só permite depth > 0 se tiver repeater/divider antes
           if (depth > 0 && !hasPreviousRepeater($item)) {
               depth = 0;
           }

           $item.removeClass((i, c) => (c.match(/depth-\d+/) || []).join(' '))
                .addClass(`depth-${depth}`);
           $item.find('input[name$="[depth]"]').val(depth);
       }

       function hasPreviousRepeater($item)
       {
           const $prev = $item.prevAll('.field-content-card');
           let found = false;
           $prev.each(function ()
           {
               const type = $(this).find('input[type="hidden"][name$="[type_field]"]').val();
               if (type === 'field_repeater' || type === 'divider') {
                   found = true;
                   return false;
               }
           });
           return found;
       }

       function updateDescendants($parent, delta, parentOriginalDepth)
       {
           let $next = $parent.next('.field-content-card');
           while ($next.length)
           {
               let childDepth = getDepthClass($next);
               if (childDepth > parentOriginalDepth) {
                 let newChildDepth = childDepth - delta;
                 setDepthClass($next, newChildDepth);
                 $next = $next.next('.field-content-card');
               } else break;
           }
           saveItemsOrder();
       }

       function saveItemsOrder()
       {
         let order = [];
         $("#field-container-card .field-content-card").each(function (i, el)
         {
           let $item = $(el);
           let depth = getDepthClass($item);
           let id = $item.find('input[name$="[id]"]').val() || "";
           order.push({ id: id, depth: depth });
         });
         // $("#items-order").val(JSON.stringify(order));
       }


       // ========== Keybindings (mover/profundidade) ==========
       $(document).on('keydown', function (e)
       {
           if (isTypingTarget(e.target)) return;

           let $active = $('.field-content-card.active');
           if (!$active.length) return;

           const currentDepth = getDepthClass($active);
           const isDivider = $active.hasClass('fields-for-divider');

           if (e.which === 39) { // →
               e.preventDefault();
               if (isDivider) return;

               const prev = $active.prev('.field-content-card');
               if (prev.length) {
                 commitChange('depth:increase');
                 const prevDepth = getDepthClass(prev);
                 const newMax = Math.min(prevDepth + 1, maxDepth);
                 setDepthClass($active, Math.min(currentDepth + 1, newMax));
               }
           }
           else if (e.which === 37) { // ←
               e.preventDefault();
               if (isDivider) return;

               commitChange('depth:decrease');
               const originalDepth = currentDepth;
               const newDepth = Math.max(0, currentDepth - 1);
               const delta = currentDepth - newDepth;
               setDepthClass($active, newDepth);
               updateDescendants($active, delta, originalDepth);
           }
           else if (e.which === 38) { // ↑
               e.preventDefault();
               const prev = $active.prev('.field-content-card');
               if (prev.length) {
                 commitChange(e.shiftKey ? 'focus:prev' : 'move:up');
                 if (!e.shiftKey) $active.insertBefore(prev);
                 else {
                   $active.removeClass('active');
                   prev.addClass('active');
                 }
               }
           }
           else if (e.which === 40) { // ↓
               e.preventDefault();
               const next = $active.next('.field-content-card');
               if (next.length) {
                 commitChange(e.shiftKey ? 'focus:next' : 'move:down');
                 if (!e.shiftKey) $active.insertAfter(next);
                 else {
                   $active.removeClass('active');
                   next.addClass('active');
                 }
               }
           }

           saveItemsOrder();
       });

    });



    /**
     *
     * Show the specific fields for each CRUD type.
     *
     */
    (function ()
    {
      function getSelectedTypes() {
        const types = new Set();
        document.querySelectorAll('select[name="type_crud"]').forEach(sel => {
          const v = String(sel.value || '').toLowerCase();
          if (v) types.add(v);
        });
        return types;
      }

      function setVisible(el, show) {
        el.style.display = show ? '' : 'none';
      }

      function applytype_crudViews() {
        const selected = getSelectedTypes();
        const showRelated = selected.has('view') || selected.has('insert') || selected.has('update');
        const showForm = selected.has('insert') || selected.has('update');
        const showList = selected.has('list');

        // grupos agregados
        document.querySelectorAll('[data-content-form]').forEach(el => setVisible(el, showForm));
        document.querySelectorAll('[data-content-list]').forEach(el => setVisible(el, showList));
        document.querySelectorAll('[data-content-related]').forEach(el => setVisible(el, showRelated));
      }

      // delegação para mudanças no type_crud (inclusive dinâmicos)
      document.addEventListener('change', function (e) {
        if (e.target && e.target.matches('select[name="type_crud"]')) {
          applytype_crudViews();
        }
      });

      // estado inicial
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applytype_crudViews);
      } else {
        applytype_crudViews();
      }
    })();


    /**
     *
     * Mostra/oculta os selects conforme o modo escolhido
     *
     */
    function apply(action){
      var c = document.querySelector('input[name="pages_list['+action+'][mode]"]:checked');
      var v = c ? c.value : 'page'; // 'page' | 'modal'
      document.querySelectorAll('.mode-section[data-mode-section="'+action+'"]').forEach(function(s){
        var show = (s.getAttribute('data-mode') === v);
        s.style.display = show ? '' : 'none';
        s.setAttribute('aria-hidden', show ? 'false' : 'true');
      });
    }

    document.addEventListener('change', function(e){
      var t = e.target;
      if (!t || t.type !== 'radio') return;
      var name = t.name || '';
      var m = name.match(/^pages_list\[(insert|view|update)\]\[mode\]$/);
      if (m) apply(m[1]);
    });

    document.addEventListener('DOMContentLoaded', function(){
      ['insert','view','update'].forEach(apply);
    });
    </script>
    <?php
}

