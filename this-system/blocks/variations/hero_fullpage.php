<?php
if(!isset($seg)) exit;

function hero_fullpage(array $Attr = [])
{
  global $class, $effect, $animations, $section_attributes, $counter, $container, $align, $background, $contents;

  $res = "
  <article class='hero-fullpage' data-fullheightPage>";

  $res.= "<div>";
  $res .= "<h1 class='". ($animations ? 'animate-top' : '') ."'>". format_text($Attr['title']) ."</h1>";
  $res .= !empty($Attr['subtitle']) ? "<h2 class='". ($animations ? 'animate-top' : '') ."'><span>". format_text($Attr['subtitle']) ."</span></h2>" : '';
  $res .= !empty($Attr['contents']) ? $Attr['contents'] : '';
  $res .= '</div>';

  $res.= "
  </article>";

  return $res;
}
