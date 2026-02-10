<?php
if(!isset($seg)) exit;

function accordion_default(array $content = [])
{
  if (!empty($content))
  {
    $show      = $content['counter']==1 ? 'show' : '';
    $collapsed = $content['counter']!=1 ? 'collapsed' : '';

    $attributes = !empty($content['attributes'])
      ? parse_html_tag_attributes($content['attributes'])
      : '';

    $res = "
    <article $attributes class='accordion-item'>
      <h3 class='accordion-header' id='heading{$content['counter']}'>
        <button class='accordion-button $collapsed' type='button' data-bs-toggle='collapse' data-bs-target='#collapse{$content['counter']}' aria-expanded='true' aria-controls='collapse{$content['counter']}'>
          ". format_text($content['title']) ."
        </button>
      </h3>
      <div id='collapse{$content['counter']}' class='accordion-collapse collapse $show' aria-labelledby='heading{$content['counter']}' data-bs-parent='.accordion'>
        <div class='accordion-body'>". format_text($content['content']) ."</div>
      </div>
    </article>";

    return $res;
  }
}
