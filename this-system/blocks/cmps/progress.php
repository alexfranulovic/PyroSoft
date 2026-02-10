<?php
if(!isset($seg)) exit;


function progress(array $Attr = [])
{
  $part      = $Attr['part'] ?? 0;
  $whole     = $Attr['whole'] ?? null;
  $min       = $Attr['min'] ?? 0;
  $max       = $Attr['max'] ?? 100;
  $text      = $Attr['text'] ?? '';
  $color     = $Attr['color'] ?? 'nd';
  $class     = $Attr['class'] ?? '';
  $variation = $Attr['variation'] ?? 'progress_bar';
  $height    = $Attr['height'] ?? '';

  if (in_array($variation, ['progress_steps_detailed', 'progress_steps_summary', 'progress_steps_detailed_vertical', 'progress_steps_summary_vertical']))
  {
    $steps = array_values($Attr['steps'] ?? []);
    if (!$steps) return '';

    $total     = count($steps);
    $activeOne = max(1, min((int)($Attr['step_active'] ?? 1), $total));
    $activeIdx = $activeOne - 1;

    $percent = ($total > 1)
      ? round(($activeIdx / ($total - 1)) * 100)
      : 0;

    $isVertical = ($variation === 'progress_steps_detailed_vertical' || $variation === 'progress_steps_summary_vertical');
    $baseClass  = 'progress_steps_detailed' . ($isVertical ? ' progress-vertical' : '');

    $out  = "<article class='progress-content $class {$baseClass}' data-active='{$activeOne}' data-total='{$total}'>";
    $direction = $isVertical
      ? "height"
      : "width";

    $out .= "<div class='progress bg-{$color}' style='{$direction}: {$percent}%;'></div>";

    $out .= "<div class='steps'>";
    foreach ($steps as $i => $step)
    {
      $active = ($i < $activeOne) ? 'progress-step-active' : '';
      $number = $i + 1;

      // if (empty($step['title'])) continue;,

      $icon = !empty($step['icon'])
        ? icon($step['icon'])
        : null;

      $title = $step['title'] ?? '';
      $title = trim("$icon {$title}");

      if ($variation === 'progress_steps_detailed' || $variation === 'progress_steps_detailed_vertical') {
        $out .= "
        <div class='progress-step'>
          <div class='progress-step-circle bg-{$color} {$active}'>
            <span class='step-number'>{$number}</span>
          </div>
          <div class='progress-step-label'>{$title}</div>
        </div>";
      }

      else
      {
        $title = $icon ?? $number;

        $out .= "
        <div class='progress-step'>
          <div class='progress-step-circle bg-{$color} {$active}'>
            <span class='step-number'>{$title}</span>
          </div>
        </div>";
      }
    }
    $out .= "</div>";

    $out .= "</article>";
    return $out;
  }


  if (!empty($Attr['whole']) )
  {
    if (!empty($height)) {
      $height = "style='height: $height'";
    }

    $done = ($part / $whole) * 100;
    $done = number_format($done, 0,'.',',');

    $show_percentage = (!empty($Attr['show_percentage']) AND $Attr['show_percentage'])
      ? true
      : false;

    if ($show_percentage) {
      $text = "{$done}%";
    }

    $show_steps = (!empty($Attr['show_steps']) AND $Attr['show_steps'])
      ? true
      : '';

    if ($show_steps) {
      $show_steps = "<p>$part/$whole</p>";
    }

    $baseClass = '';
    if ($variation == 'progress_bar_striped') {
      $baseClass = 'progress-bar-striped';
    }

    elseif ($variation == 'progress_bar_striped_animated') {
      $baseClass = 'progress-bar-striped progress-bar-animated';
    }

    $res = "
    <article class='progress-content $class'>
      {$show_steps}
      <div class='progress' role='progressbar' aria-valuenow='$done' aria-valuemin='$min' aria-valuemax='$max' $height>
        <div class='progress-bar bg-$color $baseClass' style='width: $done%'>". format_text($text) ."</div>
      </div>
    </article>";

    return $res;
  }
}