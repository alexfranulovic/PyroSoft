<?php
if(!isset($seg)) exit;

function hero_vertical(array $Attr = [])
{
  global $class, $effect, $animations, $section_attributes, $counter, $container, $align, $background, $contents;

  $res = "
  <article class='hero-vertical'>
    <h1>". format_text($Attr['title']) ."</h1>
    <div class='col-lg-6 main'>
      <p class='lead'>". format_text($Attr['subtitle']) ."</p>
      <div class='content'>".
        (!empty($Attr['contents']['left']) ? $Attr['contents']['left'] : '') ."
      </div>
    </div>";

    if (!empty($contents['right']))
    {
      $res.= "<div class='aside'>";
      if($contents['right']['mode'] == 'crud')   $res.= crud_piece(['piece_id' => $Attr['crud_id']]);
      if($contents['right']['mode'] == 'image')  $res.= "<img loading='lazy' src='{$contents['right']['content']}' alt='Imagem não carregada'>";
      if($contents['right']['mode'] == 'custom') $res.= $contents['right']['content'];
      $res.= "</div>";
    }

  $res.= "
  </article>";

  return $res;
}
