<?php
if(!isset($seg)) exit;

function floating_card(array $Attr = [])
{
  if (!empty($Attr))
  {
    $position     = $Attr['position'] ?? 'fixed-bottom-start';
    $variation    = $Attr['variation'] ?? 'card_default';
    $counter      = $Attr['counter'] ?? '1';

    if (!empty($Attr['image_folder']) AND !empty($Attr['image'])) $Attr['image'] = pg ."/uploads/images/{$Attr['image_folder']}/{$Attr['image']}";

    $res = "<section id='floating-card-$counter' class='alert floating-card animate-left $position'>";
    $res.= "<button type='button' class='btn-close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>";
    $res.= !function_exists($variation) ? '<div>Esse tipo de card não existe.</div>' : $variation($Attr);
    $res.= "</section>";

    return $res;
  }
}
