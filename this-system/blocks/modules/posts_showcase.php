<?php
if(!isset($seg)) exit;

/**
 * Generates a showcase section for blog posts based on the given attributes.
 *
 * @param array $Attr An array of attributes for the blog post showcase.
 */
function posts_showcase(array $Attr = [])
{
  global $animations, $counter, $variation;

  if (!empty($Attr['contents']))
  {

    $variation = $Attr['variation'] ?? 'posts_showcase_default';

    if ($variation == 'posts_showcase_as_blog')
    {
      $res = "<div class='row justify-content-center ". ($animations ? 'animate-bottom' : '') ."'>";
      foreach ($Attr['contents'] as $content)
      {
        $content = (array) $content;

        $image = $content['image'] ?? null;

        if (isset($content['image_folder']) && isset($content['image'])) $image = pg ."/uploads/images/{$content['image_folder']}/{$content['image']}";

        $comments = (isset($content['total_comments']) && $content['total_comments'] > 0) ? "<div><a href='{$content['url']}' title='Total de comentários' class='meta-chat d-flex'>". icon('fas fa-comments') ."</i>&nbsp;{$content['total_comments']}</a></div>" : '';
        $date  = !empty($content['date']) ? "<div><a href='{$content['url']}' title='Escrito em: " . strftime('%d/%m/%Y', strtotime($content['date'])) . "'>" . strftime('%d/%m/%Y', strtotime($content['date'])) . "</a></div>" : '';

        $res.= "
        <article class='posts-showcase-as-blog ". ($content['size'] ?? 'col-md-6 col-lg-4 col-xl-3') ."'>
        <div class='blog-entry align-self-stretch w-100'>

          <a href='{$content['url']}' class='block-20' style='background-image: url({$image})' alt='{$content['title']}' title='Saiba mais'></a>

          <div class='text mt-3 d-block'>
            <h3 class='heading mt-3'><a href='{$content['url']}' title='{$content['title']}'>{$content['title']}</a></h3>
            <div class='meta mb-3'>
              <div><a href='{$content['url']}' title='Escrito por: {$content['author']}'>{$content['author']}</a></div>
              $date
              $comments
            </div>
          </div>

        </div>
        </article>";
      }
      $res .='</div>';
    }

    elseif ($variation == 'posts_showcase_default')
    {
      $res = "<div class='row justify-content-center ". ($animations ? 'animate-bottom' : '') ."'>";
      foreach ($Attr['contents'] as $content)
      {
        $content = (array) $content;

        $image = $content['image'] ?? null;

        if (isset($content['image_folder']) && isset($content['image'])) $image = pg ."/uploads/images/{$content['image_folder']}/{$content['image']}";

        $res.= "
        <article class='". ($content['size'] ?? 'col-md-6 col-lg-4 col-xl-3') ." posts-showcase-default'>
        <div class='card ". ($animations ? 'animate-bottom' : '') ."'>

          <div class='card-body'>
            <img loading='lazy' alt='{$content['author']}' title='{$content['author']}' src='{$image}'>
            <p class='content'>“{$content['content']}”</p>
          </div>";

          if (!empty($content['url']))
          {
            $res.= "
            <div class='footer'>
              <a href='{$content['url']}' ". check_pg_in_url($content['url'] ?? '') ." title='Escrito por: {$content['author']}'>Ver matéria ". icon('fas fa-arrow-right') ."</a>
            </div>";
          }

        $res.= "
        </div>
        </article>";
      }
      $res .='</div>';
    }

    return $res;
  }
}
