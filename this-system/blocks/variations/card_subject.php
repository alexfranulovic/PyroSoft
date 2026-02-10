<?php
if(!isset($seg)) exit;

/**
 * Generates a card with a different layout based on the provided content.
 *
 * @param array $content An associative array containing the content for the card.
 * The array should include the following keys:
 * - 'image': The image to be displayed in the card.
 * - 'title': The title of the card.
 *
 * @return string The generated HTML markup for the card.
 */
function card_subject(array $content = [])
{
  if (!empty($content))
  {
    $attributes = $content['attributes'] ?? '';
    $url = check_pg_in_url($content['link_button']['url'] ?? '');

    return "
    <a $url $attributes title='{$content['title']}' class='card card-subject' style='background-image: url({$content['image']})'>
      <h3>{$content['title']}</h3>
    </a>";
  }
}
