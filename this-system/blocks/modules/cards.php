<?php
if(!isset($seg)) exit;

/**
 * Generates a section with a set of cards based on the specified $type_card function and attributes.
 *
 * @param array $Attr An array of attributes for the card.
 */
function cards(array $Attr = [])
{
  global $animations, $counter;

  if (!empty($Attr['contents']))
  {
    $variation = $Attr['variation'] ?? 'card_default';

    $counter = 1;
    $res = "<div class='list row justify-content-center ". ($animations ? 'animate-bottom' : '') ."'>";
    foreach ($Attr['contents'] as $content)
    {
      $content = (array) $content;

      if (isset($content['image_folder']) && !empty($content['image'])) $content['image'] = pg ."/uploads/images/{$content['image_folder']}/{$content['image']}";

      if(!variation($variation, $content)) {
        $res.= '<div>This variation does not exist.</div>';
        break;
      }

      $res.= "<article class='". ($content['size'] ?? 'col-md-6 col-lg-4 col-xl-3') ."'>";
      $res.= variation($variation, $content);
      $res.= "</article>";
    }
    $res .= "</div>";

    return $res;
  }
}
