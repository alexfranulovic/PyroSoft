<?php
if(!isset($seg)) exit;

/**
 * Generates a horizontal list section based on the given attributes.
 *
 * @param array $Attr An array of attributes for the horizontal list.
 */
function horizontal_list(array $Attr = [])
{
  global $animations, $counter;

  if (!empty($Attr['contents']))
  {
    $variation = $Attr['variation'] ?? '';

    $res = "<div class='row horizontal-list ". ($animations ? 'animate-bottom' : '') ." mx-0 pb-4' id='section-horizontal-list-$counter'>";
    foreach ($Attr['contents'] as $content)
    {
      $content = (array) $content;
      if (isset($content['image_folder'])) $content['image'] = pg ."/uploads/images/{$content['image_folder']}/". ($content['image']??'');

      //$res.= !empty($variation) ? $variation($content) : $content['content'];
      $res.= !block($variation, $content) ? $content['content'] : block($variation, $content);
    }
    $res.='</div>';

    return $res;
  }
}
