<?php
if(!isset($seg)) exit;

/**
 * Generate the top section of the base page layout.
 *
 * This function is used to generate the top section of a base page layout, typically used in an admin panel.
 *
 * @param array $hooks_out An array of outgoing action hooks to be displayed on the top right corner of the page.
 * @param array $hooks_in An array of incoming action hooks to be processed and displayed within a dropdown menu.
 * @return void This function does not return a value; it directly generates and outputs HTML content.
 */
function pageBaseTop(array $params = [])
{
    global $page;

    $pre_title  = $params['pre_title'] ?? '';
    $title      = $params['title'] ?? $page['title'];
    $subtitle   = $params['subtitle'] ?? '';
    $hooks_out  = $params['hooks_out'] ?? [];


    $pre_title = !empty($pre_title)
        ? "<span class='pre-title'>{$pre_title}</span>"
        : '';
    $subtitle = !empty($subtitle)
        ? "<p class='subtitle'>{$subtitle}</p>"
        : '';

    echo"
    <section class='main-content table-responsive'>
    <header class='col-12'>
        <section class='about-page'>
            {$pre_title}
            <h1>{$title}</h1>
            {$subtitle}
        </section>
        <div class='btn-toolbar'>
        <div class='btn-group'>";
        foreach (array_filter($hooks_out) as $hook_out)
        {
            if (!empty($hook_out)) {
                $attr =  $hook_out['attr'] ?? null;
                echo "<a ". check_pg_in_url($hook_out['link'] ?? '') ." {$attr} title='{$hook_out['title']}' class='btn btn-{$hook_out['color']} btn-sm'>{$hook_out['title']}</a>";
            }
        }
        echo"
        </div>
        </div>
    </header>";
}
