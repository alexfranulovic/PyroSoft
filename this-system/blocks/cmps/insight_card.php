<?php
if(!isset($seg)) exit;

/**
 * Renders a KPI insight card with optional comparison (baseline).
 *
 * Params:
 * - title (string)
 * - data (int|float)           Required
 * - last_data (int|float)      Optional (baseline)
 * - description (string)               Optional period label
 * - obs (string)                 Optional tooltip
 * - formatter (callable|string)  Optional formatter (e.g. BRL)
 * - size (string)                Optional bootstrap cols
 */
function insight_card(array $params = []): string
{
    /**
     * 1) Params & defaults
     */
    $size      = $params['size'] ?? 'col-lg-4 col-md-6';
    $title     = $params['title'] ?? '';
    $description = $params['description'] ?? '';
    $small     = $params['small'] ?? '';
    $obs       = $params['obs'] ?? '';
    $bg_color       = $params['color'] ?? '';
    $formatter = $params['formatter'] ?? null;

    $data = isset($params['data']) ? $params['data'] : 0;
    $text = $params['text'] ?? '';

    // Baseline is OPTIONAL
    $has_baseline = array_key_exists('last_data', $params);
    $last_data  = $has_baseline ? (float) $params['last_data'] : null;

    /**
     * 2) Formatter resolver
     */
    $fmt = null;
    if ($formatter) {
        if (is_callable($formatter)) {
            $fmt = $formatter;
        } elseif (is_string($formatter) && function_exists($formatter)) {
            $fmt = $formatter;
        }
    }

    $data_fmt = $fmt ? $fmt($data) : (string) $data;

    /**
     * Bypasses the formatation.
     */
    if (!empty($text)) {
        $data_fmt = $text;
    }

    /**
     * 3) Tooltip
     */
    $obs_html = '';
    if (!empty($obs))
    {
        $obs_html = "
            <span class='badge bg-secondary obs'
                  data-bs-toggle='tooltip'
                  data-bs-placement='top'
                  data-bs-original-title='{$obs}'>?</span>
        ";
    }

    /**
     * 4) Comparison logic (ONLY if baseline exists)
     */
    $comparison_html = '';

    if ($has_baseline) {

        $delta = $data - $last_data;

        // Percent only if baseline != 0
        if ($last_data != 0.0) {
            $delta_percent = round(($delta / $last_data) * 100, 1);
        } else {
            $delta_percent = 0;
        }

        $delta_percent_label = ($delta_percent > 0)
            ? "+{$delta_percent}"
            : (string) $delta_percent;

        if ($delta === 0.0) {
            $color = 'info';
            $arrow = '•';
        } elseif ($delta > 0.0) {
            $color = 'success';
            $arrow = '▲';
        } else {
            $color = 'danger';
            $arrow = '▼';
        }

        $delta_fmt       = $fmt ? $fmt($delta) : (string) $delta;
        $last_data_fmt = $fmt ? $fmt($last_data) : (string) $last_data;

        $comparison_html = "
            <div class='delta delta-{$color}'>
                <span class='arrow'>{$arrow}</span>
                <span class='percent'>{$delta_percent_label}%</span>
                <span class='value'>({$delta_fmt})</span>
            </div>

            <div class='base'>
                $description <strong>{$last_data_fmt}</strong>
            </div>
        ";
    }

    $footer = '';
    if (!empty($comparison_html) || !empty($small))
    {
        $footer = "
        <div class='footer'>
            {$comparison_html}
            " . (!empty($small) ? "<div class='period'>{$small}</div>" : "") . "
        </div>";
    }

    /**
     * 5) Render
     */
    $res = "
    <article class='{$size}'>
    <div class='insight-card bg-{$bg_color}'>
        <div class='content'>
            <div class='header'>
                <h2 class='card-title'>{$title}</h2>
                {$obs_html}
            </div>

            <div class='body'>
                <span class='number'>{$data_fmt}</span>
            </div>

            {$footer}

        </div>
    </div>
    </article>";

    return $res;
}
