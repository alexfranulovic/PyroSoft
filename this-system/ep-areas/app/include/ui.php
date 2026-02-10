<?php
if(!isset($seg)) exit;


function feature_item($params = [])
{
    extract($params);

    $icon = !empty($icon)
        ? icon($icon)
        : '';

    $size = $size ?? 'col-12';
    $title = $title ?? '';
    $description = $description ?? '';

    return "
    <div class='$size'>
    <div class='item'>
      $icon
      <div>
        <h5>$title</h5>
        <p>$description</p>
      </div>
    </div>
    </div>";
}

function signature_box_mini($params = [])
{
    extract($params);

    $icon = !empty($icon)
      ? icon($icon)
      : '';

    $size = $size ?? 'col-12';
    $recurrence = $recurrence ?? '';
    $discount = $discount ?? '';
    $price = $price ?? '';
    $active = $active ?? false;
    $active = $active ? 'active' : '';

    $highlight = !empty($highlight)
      ? "<p class='highlight'>$highlight</p>"
      : '';

    return "
    <div class='$size'>
    <div class='premium-box $active'>
    $highlight
      <p class='recurrence'>$recurrence</p>
      <p class='discount'>$discount</p>
      <p class='price'>$price</p>
    </div>
    </div>";
}
