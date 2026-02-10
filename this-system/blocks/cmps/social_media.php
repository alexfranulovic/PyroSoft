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
function social_media(array $Attr = [])
{
  global $info;

  $class = $Attr['class'] ?? '';
  $align = $Attr['align'] ?? 'start';

  if (!empty($Attr['content']))
  {
    $res = "<div class='social-media $class content-$align'>";
    $res.= !empty($Attr['title']) ? "<h3 class='text-$align'>". format_text($Attr['title']) ."</h3>" : "";
    $res.= !empty($Attr['subtitle']) ? "<p class='text-$align'>". format_text($Attr['subtitle']) ."</p>" : '';
    $res.= "<ul class='content-$align'>";

    foreach ($Attr['content'] as $title => $detail)
    {
      if (empty($detail['url'])) continue;
      $res.= "<li><a target='_blank' href='{$detail['url']}' title='{$detail['name']}'>". icon($detail['icon']) ."</a></li>";
    }

    $res.= "
    </ul>
    </div>";

    return $res;
  }
}