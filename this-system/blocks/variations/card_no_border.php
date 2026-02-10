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
function card_no_border(array $content = [])
{
  if (!empty($content))
  {
    $res = "<div class='card-no-border'>";

    if (!empty($content['icon'])) {
      $res.= icon($content['icon']);
    }

    $res.= "
    <div class='card-body'>
    <h3>". format_text($content['title']) ."</h3>
    <p>". format_text($content['content']) ."</p>";

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
