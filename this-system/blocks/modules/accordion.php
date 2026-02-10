<?php
if(!isset($seg)) exit;

/**
 * Generates an accordion with collapsible items based on the given attributes.
 *
 * @param array $Attr An array of attributes for the accordion.
 */
function accordion(array $Attr = [])
{
  global $animations, $counter;

  $number = 1;
  if (!empty($Attr['contents']))
  {
    $variation = $Attr['variation'] ?? 'accordion-default';

    $FAQPage = [
      "@context" => "https://schema.org",
      "@type" => "FAQPage",
    ];

    $res = "<div class='accordion $variation ". ($animations ? 'animate-bottom' : '') ."'>";
    foreach ($Attr['contents'] as $content)
    {
      $content = (array) $content;

      $content['counter'] = $number;

      $show      = $content['counter']==1 ? 'show' : '';
      $collapsed = $content['counter']!=1 ? 'collapsed' : '';

      $attributes = !empty($content['attributes'])
        ? parse_html_tag_attributes($content['attributes'])
        : '';

      $res.= "
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

      $FAQPage['mainEntity'][] = [
        "@type" => "Question",
        "name" => $content['title'],
        "acceptedAnswer" => [
          "@type" => "Answer",
          "text" => $content['content'],
        ]
      ];

      $res.= variation($variation, $content);

      $number++;

    }
    $res.= "</div>";

    $res.= seo_structred_data($FAQPage, false);

    return $res;
  }
}
