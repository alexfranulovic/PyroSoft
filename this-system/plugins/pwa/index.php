<?php
if(!isset($seg)) exit;

function pwa_load_functions() {
    global $seg;
    require_once __DIR__ .'/src/manager-functions.php';
}


/**
 * Builds every <meta>/<link> tag the site's PWA needs and pushes it into
 * the head via add_assets('head', ...) — call this once inside <head>.
 * Reads the already-generated manifest.json (plugin mode) and does nothing
 * if it hasn't been generated yet.
 */
function load_pwa()
{
    global $seg;
    $manifest_path = __BASE_DIR__ . 'dist/scripts/manifest.json';

    $pwa_is_active = $GLOBALS['config']['pwa_is_active'] ?? '0';

    if (!is_file($manifest_path)) return;
    if ($pwa_is_active == 0) return;

    $manifest = json_decode(file_get_contents($manifest_path), true);
    if (empty($manifest)) return;


    $short_name   = $manifest['short_name'] ?? ($manifest['name'] ?? '');
    $theme_color  = $manifest['theme_color'] ?? '#000000';
    $manifest_url = rtrim(base_url, '/') . '/dist/scripts/manifest.json';

    // Grab the 192x192/512x512 icons already generated for the manifest, to
    // reuse as favicon/apple-touch-icon (iOS doesn't read icons from manifest.json).
    $icon_192 = $icon_512 = '';
    foreach ((array) ($manifest['icons'] ?? []) as $icon)
    {
        if (($icon['sizes'] ?? '') == '192x192') $icon_192 = $icon['src'] ?? '';
        if (($icon['sizes'] ?? '') == '512x512') $icon_512 = $icon['src'] ?? '';
    }

    // iOS doesn't infer the status bar color from theme_color: it needs to be
    // told explicitly. Compute luminance to pick 'black-translucent' (dark
    // theme) or 'default' (light theme), so the status bar text stays readable.
    $status_bar_style = 'default';
    if (preg_match('/^#?([a-f0-9]{6})$/i', $theme_color, $m))
    {
        $r = hexdec(substr($m[1], 0, 2));
        $g = hexdec(substr($m[1], 2, 2));
        $b = hexdec(substr($m[1], 4, 2));
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
        $status_bar_style = ($luminance < 0.5) ? 'black-translucent' : 'default';
    }

    $value = "\n<!-- PWA -->\n";

    // The manifest itself.
    $value.= "<link rel='manifest' href='". htmlspecialchars($manifest_url, ENT_QUOTES) ."'>\n";

    // Standard / Chromium / Android.
    $value.= "<meta name='theme-color' content='". htmlspecialchars($theme_color, ENT_QUOTES) ."'>\n";
    $value.= "<meta name='mobile-web-app-capable' content='yes'>\n";

    // iOS/Safari specific — Apple ignores most of the manifest.json above.
    $value.= "<meta name='apple-mobile-web-app-capable' content='yes'>\n";
    $value.= "<meta name='apple-mobile-web-app-status-bar-style' content='{$status_bar_style}'>\n";
    $value.= "<meta name='apple-mobile-web-app-title' content='". htmlspecialchars($short_name, ENT_QUOTES) ."'>\n";
    $value.= "<meta name='format-detection' content='telephone=no'>\n";

    // Icons — regular favicon + apple-touch-icon (iOS doesn't read manifest.json's "icons").
    if ($icon_192)
    {
        $value.= "<link rel='icon' type='image/png' sizes='192x192' href='". htmlspecialchars($icon_192, ENT_QUOTES) ."'>\n";
        $value.= "<link rel='apple-touch-icon' sizes='192x192' href='". htmlspecialchars($icon_192, ENT_QUOTES) ."'>\n";
    }
    if ($icon_512)
    {
        $value.= "<link rel='icon' type='image/png' sizes='512x512' href='". htmlspecialchars($icon_512, ENT_QUOTES) ."'>\n";
        $value.= "<link rel='apple-touch-icon' sizes='512x512' href='". htmlspecialchars($icon_512, ENT_QUOTES) ."'>\n";
    }

    // Windows/Edge tiles — not iOS, but standard alongside this group of metas.
    $value.= "<meta name='msapplication-TileColor' content='". htmlspecialchars($theme_color, ENT_QUOTES) ."'>\n";
    if ($icon_512) {
        $value.= "<meta name='msapplication-TileImage' content='". htmlspecialchars($icon_512, ENT_QUOTES) ."'>\n";
    }

    // viewport-fit=cover is what makes the installed (standalone) app respect
    // the notch/Dynamic Island safe area on iOS. Drop this line if <head>
    // already prints its own <meta name="viewport"> tag, to avoid a duplicate.
    $value.= "<meta name='viewport' content='width=device-width, initial-scale=1, viewport-fit=cover'>\n";

    add_asset('head', $value);
}
load_pwa();
