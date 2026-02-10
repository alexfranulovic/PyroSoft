<?php
if(!isset($seg)) exit;

/**
 * Generates a badge with a link based on the provided attributes.
 *
 * @param array $Attr An array of attributes for the badge.
 *   - title: The title or text content of the badge.
 *   - url: The URL to link the badge to (optional).
 *   - color: The color theme for the badge (e.g., 'primary', 'secondary', 'success', 'danger', etc.).
 *
 * @return string The HTML markup for the badge.
 */
function badge(array $Attr = [])
{
  if (!empty($Attr))
  {
    global $info;

    $title  = $Attr['title'] ?? '';
    $color  = $Attr['color'] ?? '';

    $res = "
    <a ". check_pg_in_url($Attr['url'] ?? '') ." class='btn badge text-bg-$color' title='$title'>
      ". format_text($title) ."
    </a>";

    return $res;
  }
}