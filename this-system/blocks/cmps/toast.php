<?php
if(!isset($seg)) exit;

/**
 * Generate an toast message HTML block.
 *
 * This function creates an HTML toast message block based on the provided attributes.
 *
 * @param array $Attr - An associative array containing attributes for the toast message.
 *
 * @return string|null - An HTML toast message block or null if 'message' attribute is not provided.
 */
function toast(array $Attr = [])
{
  if (!empty($Attr['body']))
  {
    $id         = $Attr['id'] ?? 'common-toast';
    $delay      = $Attr['delay'] ?? '0';
    $time       = $Attr['time'] ?? '10000';
    $class      = $Attr['class'] ?? '';
    $title      = $Attr['title'] ?? '';
    $small      = $Attr['small'] ?? '';
    $text       = $Attr['color']['text'] ?? 'white';
    $background = $Attr['color']['background'] ?? 'info';
    $color      = $Attr['color'] ?? '';

    $attributes = !empty($Attr['attributes'])
      ? parse_html_tag_attributes($Attr['attributes'])
      : '';

    $close_button = (isset($Attr['close_button']) && $Attr['close_button'] == true)
      ? "<button type='button' class='btn-close' delay='$delay' data-bs-dismiss='toast' aria-label='Close'></button>"
      : '';

    $img = !empty($Attr['img'])
      ? img(['src' => $Attr['img']])
      : '';

    $header = (!empty($title) OR !empty($close_button))
      ? "<div class='toast-header message-$color'>
        $img
        <h6>".format_text($title)."</h6>
        <span><small>$small</small>$close_button</span>
        </div>"
      : '';

    $res = "<article class='toast message-$color' id='$id' $attributes role='toast' aria-live='polite' aria-atomic='true' data-bs-delay='$time'>";
    $res.= $header;
    $res.= "<div class='content'>";
    $res.= "<div class='toast-body'>". format_text($Attr['body']) ."</div>";
    $res.= !empty($footer) ? "<div class='toast-footer'>".format_text($footer)."</div>" : '';
    $res.= "</article>";

    return $res;
  }
}
