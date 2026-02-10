<?php
if(!isset($seg)) exit;

if(!function_exists('numbers')) {
  /**
   * Outputs a section with a list of site numbers.
   * 
   * @param array $Attr An array of attributes for the numbers.
   */
  function numbers(array $Attr = []) 
  {
    global $animations, $counter;

    if (!empty($Attr['contents']))
    {
      $result = "<div class='row justify-content-center ". ($animations ? 'animate-bottom' : '') ." py-5'>";
      foreach ($Attr['contents'] as $content)
      {
        $content = (array) $content;

        $result.= "
        <article class='". ($content['size'] ?? 'col-md-6 col-lg-4 col-xl-3') ." d-flex justify-content-center'>
        <div class='text text-center'>
          <strong class='number' data-number='{$content['content']}'>". $content['content'] ."</strong>
          <span>{$content['title']}</span>
        </div>
        </article>";
      }
      $result.='</div>';
      return $result;
    }
  }
}