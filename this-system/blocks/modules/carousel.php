<?php
if(!isset($seg)) exit;

/**
 * Generates a carousel with sliding images based on the given attributes.
 *
 * @param array $Attr An array of attributes for the carousel.
 */
function carousel(array $Attr = [])
{
  global $animations, $commands, $counter;


  if (!empty($Attr['contents']))
  {
    $count_carousels = count($Attr['contents']);

    $res = "<div id='carousel-$counter' class='carousel ". ($animations ? 'animate-bottom' : '') ." slide mb-3' data-bs-ride='carousel'>";

    /**
     *
     * Commands
     *
     */
    $res.='<ul class="carousel-indicators mb-0">';
    if ($commands && $count_carousels > 1)
    {
      $count = 0;
      foreach ($Attr['contents'] as $content)
      {
        $res.= "<li class='". ($count == 0 ? 'active' : '') ."'' data-bs-target='#carousel-$counter' data-bs-slide-to='$count'></li>";
        $count++;
      }
    }
    $res.='</ul>';


    /**
     *
     * Images
     *
     */
    $count = 0;
    $res.= "<div class='carousel-inner rounded'>";
    foreach ($Attr['contents'] as $content)
    {
      $content = (array) $content;

      if (isset($content['image_folder'])) $content['image'] = pg ."/uploads/images/{$content['image_folder']}/{$content['image']}";

      $href = check_pg_in_url($content['url'] ?? null);

      $a_open = !empty($content['url']) ? "<a $href class='carousel-hover' title='Ver link'>" : '';
      $a_close = !empty($content['url']) ? '</a>' : '';

      $res.= "
      <article class='carousel-item ". ($count == 0 ? 'active' : '') ."'>
        $a_open
        <img loading='lazy' class='second-slide img-fluid rounded w-100' src='{$content['image']}' alt='Imagem não carregada'>
        $a_close
      </article>";

     $count++;
    }
    $res.= "</div>";


    /**
     *
     * Commands
     *
     **/
    if ($commands && $count_carousels > 1)
    {
      $res.= "
      <a class='carousel-control-prev carousel-button carousel-button-pers' href='#carousel-$counter' role='button' data-bs-slide='prev' title='Voltar' style='border-radius: 0 10px 10px 0;'>
          <span class='carousel-control-prev-icon' aria-hidden='true'></span>
          <span class='sr-only'>Voltar</span>
      </a>
      <a class='carousel-control-next carousel-button carousel-button-pers' href='#carousel-$counter' role='button' data-bs-slide='next' title='Próximo' style='border-radius: 10px 0 0 10px;'>
          <span class='carousel-control-next-icon' aria-hidden='true'></span>
          <span class='sr-only'>Próximo</span>
      </a>";
    }

    $res.= "</div>";

    return $res;
  }
}
