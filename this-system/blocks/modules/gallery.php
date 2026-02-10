<?php
if(!isset($seg)) exit;

/**
 * Generates a gallery section with alternating content and images based on the given attributes.
 *
 * @param array $Attr An array of attributes for the gallery.
 */
function gallery(array $Attr = [])
{
  global $animations, $counter, $info;

  if (!empty($Attr['contents']))
  {
    $variation = $Attr['variation'] ?? 'gallery_default';

    $number = 1;
    $res = "<div class='row justify-content-center ". ($animations ? 'animate-bottom' : '') ."'>";

    foreach ($Attr['contents'] as $content)
    {
      $content = (array) $content;

      $attr = $content['attr'] ?? '';
      $button_title = !empty($content['button_title']) ? $content['button_title'] : 'Ver mais';

      if (isset($content['image_folder'])) $content['image'] = pg ."/uploads/images/{$content['image_folder']}/{$content['image']}";


      if ($variation == 'gallery_default')
      {
        $size = isset($content['size']) ? $content['size'] : 'col-md-3';

        $res.= "
        <article class='gallery-default $size py-3 animate-bottom' $attr>
        <a ". check_pg_in_url($content['url'] ?? '') ." title='{$content['title']}'>
          <img loading='lazy' class='img-fluid' width='200' height='200' src='{$content['image']}' alt='{$content['title']}'>
        </a>
        </article>";
      }

      elseif ($variation == 'gallery_timeline')
      {
        $order_image    = is_even($number) ? 'order-lg-1' : '';
        $order_content  = is_even($number) ? 'order-lg-2' : '';
        $content_border = !is_even($number) ? '' : 'colored-border-'. $number;
        $image_border   = !is_even($number) ? 'colored-border-'. $number : '';

        $res.= "
        <article class='col-12 gallery-timeline animate-bottom' $attr>
        <div class='row'>
          <div class='col-lg  $content_border $order_content'>
          <div class='entry content'>
            <h3 class='title'>{$content['title']}</h3>
            <p class='body'>{$content['content']}</p>";

            if (!empty($content['url']))
            $res.= "<div class='footer'><a ". check_pg_in_url($content['url'] ?? '') ." class='btn btn-nd'>$button_title</a></div>";

          $res.= "
          </div>
          </div>
          <div class='col-lg $image_border $order_image'>
          <div class='entry image-card'>
            <img loading='lazy' src='{$content['image']}' alt='Imagem não carregada'>
          </div>
          </div>
        </div>
        </article>";
      }

      $number++;
    }
    $res.= "</div>";

    return $res;
  }
}
