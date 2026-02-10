<?php
if(!isset($seg)) exit;

if(!function_exists('custom')) {
  /**
   * Outputs a section with long text.
   *
   * @param array $Attr An array of attributes for the custom.
   */
  function custom(array $Attr = []) 
  {
    global $animations, $counter;

    if (!empty($Attr['contents']))
    {
      $result ="<article class='". ($animations ? 'animate-bottom' : '') ."'>";
      $result.= $Attr['contents'];
      $result.= "</article>";

      return $result;
    }
  }
}