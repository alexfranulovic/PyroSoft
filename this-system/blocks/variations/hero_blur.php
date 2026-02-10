<?php
if(!isset($seg)) exit;

function hero_blur(array $Attr = [])
{
  global $class, $effect, $animations, $section_attributes, $counter, $container, $align, $background, $contents;

  $res = "
  <article class='hero-blur'>
  <div class='row'>";

  $res.= "
  <section class='col-lg main'>
  <div class='content'>
    <h1>". format_text($Attr['title']) ."</h1>
    <p class='lead'>". format_text($Attr['subtitle']) ."</p>
    <div class='footer'>".
      (!empty($Attr['contents']['left']) ? $Attr['contents']['left'] : '') ."
    </div>
  </div>
  </section>";

  if (!empty($contents['right']))
  {
    $res.= "<div class='col-lg-4 aside'>";
    if($contents['right']['mode'] == 'crud')   $res.= crud_piece(['piece_id' => $Attr['crud_id']]);
    if($contents['right']['mode'] == 'image')  $res.= "<img loading='lazy' src='{$contents['right']['content']}' alt='Imagem não carregada' width='720'>";
    if($contents['right']['mode'] == 'custom') $res.= $contents['right']['content'];
    $res.= "</div>";
  }

  $res.= "
  </div>
  </article>";

  return $res;
}
