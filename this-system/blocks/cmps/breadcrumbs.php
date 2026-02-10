<?php
if(!isset($seg)) exit;


/**
 * Generates a breadcrumbs navigation for the current page based on the provided information.
 *
 * @param array $hooks_out An array of hooks for the breadcrumbs (optional).
 * @param array $hooks_in An array of hooks for the breadcrumbs (optional).
 *
 * @return string The HTML markup for the breadcrumbs navigation.
 */
function breadcrumbs(array $Attr = [])
{
    global $page;
    global $config;

    $class              = $Attr['class'] ?? 'module m-breadcrumb';
    $container          = (isset($Attr['container']) && $Attr['container'] == true) ? true : false;
    $section_attributes = $Attr['section_attributes'] ?? "";
    $divisor            = "&nbsp;>&nbsp;";
    $counter            = 1;

    $breadcrumbList = [
        "@context" => "https://schema.org",
        "@type" => "BreadcrumbList",
    ];

    $res = "<nav class='$class' $section_attributes style='margin-top: 55px; padding-top: 30px;'>";
    if ($container) $res .= "<div class='container'>";

    $res .= "
    <div class='content'>
    <ol>
    <li><a href='{$config['main_page']['url']}' title='{$config['main_page']['title']}'><span>{$config['main_page']['title']}</span></a></li>";

    $breadcrumbList['itemListElement'][] = [
        "@type" => "ListItem",
        "position" => $counter,
        "name" => $config['main_page']['title'],
        "item" => $config['main_page']['url']
    ];

    $counter++;

    if (!empty($page['parent_page_id']) AND ($page['parent_page_id'] != 'none' AND $page['parent_page_id'] != 0))
    {
        $depend = get_result("SELECT title, slug FROM tb_pages WHERE id = '{$page['parent_page_id']}'");

        $title = $depend['title'];
        $url = get_url_page($depend['slug'], 'full');

        $res .= "$divisor<li><a href='$url' title='$title'><span>$title</span></a></li>";

        $breadcrumbList['itemListElement'][] = [
            "@type" => "ListItem",
            "position" => $counter,
            "name" => $title,
            "item" => $url
        ];

        $counter++;
    }

    $title = "{$page['title']}" . general_status_admin($page['status_id'] ?? 0);

    $res .= "$divisor<li><span>{$title}</span></li>";

    $breadcrumbList['itemListElement'][] = [
        "@type" => "ListItem",
        "position" => $counter,
        "name" => $title,
        "item" => canonical
    ];

    $res .= "
    </ol>
    <div class='links'>";

    if (!empty($page['custom_urls']))
    {
        $custom_urls = (array) $page['custom_urls'];
        foreach (array_filter($custom_urls) as $hook_out)
        {
            $hook_out = (array) $hook_out;
            if (!empty($hook_out))
            {
                $attr = !empty($hook_out['attr']) ? $hook_out['attr'] : null;
                $url  = ($hook_out['type'] == 'page') ? get_url_page($hook_out['url'], 'full') : $hook_out['url'];
                $link = "&nbsp;<a " . check_pg_in_url($url) . " $attr title='{$hook_out['title']}' class='btn btn-{$hook_out['color']} btn-sm'>{$hook_out['title']}</a>";

                $res .= (($hook_out['type'] == 'custom') OR ($hook_out['type'] == 'page') AND load_permission($hook_out['url'])) ? $link : '';
            }
        }
    }

    $res .= "
    </div>
    </div>
    <hr>";

    if ($container) $res .= "</div>";
    $res .= "</nav>";

    $res .= seo_structred_data($breadcrumbList, false);

    return $res;
}
