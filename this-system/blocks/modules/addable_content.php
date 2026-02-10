<?php
if (!isset($seg)) exit;

/**
 * Generate addable content for a form or section.
 *
 * This function generates addable content for a form or section, allowing users to dynamically add more content.
 *
 * @param array $data - An associative array containing data for generating addable content.
 *   - 'group' (string): Group identifier for the addable content.
 *   - 'title' (string): Title for the addable content section.
 *   - 'counter' (int): Counter value for tracking the number of addable items.
 *   - 'type_form' (string): Type of form.
 *   - 'content' (string): JSON-encoded content representing existing items.
 *   - 'type' (string): Type identifier.
 *   - 'function' (callable): Callback function for generating content.
 *
 * @return string - HTML markup for the addable content.
 */
function addable_content(array $data = [])
{
    $group     = $data['group'] ?? 'module-content-form';
    $title     = $data['title'] ?? '';
    $counter   = $data['counter'] ?? 1;
    $type_form = $data['type_form'] ?? '';
    $content   = $data['content'] ?? [];
    $type      = $data['type'] ?? '';
    $function  = $data['function'] ?? '';

    $res = "
    <section class='addable-content'>
    <div class='header'>
        <h4>$title</h4>
        <button type='button' class='btn add-module-content' title='Adicionar' value='$type-|-$counter'>+</button>
    </div>
    <hr>
    <div class='draggable-column' id='$group-$counter'>";

    if (is_object($content) || is_array($content))
    {
        $module_counter = 1;
        foreach ($content as $val)
        {
            $val = (array) $val;
            $res .= $function($type_form, $counter, $module_counter, $val);
            $module_counter++;
        }
    }

    $res .= "
    </div>
    </section>";

    return $res;
}


/**
 * Generate addable content with input group for a form or section.
 *
 * This function generates addable content with an input group for a form or section,
 * allowing users to dynamically add more content with additional input groups.
 *
 * @param array $data - An associative array containing data for generating addable content with input group.
 *   - 'counter' (int): Counter value for tracking the number of addable items.
 *   - 'title' (string): Title for the addable content section.
 *   - 'module_counter' (int): Counter value for tracking the number of input groups within an addable item.
 *   - 'content' (string): Content for the input group.
 *
 * @return string - HTML markup for the addable content with input group.
 */
function addable_content_input_group(array $data = [])
{
    $counter        = $data['counter'] ?? 1;
    $title          = $data['title'] ?? '';
    $module_counter = $data['module_counter'] ?? 1;
    $content        = $data['content'] ?? '';

    $res = "
    <article class='accordion-item addable-content-input-group draggable-item' id='module_content-$counter-$module_counter'>
      <h2 class='accordion-header'>
        <div class='btn move' id='$counter'>". icon('fas fa-arrows-up-down') ."</div>
        <button type='button' class='btn remove-content' id='$counter-$module_counter'>". icon('fas fa-trash') ."</button>
        <button
        class='accordion-button collapsed' type='button'
        id='heading$counter-$module_counter'
        data-bs-target='#collapse_advanced-$counter-$module_counter' aria-expanded='false' title='Ver mais'
        data-bs-toggle='collapse' aria-controls='collapse_advanced-$counter-$module_counter'>
          $title - $module_counter
        </button>
      </h3>
      <div id='collapse_advanced-$counter-$module_counter' class='accordion-collapse collapse' aria-labelledby='heading$counter-$module_counter' data-bs-parent='#module-content-form-$counter'>
        <div class='accordion-body'>
          {$content}
        </div>
      </div>
    </article>";

    return $res;
}
