<?php
if(!isset($seg)) exit;

if(!function_exists('regressive_counter')) {
  /**
   * Generates a regressive counter section based on the provided attributes.
   *
   * @param array $Attr
   * @return string
   */
  function regressive_counter(array $Attr = [])
  {
    global $animations;

    // -------------------------------------------------------
    // Resolve final moment (default: now + 15 minutes)
    // -------------------------------------------------------
    $tz = new DateTimeZone('America/Sao_Paulo');

    $final = null;

    if (!empty($Attr['final_moment'])) {
      $final_moment = (array)(is_json($Attr['final_moment'])
        ? json_decode($Attr['final_moment'], true)
        : $Attr['final_moment']
      );

      $date = trim((string)($final_moment['date'] ?? ''));
      $time = trim((string)($final_moment['time'] ?? ''));

      // Accept "HH:ii" or "HH:ii:ss"
      if ($date !== '' && $time !== '') {
        if (preg_match('/^\d{2}:\d{2}$/', $time)) $time .= ':00';

        $final = DateTime::createFromFormat('Y-m-d H:i:s', "{$date} {$time}", $tz);

        // Fallback if parsing failed
        if (!$final) $final = null;
      }
    }

    if (!$final) {
      $final = new DateTime('now', $tz);
      $final->modify('+15 minutes');
    }

    $final_str = $final->format('Y-m-d H:i:s');

    // -------------------------------------------------------
    // Pre-calc remaining time (server side) to hide 0 blocks
    // and avoid default "0 0 0"
    // -------------------------------------------------------
    $now = new DateTime('now', $tz);

    $diff_seconds = $final->getTimestamp() - $now->getTimestamp();
    if ($diff_seconds < 0) $diff_seconds = 0;

    // Same approximations used in your JS
    $sec_year  = (int)round(60 * 60 * 24 * 365.25);
    $sec_month = (int)round(60 * 60 * 24 * 30.44);
    $sec_day   = 60 * 60 * 24;
    $sec_hour  = 60 * 60;
    $sec_min   = 60;

    $years  = (int)floor($diff_seconds / $sec_year);
    $rem    = $diff_seconds % $sec_year;

    $months = (int)floor($rem / $sec_month);
    $rem    = $rem % $sec_month;

    $days   = (int)floor($rem / $sec_day);
    $rem    = $rem % $sec_day;

    $hours  = (int)floor($rem / $sec_hour);
    $rem    = $rem % $sec_hour;

    $minutes= (int)floor($rem / $sec_min);
    $seconds= (int)floor($rem % $sec_min);

    $pad = fn($n) => str_pad((string)$n, 1, '0', STR_PAD_LEFT);

    // Helper to render a block
    $block = function(string $data_attr, string $label, int $value) use ($pad)
    {
      $content = explode('-', $data_attr);
      $content = $content[1];

      return "
      <div class='content {$content}'>
        <span class='number' {$data_attr}>{$pad($value)}</span>
        <span class='title'>{$label}</span>
      </div>";
    };

    $result  = "<article class='row justify-content-center ". ($animations ? 'animate-bottom' : '') ."' data-final-moment='{$final_str}'>";

    // Hide years/months/days if 0 (same behavior as JS)
    if ($years  > 0) $result .= $block("data-years",   "Anos",    $years);
    if ($months > 0) $result .= $block("data-months",  "Meses",   $months);
    if ($days   > 0) $result .= $block("data-days",    "Dias",    $days);

    // Always show these
    $result .= $block("data-hours",   "Horas",   $hours);
    $result .= $block("data-minutes", "Minutos", $minutes);
    $result .= $block("data-seconds", "Segundos",$seconds);

    $result .= "</article>";

    return $result;
  }
}
