<?php
if(!isset($seg)) exit;

if(!function_exists('division')) {
  /**
   * Generates a division HTML element with optional class and title.
   *
   * @param array $Attr An associative array containing attributes for the division.
   * @return string The HTML markup for the division element.
   */
  function division(array $Attr = []) 
  {
    if (!empty($Attr))
    {
      $class  = $Attr['class'] ?? '';
      $title  = $Attr['title'] ?? '';

      $result = "
      <div class='division $class'>
        <span>$title</span>
      </div>";

      return $result;
    }
  }
}