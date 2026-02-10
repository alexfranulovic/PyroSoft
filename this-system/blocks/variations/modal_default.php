<?php
if(!isset($seg)) exit;

function modal_default(array $Attr = [])
{
  $title        = $Attr['title'] ?? '';
  $body         = $Attr['body'] ?? '';
  $footer       = $Attr['footer'] ?? '';

  $close_button = (isset($Attr['close_button']) && $Attr['close_button'] == true)
    ? "<button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>"
    : '';

  $res = "<div class='modal-content modal-default'>";
  $res.= (!empty($title) OR !empty($close_button)) ? "<div class='modal-header'><span>".format_text($title)."</span>$close_button</div>" : '';
  $res.= "<div class='modal-body'>".($body)."</div>";
  $res.= !empty($footer) ? "<div class='modal-footer'>".format_text($footer)."</div>" : '';
  $res.= "</div>";

  return $res;
}
