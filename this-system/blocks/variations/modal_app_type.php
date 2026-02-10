<?php
if(!isset($seg)) exit;

function modal_app_type(array $Attr = [])
{
  $title        = $Attr['title'] ?? '';
  $body         = $Attr['body'] ?? '';
  $footer       = $Attr['footer'] ?? '';

  $res = "
  <div class='modal-content modal-app-type'>
  <div class='modal-body'>";
  $res.= !empty($title) ? "<h5>".format_text($title)."</h5>" : '';
  $res.= format_text($body);
  $res.= "</div>";
  $res.= !empty($footer) ? "<div class='modal-footer'>".format_text($footer)."</div>" : '';
  $res.= "</div>";

  return $res;
}