<?php
if(!isset($seg)) exit;

/**
 * Generate an alert message HTML block.
 *
 * This function creates an HTML alert message block based on the provided attributes.
 *
 * @param array $Attr - An associative array containing attributes for the alert message.
 *
 * @return string|null - An HTML alert message block or null if 'message' attribute is not provided.
 */
function alert(array $Attr = [])
{
  if (!empty($Attr['body']))
  {
    $class      = $Attr['class'] ?? '';
    $title      = $Attr['title'] ?? '';
    $color      = $Attr['color'] ?? '';
    $background = $Attr['color']['background'] ?? 'info';
    $variation  = $Attr['variation'] ?? 'alert-default';

    $attributes = !empty($Attr['attributes'])
      ? parse_html_tag_attributes($Attr['attributes'])
      : '';

    $res = "<div class='alert $variation fade show alert-$color' $attributes>";
    $res.= (isset($Attr['close_button']) && $Attr['close_button'] == true)
      ? "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>"
      : '';
    $res.= "<span class='body'>". format_text($title) ." ". format_text($Attr['body']) ."</span>";
    $res.= "</div>";

    return $res;
  }
}