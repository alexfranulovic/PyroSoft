<?php
if(!isset($seg)) exit;

if(!function_exists('huge_button')) {
  /**
   * Generates a huge button section based on the provided attributes.
   *
   * @param array $Attr An array of attributes for the huge button.
   */
  function huge_button(array $Attr = [])
  {
    global $animations, $counter, $url;

    if (!empty($Attr))
    {
      $color = $Attr['color'] ?? 'primary';

      $result = "
      <a ". check_pg_in_url($url) ."
      class='btn btn-$color btn-lg btn-block ". ($animations ? 'animate-reverse-bottom' : '') ."'>".
        (!empty($Attr['title']) ? $Attr['title'] : "Ver mais") ."
      </a>";

      return $result;
    }
  }
}