<?php
if(!isset($seg)) exit;

if(!function_exists('videos')) {
  /**
   * Outputs a section with a list of site videos.
   *
   * @param array $Attr An array of attributes for the videos.
   */
  function videos(array $Attr = []) 
  {
    global $animations, $counter;

    if (!empty($Attr['contents']))
    {
      $result = "<div class='row justify-content-center ". ($animations ? 'animate-bottom' : '') ."'>";
      foreach ($Attr['contents'] as $content)
      {
        $content = (array) $content;

        $result .= "
        <article class='". ($content['size'] ?? 'col-md-6 col-lg-4 col-xl-3') ." pb-3 d-flex align-self-stretch '>
          <article class='embed-responsive embed-responsive-16by9'>{$content['url']}</article>
        </article>";
      }
      $result.='</div>';

      return $result;
    }
  }
}