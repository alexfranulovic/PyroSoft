<?php
if(!isset($seg)) exit;

if(!function_exists('fixed_message')) {
  /**
   * Generates a fixed message box with the specified body and position.
   *
   * @param array $Attr An array of attributes for the fixed message box.
   * @return string The HTML markup for the fixed message box.
   */
  function fixed_message(array $Attr = []) 
  {
    if (!empty($Attr))
    {
      $body     = $Attr['body'] ?? '';
      $position = $Attr['position'] ?? 'fixed-bottom';

      $result = "<div class='fixed-message $position' role='alert'><span>$body</span></div>";

      return $result;
    }
  }
}