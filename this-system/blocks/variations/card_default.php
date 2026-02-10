<?php
if(!isset($seg)) exit;

/**
 * Generates a card with a different layout based on the provided content.
 *
 * @param array $content An associative array containing the content for the card.
 * The array should include the following keys:
 * - 'icon': The HTML class for the icon to be displayed in the card.
 * - 'title': The title of the card.
 * - 'content': The content to be displayed in the card.
 *
 * @return string The generated HTML markup for the card.
 */
function card_default(array $content = [])
{
  if (!empty($content))
  {
    $bg_color = $content['color'] ?? 'light';
    $style    = !empty($content['effect']) ? "style='background-image: linear-gradient({$content['effect']});'" : '';

    $image = $content['image'] ?? null;

    $res = "<div class='card card-default border-0 bg-$bg_color shadow rounded p-4 w-100' $style>";

    if (!empty($content['image'])) {
      $res.= "<div class='rounded-top block-20' alt='{$content['title']}' style='background-image: url({$image})'></div>";
    }

    $res.= "
    <div class='card-body'>";
    if (!empty($content['icon'])) {
      $res.= icon($content['icon']);
    }

    $res.="
    <h3 class='heading'>". format_text($content['title'] ?? '') ."</h3>
    <p>". format_text($content['content'] ?? '') ."</p>";

    if (!empty($content['link_button']['url']))
    {
      $btn_title = !empty($content['link_button']['title'])
        ? $content['link_button']['title']
        : 'Ver mais';
      $res.= "<div class='card-footer'><a ". check_pg_in_url($content['link_button']['url']) ." class='btn btn-primary'>$btn_title</a></div>";
    }

    $res.="
    </div>
    </div>";

    return $res;
  }
}