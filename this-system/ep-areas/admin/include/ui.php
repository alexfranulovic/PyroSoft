<?php
if(!isset($seg)) exit;

/**
 * Generate the top section of the base page layout.
 *
 * This function is used to generate the top section of a base page layout, typically used in an admin panel.
 *
 * @param array $hooks_out An array of outgoing action hooks to be displayed on the top right corner of the page.
 * @param array $hooks_in An array of incoming action hooks to be processed and displayed within a dropdown menu.
 * @return void This function does not return a value; it directly generates and outputs HTML content.
 */
function pageBaseTop(array $hooks_out = [], array $hooks_in = [])
{
    global $page;

    echo"
    <section class='main-content table-responsive'>
    <header class='col-12'>
    <section>
        <h1>{$page['title']}</h1>
        <div class='btn-toolbar'>
        <div class='btn-group'>";
        foreach (array_filter($hooks_out) as $hook_out)
        {
            if (!empty($hook_out)) {
                $attr =  $hook_out['attr'] ?? null;
                echo "<a ". check_pg_in_url($hook_out['link'] ?? '') ." {$attr} title='{$hook_out['title']}' class='btn btn-{$hook_out['color']} btn-sm'>{$hook_out['title']}</a>";
            }
        }
        echo"
        </div>
        </div>
    </section>
    </header>";
}


/**
 * Renders a KPI insight card with optional comparison (baseline).
 *
 * Params:
 * - title (string)
 * - number (int|float)           Required
 * - last_number (int|float)      Optional (baseline)
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
    $formatter = $params['formatter'] ?? null;

    $number = isset($params['number']) ? (float) $params['number'] : 0.0;

    // Baseline is OPTIONAL
    $has_baseline = array_key_exists('last_number', $params);
    $last_number  = $has_baseline ? (float) $params['last_number'] : null;

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

    $number_fmt = $fmt ? $fmt($number) : (string) $number;

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

        $delta = $number - $last_number;

        // Percent only if baseline != 0
        if ($last_number != 0.0) {
            $delta_percent = round(($delta / $last_number) * 100, 1);
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
        $last_number_fmt = $fmt ? $fmt($last_number) : (string) $last_number;

        $comparison_html = "
            <div class='delta delta-{$color}'>
                <span class='arrow'>{$arrow}</span>
                <span class='percent'>{$delta_percent_label}%</span>
                <span class='value'>({$delta_fmt})</span>
            </div>

            <div class='base'>
                $description <strong>{$last_number_fmt}</strong>
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
    <div class='insight-card'>
        <div class='content'>
            <div class='header'>
                <h2 class='card-title'>{$title}</h2>
                {$obs_html}
            </div>

            <div class='body'>
                <span class='number'>{$number_fmt}</span>
            </div>

            {$footer}

        </div>
    </div>
    </article>";

    return $res;
}
