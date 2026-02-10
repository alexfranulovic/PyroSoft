<?php
if(!isset($seg)) exit;

/**
 * Generates a vertical list section based on the provided attributes.
 *
 * @param array $Attr An array of attributes for the vertical list.
 */
function vertical_list(array $Attr = [])
{
  global $animations, $commands, $counter;

  if (!empty($Attr['contents']))
  {
    $variations = [
      'dot' => [ 'tag' => 'ul', 'type' => '' ],
      'numbered' => [ 'tag' => 'ol', 'type' => '' ],
      'roman' => [ 'tag' => 'ol', 'type' => 'type="I"' ],
      'literate' => [ 'tag' => 'ol', 'type' => 'type="a"' ],
      'verified-list' => [ 'tag' => 'ul', 'type' => 'type="verified-list"' ],
      'lock-list' => [ 'tag' => 'ul', 'type' => 'type="lock-list"' ],
    ];

    $variation = $Attr['variation'] ?? 'dot';
    $variation = $variations[$variation];


    $res = "<{$variation['tag']} {$variation['type']} class='". ($animations ? 'animate-bottom' : '') ."' id='section-list-$counter'>";
    foreach ($Attr['contents'] as $content)
    {
      $content = (array) $content;

      $class = !empty($content['class']) ? $content['class'] : '';

      $res.= "<li class='$class'>";
      $res.= !empty($content['url']) ? "<a ". check_pg_in_url($content['url']) .">" : '';
      $res.= format_text($content['content']);
      $res.= !empty($content['url']) ? "</a>" : '';
      $res.= "</li>";
    }
    $res.= "</{$variation['tag']}>";

    return $res;
  }
}