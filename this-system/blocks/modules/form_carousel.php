<?php
if(!isset($seg)) exit;


function form_carousel(array $Attr = [])
{
  $part      = $Attr['part'] ?? 0;
  $whole     = $Attr['whole'] ?? null;
  $min       = $Attr['min'] ?? 0;
  $max       = $Attr['max'] ?? 100;
  $text      = $Attr['text'] ?? '';
  $color     = $Attr['color'] ?? 'nd';
  $variation = $Attr['variation'] ?? 'progress_1';
  $height    = $Attr['height'] ?? '';


  $res = '';

  return $res;
}