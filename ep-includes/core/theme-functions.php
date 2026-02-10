<?php
if(!isset($seg)) exit;


/**
 * Get theme button colors.
 *
 * @param bool $ForSelects Whether to format the colors for select options.
 * @return mixed The list of theme colors.
 */
function theme_button_colors()
{
    return [
        [ 'display' => 'Primária', 'value' => 'primary', ],
        [ 'display' => 'Primária (contorno)', 'value' => 'outline-primary', ],
        [ 'display' => 'Secundária', 'value' => 'secondary', ],
        [ 'display' => 'Secundária (contorno)', 'value' => 'outline-secondary', ],
        [ 'display' => 'Terciária', 'value' => 'tertiary', ],
        [ 'display' => 'Terciária (contorno)', 'value' => 'outline-tertiary', ],
        [ 'display' => 'Quaternária', 'value' => 'quaternary', ],
        [ 'display' => 'Quaternária (contorno)', 'value' => 'outline-quaternary', ],
        [ 'display' => 'Branco gelo', 'value' => 'light', ],
        [ 'display' => 'Branco gelo (contorno)', 'value' => 'outline-light', ],
        [ 'display' => 'Cinza escuro', 'value' => 'dark', ],
        [ 'display' => 'Cinza escuro (contorno)', 'value' => 'outline-dark', ],
    ];
}


/**
 * Get theme background colors.
 *
 * @param bool $ForSelects Whether to format the colors for select options.
 * @return mixed The list of theme colors.
 */
function theme_background_colors()
{
    return [
        [ 'display' => 'Primária', 'value' => 'primary', ],
        [ 'display' => 'Secundária', 'value' => 'secondary', ],
        [ 'display' => 'Terciária', 'value' => 'tertiary', ],
        [ 'display' => 'Quaternária', 'value' => 'quaternary', ],
        [ 'display' => 'Verde', 'value' => 'success' ],
        [ 'display' => 'Vermelho', 'value' => 'danger' ],
        [ 'display' => 'Amarelo', 'value' => 'warning' ],
        [ 'display' => 'Azul claro', 'value' => 'info' ],
        [ 'display' => 'Branco', 'value' => 'white' ],
        [ 'display' => 'Branco gelo', 'value' => 'light' ],
        [ 'display' => 'Cinza escuro', 'value' => 'dark' ],
        [ 'display' => 'Preto', 'value' => 'black' ],
    ];
}


/**
 * Get theme colors.
 *
 * @param bool $ForSelects Whether to format the colors for select options.
 * @return mixed The list of theme colors.
 */
function theme_colors()
{
    return [
        [ 'display' => 'Verde', 'value' => 'success' ],
        [ 'display' => 'Verde (contorno)', 'value' => 'outline-success' ],
        [ 'display' => 'Vermelho', 'value' => 'danger' ],
        [ 'display' => 'Vermelho (contorno)', 'value' => 'outline-danger' ],
        [ 'display' => 'Amarelo', 'value' => 'warning' ],
        [ 'display' => 'Amarelo (contorno)', 'value' => 'outline-warning' ],
        [ 'display' => 'Azul escuro', 'value' => 'primary' ],
        [ 'display' => 'Azul escuro (contorno)', 'value' => 'outline-primary' ],
        [ 'display' => 'Cinza', 'value' => 'secondary' ],
        [ 'display' => 'Cinza (contorno)', 'value' => 'outline-secondary' ],
        [ 'display' => 'Azul claro', 'value' => 'info' ],
        [ 'display' => 'Azul claro (contorno)', 'value' => 'outline-info' ],
        [ 'display' => 'Branco gelo', 'value' => 'light' ],
        [ 'display' => 'Branco gelo (contorno)', 'value' => 'outline-light' ],
        [ 'display' => 'Cinza escuro', 'value' => 'dark' ],
        [ 'display' => 'Cinza escuro (contorno)', 'value' => 'outline-dark' ],
    ];
}


/**
 * Outputs a line break (<br> tag) in HTML.
 *
 * @return void
 */
function br()
{
    return "<br>";
}


/**
 * Generates an HTML <div> element with the class attribute set to "w-100".
 *
 * @param array|null $params An associative array containing the parameters for generating the HTML element.
 * @return string Returns the generated HTML element as a string.
 */
function break_line($params = null)
{
    return "<div class='w-100'></div>";
}


/**
 * Generates an HTML <hr> element wrapped in a <div> element with optional attributes.
 *
 * @param array|null $params An associative array containing the parameters for generating the HTML elements.
 * @return string Returns the generated HTML elements as a string.
 */
function hr($params = [])
{
    return "<fieldset class='col-12'><hr class='my-0'></fieldset>";
}


/**
 * Generates an HTML element with optional attributes and content, wrapped in a <div> element with optional attributes.
 *
 * @param array $params An associative array containing the parameters for generating the HTML elements.
 * @return string Returns the generated HTML elements as a string.
 */
function shortcode($params = [])
{
    $div_attributes = !empty($Attr['div_attributes'])
        ? parse_html_tag_attributes($Attr['div_attributes'])
        : '';

    $size           = $params['size'] ?? 'col-md-6';
    $div_class      = $Attr['div_class'] ?? '';
    $div_class      = trim("$div_class $size");
    $content        = $params['content'] ?? '';

    return "
    <fieldset $div_attributes class='$div_class'>
        {$content}
    </fieldset>";
}


/**
 * Includes an SVG file based on the provided filename.
 *
 * @param string $svg The name of the SVG file (without the .php extension) to include.
 */
