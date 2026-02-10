<?php
if(!isset($seg)) exit;

function audio_player(array $params = [])
{
  $class  = $params['class'] ?? '';
  $remove = $params['remove'] ?? false;
  $src    = $params['src'] ?? '';

  $btn_remove = $remove
    ? "<button type='button' class='btn btn-remove'>✕</button>"
    : '';

  if (empty($src)) return '';

  $res = "
  <div class='audio-player $class' data-audio='$src'>
    <button type='button' class='play-btn'>". icon('fas fa-play') ."</button>
    <button type='button' class='pause-btn' style='display: none;'>". icon('fas fa-pause') ."</button>
    <div class='progress-bar'><div class='progress-fill'></div></div>
    <span class='time'>--:--</span>
    $btn_remove
  </div>";

  add_asset('footer', "<script src='".base_url."/dist/scripts/filesPreviewer.js' defer></script>");

  return $res;
} 
