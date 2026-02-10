<?php
if(!isset($seg)) exit;

/**
 * Outputs a section with a list of site numbers.
 *
 * @param array $Attr An array of attributes for the comments.
 *
 * @return void
 */
function comments(array $Attr = [])
{
  global $animations, $commands, $counter, $variation;

  if (!empty($Attr['contents']))
  {
    $variation = $Attr['variation'] ?? 'comments_default';

    if ($variation == 'comments_carousel')
    {
      $count_carousels = count($Attr['contents']);
      $res = "<div id='comments-$counter' class='carousel slide' data-ride='carousel'>";

      /** Commands **/
      $res.='<ul class="carousel-indicators mb-0">';
      if ($commands && $count_carousels > 1)
      {
        $count = 0;
        foreach ($Attr['contents'] as $content)
        {
          $res.= "<li class='". ($count == 0 ? 'active' : '') ."'' data-bs-target='#comments-$counter' data-slide-to='$count'></li>";
          $count++;
        }
      }
      $res.='</ul>';

      $count = 0;
      $res.= "<div class='carousel-inner ". ($animations ? 'animate-bottom' : '') ."'>";
      foreach ($Attr['contents'] as $content)
      {
        $content = (array) $content;

        $image = $content['image'] ?? null;
        if (isset($content['image_folder']) && isset($content['image'])) $image = pg ."/uploads/images/{$content['image_folder']}/{$content['image']}";

        $res.='<article class="carousel-item comment '. ($count == 0 ? 'active' : '') .' pt-5">';
        $res.="
        <div class='testimony-wrap pt-5'>
        <div class='user-img mb-4' style='background-image: url({$image})'>
          <span class='quote d-flex align-items-center justify-content-center bg-light' style='bottom: -50px;'>". icon('fas fa-quote-left') ."</span>
        </div>
        <div class='text text-center'>
          <p class='mb-4'>{$content['content']}</p>
          <p class='name'>{$content['title']}</p>
          <span class='position'>{$content['subtitle']}</span>
        </div>
        </div>";
        $res.= "</article>";

        $count++;
      }
      $res.= '</div>';
      $res.= "</div>";
    }

    elseif ($variation == 'comments_default')
    {
      $counter = 1;
      $res = "<div class=' row justify-content-center ". ($animations ? 'animate-bottom' : '') ."'>";

      foreach ($Attr['contents'] as $content)
      {
        $content = (array) $content;

        $image = $content['image'] ?? null;
        if (isset($content['image_folder']) && isset($content['image'])) $image = pg ."/uploads/images/{$content['image_folder']}/{$content['image']}";

        $res.= "
        <article class='". ($content['size'] ?? 'col-md-6 col-lg-4 col-xl-3') ." comments-default'>
        <div class='card ". ($animations ? 'animate-bottom' : '') ."'>
        <div class='card-body'>
        <div>

          <p class='feedback'>“{$content['content']}”</p>

          <div class='author-info'>
          <div class='img'>
            <img loading='lazy' height='50' width='50' alt='Imagem não carregada' src='{$image}'>
          </div>
          <div class='info'>
            <div>
            <p class='name'>{$content['title']}</p>
            <p class='position'>{$content['subtitle']}</p>
            <p class='rating'>⭐⭐⭐⭐⭐</p>
            </div>
          </div>
          </div>

        </div></div>
        </div>
        </article>";
      }
      $res .= "</div>";
    }

    return $res;
  }
}
