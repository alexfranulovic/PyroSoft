<?php
if(!isset($seg)) exit;

function modal_news(array $Attr = [])
{
  $title        = $Attr['title'] ?? '';
  $body         = $Attr['body'] ?? '';
  $footer       = $Attr['footer'] ?? '';

  $close_button = (isset($Attr['close_button']) && $Attr['close_button'] == true)
    ? "<button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>"
    : '';

  $res = "<div class='modal-content modal-news'>
  <div class='modal-body'>";
  $res.= (!empty($title) OR !empty($close_button)) ? "<div class='modal-header'><h2>".format_text($title)."</h2>$close_button</div>" : '';
  $res.= format_text($body);
  $res.= "</div>";

  $res.= !empty($footer) ? "<div class='modal-footer'>".format_text($footer)."</div>" : '';

  $res.= "</div>";

  return $res;
}