function svg(string $svg = '', array $opts = [])
{
    global $info, $config;

    $file = __BASE_DIR__ . "uploads/svg/{$svg}.php";
    if (!file_exists($file)) {
        return null;
    }

    $stripStyle  = $opts['strip_style']  ?? true;
    $stripScript = $opts['strip_script'] ?? true;

    ob_start();
    include $file;                 // SVG template echoes markup
    $out = ob_get_clean();

    if (!$out) {
        return null;
    }

    // Remove <style> blocks (your renderer/sanitizer may strip the tag but keep the CSS as text)
    if ($stripStyle) {
        $out = preg_replace('#<style\b[^>]*>.*?</style>#is', '', $out);
    }

    // Remove <script> blocks (safety)
    if ($stripScript) {
        $out = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $out);
    }

    return trim($out);
}



/**
 * Generates an HTML element with the link_button.
 *
 * @param array $params An associative array containing the parameters for generating the HTML element.
 * @return string Returns the generated HTML elements as a string.
 */
function link_button($params = [])
{
    $attr       = $params['attr'] ?? '';
    $animations = !empty($params['animations']) ? 'animate-reverse-bottom' : '';
    $color      = $params['color'] ?? 'primary';
    $title      = $params['title'] ?? "Ver mais";
    $style      = $params['style'] ?? "button";
    $url        = $params['url'] ?? '';
    $size       = $params['size'] ?? '';

    if ($style == 'button') $class = "btn btn-$color $size $animations";
    elseif ($style == 'link')    $class = "m-link text-$color";

    if (($params['type']=='custom') OR ($params['type']=='page') AND load_permission($url))
    {
        $url = ($params['type']=='page') ? get_url_page($url, 'full') : $url;
        if ($attr == '[id]') $url .= "?id". id_by_get();

        $res = "<a ". check_pg_in_url($url) ." class='$class'>".format_text($title)."</a>";
    }

    return !empty($res) ? $res : '';
}


function set_recursive_menu_items(&$items)
{
    foreach ($items as &$item)
    {
        if (!empty($item['childs']))
        {
            set_recursive_menu_items($item['childs']);
            $child_active = false;
            foreach ($item['childs'] as $child)
            {
                if ((isset($child['active']) && $child['active'] === 'active') ||
                    (isset($child['show']) && $child['show'] === 'show')) {
                    $child_active = true;
                    break;
                }
            }
            $item['show'] = $child_active ? 'show' : '';
            $item['expanded'] = $child_active ? 'true' : 'false';
        }

        else {
            $item['show'] = '';
            $item['expanded'] = '';
        }
    }
}

function get_menu($id = null, bool $debug = false)
{
    global $info, $page, $current_user;

    $page_id = $page['id'] ?? 0;

    $roles = load_roles();

    $ids = implode(",", array_map('intval', $roles));

    $which_users = is_user_logged_in()
        ? 'logged_in'
        : 'logged_out';

    $query = "
    SELECT DISTINCT
        subitem.title,
        subitem.icon,
        subitem.url,
        subitem.type,
        subitem.depth,
        subitem.style,
        subitem.which_users,
        subitem.function_view,
        subitem.attributes,
        page.page_area,
        page.slug

    FROM tb_menus menu
    LEFT JOIN tb_menus subitem ON menu.id = subitem.menu_id
    LEFT JOIN tb_pages page ON page.id = subitem.url
    LEFT JOIN tb_user_role_permissions permission
        ON permission.page_id = page.id
        AND permission.role_id IN ({$ids})

    WHERE menu.id = '{$id}'
        AND (
            subitem.type != 'page'
            OR (
                page.status_id = 1
                AND subitem.type = 'page'
                AND (
                    (
                        page.permission_type = 'only_these'
                        AND permission.allowed = 1
                    )
                    OR (
                        page.permission_type = 'except_these'
                        AND (permission.allowed IS NULL OR permission.allowed != 1)
                    )
                )
            )
        )
        AND menu.status_id = 1
        AND (subitem.which_users = 'everyone' OR subitem.which_users = '{$which_users}')
    ORDER BY subitem.order_reg ASC";
    $results = get_results($query);


    $menu = [];
    $menu = $results;
    $key = 0;
    foreach ($results as $item)
    {
        $menu[$key] = $item;

        if (!empty($item['function_view']))
        {
            $item['title_value'] = replace_variables($item['title']);
            $menu[$key]['title'] = function_view($item['function_view'], 'title_value', $item);
        } else {
            $menu[$key]['title'] = replace_variables($item['title']);
        }


        $menu[$key]['attributes'] = parse_html_tag_attributes($item['attributes']);


        if ($item['type'] == 'page') {
            $url = site_url("/". page_area($item['page_area'], 'url') . $item['slug']);
        }

        elseif ($item['type'] == 'user_links')
        {
            $url = $item['url'];

            if ($item['url'] == "user-logout") {
                $url = logout_url();
            }
        }

        else {
            $url = replace_variables($item['url']);
        }

        $menu[$key]['formatted_url'] = check_pg_in_url($url);

        $key++;
    }

    $tree = $stack = [];
    foreach ($menu as $item)
    {
        $item['active'] = ($page_id == $item['url']) ? 'active' : '';
        $item['childs'] = [];

        while (!empty($stack) && end($stack)['depth'] >= $item['depth']) {
            array_pop($stack);
        }

        if (empty($stack))
        {
            $tree[] = $item;
            $stack[] = &$tree[count($tree) - 1];
        }
        else
        {
            $parent = &$stack[count($stack) - 1];
            $parent['childs'][] = $item;
            $stack[] = &$parent['childs'][count($parent['childs']) - 1];
        }
    }

    set_recursive_menu_items($tree);

    if ($debug) echo "<pre>$query</pre>";

    return $tree;
}
