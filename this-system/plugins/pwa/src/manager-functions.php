<?php
if(!isset($seg)) exit;

/**
 * Uploads for icons/screenshots are async now: by the time the form POSTs,
 * the file already sits in /uploads/temp and all we get here is the temp
 * filename (JSON-encoded by the upload widget, e.g. '["name-(tempfile-abc).png"]').
 *
 * This pulls that filename out and moves it to its final destination with
 * move_temp_file_to_final(), returning the resulting filename. If the value
 * isn't a temp filename (the field was left untouched on an edit, so it's
 * already the final stored filename), it's returned as-is — nothing to move.
 *
 * @param mixed  $raw        The posted value for one size, e.g. '["...tempfile....png"]'.
 * @param string $final_name Desired final base name (no extension), e.g. 'icon-192x192'.
 * @param string $dest_base  Destination folder under /uploads/, e.g. 'pwa-images'.
 *
 * @return string|null The final filename, or null when there's nothing to use.
 */
function pwa_finalize_upload_field($raw, string $final_name, string $dest_base)
{
    if (empty($raw)) return null;

    $list     = is_json($raw) ? json_decode($raw, true) : $raw;
    $filename = is_array($list) ? ($list[0] ?? null) : $list;

    $final_name.= '-'.rand(0,100);

    if (empty($filename)) return null;
    $filename = basename($filename);

    // Already final (kept from a previous save) — nothing to move.
    if (!is_temp_filename($filename)) return $filename;

    $dest_filename = build_final_filename($filename, $final_name);

    $moved = move_temp_file_to_final($filename, [
        'storage'    => 'local',
        'temp_dir'   => rtrim(__BASE_DIR__, '/') . '/uploads/temp',
        'dest_base'  => $dest_base,
        'final_name' => $final_name,
    ]);

    // print_r($raw);
    // print_r($dest_filename);
    // print_r($moved);

    // return !empty($moved) ? $moved : $dest_filename;
    // return $moved ?? $dest_filename;
    // return $moved ? $dest_filename : null;
    return $dest_filename;
}


/**
 * Reads the real width/height of an already-finalized local image.
 *
 * Icons still use fixed sizes (192x192/512x512), but screenshots and
 * shortcut icons now come from a field_repeater — any number of uploads,
 * any size — so their manifest "sizes" value has to be detected from the
 * actual file instead of being known upfront.
 *
 * @return string|null "WxH", or null if it can't be read.
 */
function pwa_local_image_size(string $dest_base, string $filename)
{
    $path = rtrim(__BASE_DIR__, '/') . "/uploads/{$dest_base}/{$filename}";
    if (!is_file($path)) return null;

    $size = @getimagesize($path);
    if (empty($size[0]) OR empty($size[1])) return null;

    return "{$size[0]}x{$size[1]}";
}


function generate_pwa_manifest($args, $mode = 'plugin')
{
    if (empty($args)) return false;

    $args       = filter_empty_values($args);

    $id         = $args['id'];
    $url        = parse_url( (($mode!='plugin') ? $id : pg) );
    $args['id'] = $url['host'];

    // Where the finished icons/screenshots live locally, under /uploads/.
    // Kept the same for both modes: on 'generator' mode this is only a
    // transient relay — the real deliverable is the zip below, and this
    // local copy is just where move_temp_file_to_final() can put the file.
    $images_dest_base = 'images/pwa-images';

    $zip_staging_folder = 'euphoric-pwa'; // only used for 'generator' mode

    $src = ($mode=='generator')
        ? "{$url['scheme']}://{$url['host']}/{$zip_staging_folder}/"
        : rtrim(base_url, '/') . "/uploads/{$images_dest_base}/";

    $files2zip = [];

    // Icons/screenshots/shortcuts arrive as their own POST fields now (async
    // upload), not $_FILES — pull them out before the generic "copy
    // everything else" loop below. Screenshots and shortcuts come from the
    // field_repeater under "pwa" (any number of rows), unlike icons which
    // keep their two fixed "WxH" keys.
    $icons_payload    = $args['icons'] ?? [];
    $repeaters        = $args['pwa'] ?? [];
    $screenshots_rows = (array) ($repeaters['screenshots'] ?? []);
    $shortcuts_rows   = (array) ($repeaters['shortcuts'] ?? []);
    unset($args['icons'], $args['pwa']);


    // Treat the icons.
    foreach ((array) $icons_payload as $size => $raw)
    {
        $filename = pwa_finalize_upload_field($raw, "icon-{$size}", $images_dest_base);

        if (empty($filename)) continue;

        if ($mode == 'generator')
        {
            $local_path = rtrim(__BASE_DIR__, '/') . "/uploads/{$images_dest_base}/{$filename}";
            $staged_dir = __BASE_DIR__ . "{$zip_staging_folder}/";
            if (!is_dir($staged_dir)) @mkdir($staged_dir, 0755, true);
            @copy($local_path, $staged_dir . $filename);
            $files2zip[] = $staged_dir . $filename;
        }

        $result['icons'][] = [
           'src'     => $src . $filename,
           'sizes'   => $size,
           'type'    => "image/png",
           "purpose" => ($size == '512x512') ? "maskable" : "any",
        ];
    }


    // Treat the screenshots (field_repeater rows — one upload per row, any
    // number of them; size is read from the actual file since it's no
    // longer a fixed "WxH" key).
    foreach ($screenshots_rows as $i => $row)
    {
        $row = (array) $row;

        $filename = pwa_finalize_upload_field($row['image'] ?? null, "screenshot-{$i}", $images_dest_base);
        if (empty($filename)) continue;

        if ($mode == 'generator')
        {
            $local_path = rtrim(__BASE_DIR__, '/') . "/uploads/{$images_dest_base}/{$filename}";
            $staged_dir = __BASE_DIR__ . "{$zip_staging_folder}/";
            if (!is_dir($staged_dir)) @mkdir($staged_dir, 0755, true);
            @copy($local_path, $staged_dir . $filename);
            $files2zip[] = $staged_dir . $filename;
        }

        $entry = [
            'src'         => $src . $filename,
            'sizes'       => pwa_local_image_size($images_dest_base, $filename) ?? '',
            'type'        => 'image/png',
            'form_factor' => ($row['form_factor'] ?? '') == 'wide' ? 'wide' : 'narrow',
        ];

        if (!empty($row['label'])) $entry['label'] = $row['label'];

        $result['screenshots'][] = $entry;
    }


    // Treat the shortcuts (field_repeater rows — quick actions on the app icon).
    foreach ($shortcuts_rows as $i => $row)
    {
        $row = (array) $row;

        // name + url are the only required fields per the manifest spec.
        if (empty($row['name']) OR empty($row['url'])) continue;

        $entry = [
            'name' => $row['name'],
            'url'  => $row['url'],
        ];

        if (!empty($row['description'])) $entry['description'] = $row['description'];

        $icon_filename = pwa_finalize_upload_field($row['icon'] ?? null, "shortcut-{$i}", $images_dest_base);
        if (!empty($icon_filename))
        {
            if ($mode == 'generator')
            {
                $local_path = rtrim(__BASE_DIR__, '/') . "/uploads/{$images_dest_base}/{$icon_filename}";
                $staged_dir = __BASE_DIR__ . "{$zip_staging_folder}/";
                if (!is_dir($staged_dir)) @mkdir($staged_dir, 0755, true);
                @copy($local_path, $staged_dir . $icon_filename);
                $files2zip[] = $staged_dir . $icon_filename;
            }

            $entry['icons'] = [[
                'src'   => $src . $icon_filename,
                'sizes' => pwa_local_image_size($images_dest_base, $icon_filename) ?? '',
                'type'  => 'image/png',
            ]];
        }

        $result['shortcuts'][] = $entry;
    }


    // Treat the datas.
    foreach ($args as $key => $value)
    {
        // if ($key != 'start_url' AND $key != 'offline_page')
        // {
            $result[$key] = $value;
            // continue;
        // }

        // $result[$key] = ($mode != 'generator')
        // ? get_url_page($value, 'full')
        // : $value;
    }


    // Manifest
    $manifest = __BASE_DIR__ . (($mode == 'plugin')
        ? 'dist/scripts/manifest.json'
        : "uploads/archives/pwa-json/manifest.json");

    if ($mode == 'generator') $files2zip[] = $manifest;


    // Build the manifest.json
    if (!is_dir(dirname($manifest))) @mkdir(dirname($manifest), 0755, true);

    $file = fopen($manifest, 'w');
    if (!$file) return false;

    $json = !is_json($result) ? json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : $result;

    fwrite($file, $json);
    fclose($file);


    // Generate the Zip
    if ($mode == 'generator')
    {
        $folder = 'euphoric-pwa';
        $filename   = __BASE_DIR__ . "archieves/pwa-zip/{$args['id']}-". random_name() .'.zip';

        if (!is_dir(dirname($filename))) @mkdir(dirname($filename), 0755, true);

        $zip_params = [
            'files' => $files2zip,
            'filename' => $filename,
            'folder' => 'euphoric-pwa',
        ];

        if (zip_files($zip_params)) force_download($filename);

        if (is_dir(__BASE_DIR__ ."/". $folder)) delete_folder(__BASE_DIR__ ."/". $folder);
    }

    update_option('pwa_is_active', $args['pwa_is_active'] ?? '0');

    return [
        'code' => 'success',
        'detail' => [
            'type' => 'toast',
            'msg' => alert_message("SC_TO_INSERT", 'toast')
        ],
    ];
}


/**
 * Looks up the previously generated icon (from the stored manifest info)
 * for a given "WxH" size, so the upload field can show its current preview.
 *
 * @param array  $info Current PWA info/manifest data (global $info).
 * @param string $size e.g. '192x192' or '512x512'.
 *
 * @return string The icon src, or '' when there is none yet.
 */
function pwa_get_icon_src(array|null $info, string $size)
{
    if (empty($info['icons']) OR !is_array($info['icons'])) return '';

    foreach ($info['icons'] as $icon)
    {
        $icon = (array) $icon;
        if (($icon['sizes'] ?? '') == $size)
        {
            $src = $icon['src'] ?? '';
            $src = explode('/', $src);
            $src = array_reverse($src);

            return $src[0];
        }
    }

    return '';
}


/**
 * Tries to read the already-generated manifest.json so the form can be
 * prefilled from it — makes it easy to edit an existing PWA config.
 * Only meaningful for 'plugin' mode: 'generator' mode serves a different
 * external site on every run, so there's no single manifest to read back.
 * Uses the exact same path generate_pwa_manifest() writes to.
 *
 * @return array|null Decoded manifest, or null if there isn't one (yet).
 */
function pwa_read_manifest_info(string $mode = 'plugin')
{
    if ($mode != 'plugin') return null;

    $manifest = __BASE_DIR__ . 'dist/scripts/manifest.json';
    if (!is_file($manifest)) return null;

    $data = json_decode(file_get_contents($manifest), true);

    return is_array($data) ? $data : null;
}


/**
 * Same idea as pwa_get_icon_src(), but for the screenshots field_repeater:
 * each stored screenshot's "src" is a full URL, and reduces it to a bare
 * filename so the repeater's upload rows can load their preview correctly.
 *
 * @param mixed $screenshots Stored "screenshots" entries (manifest/info).
 * @return array Rows ready to use as the repeater's Value.
 */
function pwa_get_screenshots_src($screenshots): array
{
    if (empty($screenshots) OR !is_array($screenshots)) return [];

    $rows = [];

    foreach ($screenshots as $screenshot)
    {
        $screenshot = (array) $screenshot;

        $src = explode('/', $screenshot['src'] ?? '');
        $src = array_reverse($src);

        $screenshot['src'] = $src[0] ?? '';

        $rows[] = $screenshot;
    }

    return $rows;
}


/**
 * Same idea as pwa_get_icon_src(), but for the shortcuts field_repeater:
 * flattens each shortcut's first icon into a flat "icon_src" key (matching
 * the repeater child's og_name) and reduces it to a bare filename, so the
 * repeater's icon upload rows can load their preview correctly.
 *
 * @param mixed $shortcuts Stored "shortcuts" entries (manifest/info).
 * @return array Rows ready to use as the repeater's Value.
 */
function pwa_get_shortcuts_src($shortcuts): array
{
    if (empty($shortcuts) OR !is_array($shortcuts)) return [];

    $rows = [];

    foreach ($shortcuts as $shortcut)
    {
        $shortcut = (array) $shortcut;
        $icons    = (array) ($shortcut['icons'] ?? []);
        $icon     = (array) ($icons[0] ?? []);

        $src = explode('/', $icon['src'] ?? '');
        $src = array_reverse($src);

        $shortcut['icon_src'] = $src[0] ?? '';

        $rows[] = $shortcut;
    }

    return $rows;
}


function manage_pwa_form(string $mode = 'plugin')
{
    global $info;

    $manifest = pwa_read_manifest_info($mode);
    // $mode = 'plugin'    - For PyroSoft's websites
    // $mode = 'generator' - For other websites
    if ($mode != 'plugin' AND $mode != 'generator') {
        return 'No valid mode was selected';
    }


    /**
     * Tab: Info
     */
    $info_childs = [
        [
            'type_field' => 'basic',
            'size' => 'col-12',
            'type' => 'url',
            'label' => 'Put your URL',
            'attributes' => ($mode != 'generator') ? 'readonly:();' : '',
            'name' => 'id',
            'input_id' => 'id',
            'Required' => true,
            'Value' => ($mode != 'generator') ? (parse_url(pg)['host'] ?? '') : '',
        ],
        [
            'type_field' => 'basic',
            'size' => 'col-md-6',
            'label' => 'Application Name',
            'name' => 'name',
            'input_id' => 'name',
            'Value' => $manifest['name'] ?? ($info['name'] ?? ''),
            'Required' => true,
        ],
        [
            'type_field' => 'basic',
            'attributes' => 'minlength:(0);maxlength:(20);',
            'size' => 'col-md-6',
            'label' => 'Application Short Name',
            'name' => 'short_name',
            'input_id' => 'short_name',
            'Value' => $manifest['short_name'] ?? ($info['short_name'] ?? ''),
            'obs' => 'Used when there is insufficient space to display the full name of the application. 20 characters or less.',
            'Required' => true,
        ],
        [
            'type_field' => 'textarea',
            'size' => 'col-12',
            'label' => 'Description',
            'name' => 'description',
            'input_id' => 'description',
            'Value' => $manifest['description'] ?? ($info['description'] ?? ''),
            'obs' => 'A brief description of what your app is about.',
        ],
        [
            'type_field' => 'basic',
            'type' => 'color',
            'size' => 'col-md-6',
            'label' => 'Background Color',
            'name' => 'background_color',
            'input_id' => 'background_color',
            'Value' => $manifest['background_color'] ?? ($info['brand_colors']['primary'] ?? ''),
            'obs' => 'Background color of the splash screen.',
        ],
        [
            'type_field' => 'basic',
            'type' => 'color',
            'size' => 'col-md-6',
            'label' => 'Theme Color',
            'name' => 'theme_color',
            'input_id' => 'theme_color',
            'Value' => $manifest['theme_color'] ?? ($info['brand_colors']['secondary'] ?? ''),
            'obs' => 'Theme color is used on supported devices to tint the UI elements of the browser and app switcher. When in doubt, use the same color as Background Color',
        ],
        [
            'type_field' => 'selection_type',
            'Options' => [
                [
                    'value' => '1',
                    'display' => 'Yes',
                    'checked' => ($GLOBALS['config']['pwa_is_active']=='1'),
                ],
                [
                    'value' => '0',
                    'display' => 'No',
                    'checked' => ($GLOBALS['config']['pwa_is_active']=='0'),
                ],
            ],
            'type' => 'radio',
            'variation' => 'balloons',
            'size' => 'col-12',
            'label' => 'Turn PWA active',
            'name' => 'pwa_is_active',
            'input_id' => 'pwa_is_active',
            // 'Value' => (string)$GLOBALS['config']['pwa_is_active'],
        ],
    ];

    /**
     * Tab: Settings
     */
    $settings_childs = [];

    if ($mode == 'generator')
    {
        // External sites don't have a page CRUD to pick from, so these
        // stay as free-text paths (e.g. "/" or "/offline.html").
        $settings_childs[] = [
            'type_field' => 'basic',
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Start Page',
            'name' => 'start_url',
            'input_id' => 'start_url',
            'Value' => $manifest['start_url'] ?? ($info['start_url'] ?? '/'),
        ];
        $settings_childs[] = [
            'type_field' => 'basic',
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Offline Page',
            'name' => 'offline_page',
            'input_id' => 'offline_page',
            'Value' => $manifest['offline_page'] ?? ($info['offline_page'] ?? '/'),
            'obs' => 'Offline page is displayed when the device is offline and the requested page is not already cached.',
        ];
    }

    else
    {
        $settings_childs[] = [
            'type_field' => 'selection_type',
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Start Page',
            'name' => 'start_url',
            'input_id' => 'start_url',
            'options_resolver' => 'get_pages_for_select("full_url")',
            'type' => 'search',
            'Value' => $manifest['start_url'] ?? ($info['start_url'] ?? ''),
        ];
        $settings_childs[] = [
            'type_field' => 'selection_type',
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Offline Page',
            'name' => 'offline_page',
            'input_id' => 'offline_page',
            'options_resolver' => 'get_pages_for_select("full_url")',
            'type' => 'search',
            'Value' => $manifest['offline_page'] ?? ($info['offline_page'] ?? ''),
            'obs' => 'Offline page is displayed when the device is offline and the requested page is not already cached.',
        ];
    }

    $settings_childs[] = [
        'type_field' => 'selection_type',
        'Options' => [
            ['value' => 'fullscreen', 'display' => 'Fullscreen'],
            ['value' => 'standalone', 'display' => 'Standalone'],
            ['value' => 'browser', 'display' => 'Browser'],
            ['value' => 'minimal-ui', 'display' => 'Minimal-ui'],
        ],
        'size' => 'col-md-6 col-lg-4',
        'label' => 'Display Mode',
        'name' => 'display',
        'input_id' => 'display',
        'Value' => $manifest['display'] ?? ($info['display'] ?? 'standalone'),
        'obs' => 'Display mode decides what browser UI is shown when your app is launched. Standalone is default.',
    ];
    $settings_childs[] = [
        'type_field' => 'selection_type',
        'size' => 'col-md-6 col-lg-4',
        'label' => 'Direction',
        'name' => 'dir',
        'input_id' => 'dir',
        'type' => 'radio',
        'variation' => 'balloons',
        'Options' => [
            ['value' => 'ltr', 'display' => 'Left to Right'],
            ['value' => 'rtl', 'display' => 'Right to Left'],
            ['value' => 'auto', 'display' => 'Auto'],
        ],
        'Value' => $manifest['dir'] ?? ($info['dir'] ?? 'ltr'),
        'obs' => 'The text direction of your PWA',
    ];
    $settings_childs[] = [
        'type_field' => 'selection_type',
        'size' => 'col-md-6 col-lg-4',
        'label' => 'Orientation',
        'name' => 'orientation',
        'input_id' => 'orientation',
        'Options' => [
            ['value' => 'any', 'display' => 'Any'],
            ['value' => 'natural', 'display' => 'Natural'],
            ['value' => 'landscape', 'display' => 'Landscape'],
            ['value' => 'landscape-primary', 'display' => 'Landscape Primary'],
            ['value' => 'landscape-secondary', 'display' => 'Landscape Secondary'],
            ['value' => 'portrait', 'display' => 'Portrait'],
            ['value' => 'portrait-primary', 'display' => 'Portrait Primary'],
            ['value' => 'portrait-secondary', 'display' => 'Portrait Secondary'],
        ],
        'Value' => $manifest['orientation'] ?? ($info['orientation'] ?? 'any'),
        'obs' => 'Set the orientation of your app on devices. When set to Follow Device Orientation your app will rotate as the device is rotated.',
    ];
    $settings_childs[] = [
        'type_field' => 'basic',
        'size' => 'col-md-6 col-lg-4',
        'label' => 'Scope',
        'name' => 'scope',
        'input_id' => 'scope',
        'Value' => $manifest['scope'] ?? ($info['scope'] ?? '/'),
    ];
    $settings_childs[] = [
        'type_field' => 'selection_type',
        'type' => 'search',
        'Options' => [
            ['value' => 'business', 'display' => 'Business'],
            ['value' => 'education', 'display' => 'Education'],
            ['value' => 'entertainment', 'display' => 'Entertainment'],
            ['value' => 'finance', 'display' => 'Finance'],
            ['value' => 'games', 'display' => 'Games'],
            ['value' => 'health', 'display' => 'Health'],
            ['value' => 'lifestyle', 'display' => 'Lifestyle'],
            ['value' => 'music', 'display' => 'Music'],
            ['value' => 'news', 'display' => 'News'],
            ['value' => 'productivity', 'display' => 'Productivity'],
            ['value' => 'shopping', 'display' => 'Shopping'],
            ['value' => 'social', 'display' => 'Social'],
            ['value' => 'sports', 'display' => 'Sports'],
            ['value' => 'travel', 'display' => 'Travel'],
            ['value' => 'utilities', 'display' => 'Utilities'],
        ],
        'size' => 'col-md-6 col-lg-4',
        'label' => 'App Category',
        'name' => 'app_category',
        'input_id' => 'app_category',
        'Value' => $manifest['app_category'] ?? ($info['app_category'] ?? ''),
    ];


    /**
     * Tab: Icons
     */
    $icons_childs = [
        [
            'type_field' => 'upload',
            'type' => 'images',
            'size' => 'col-md-6',
            'label' => 'Application Icon',
            // 'accepted_extensions' => 'image/x-png',
            'name' => "icons[192x192]",
            'input_id' => "src-192x192",
            'Value' => pwa_get_icon_src($manifest, '192x192'),
            'Src' => 'pwa-images',
            'Required' => true,
            'obs' => 'This will be the icon of your app when installed on the phone. Must be a <<PNG>> image exactly <<192x192>> in size.',
        ],
        [
            'type_field' => 'upload',
            'type' => 'images',
            'size' => 'col-md-6',
            'label' => 'Splash Screen Icon',
            // 'accepted_extensions' => 'image/x-png',
            'name' => "icons[512x512]",
            'input_id' => "src-512x512",
            'Value' => pwa_get_icon_src($manifest, '512x512'),
            'Src' => 'pwa-images',
            'Required' => true,
            'obs' => 'This icon will be displayed on the splash screen of your app on supported devices. Must be a <<PNG>> image exactly <<512x512>> in size.',
        ],
    ];


    /**
     * Tab: Platform
     */
    $plataform_childs = [
        [
            'type_field' => 'selection_type',
            'Options' => [
                ['value' => 'preferred', 'display' => 'Preferred'],
                ['value' => 'no', 'display' => 'No'],
            ],
            'size' => 'col-md-6 col-lg-4',
            'label' => 'Handle Links',
            'name' => 'handle_links',
            'input_id' => 'handle_links',
            'Value' => $manifest['handle_links'] ?? ($info['handle_links'] ?? 'preferred'),
            'type' => 'radio',
            'variation' => 'balloons',
        ],
        [
            'type_field' => 'basic',
            'size' => 'col-md-6 col-lg-4',
            'label' => 'GCM Sender ID',
            'name' => 'gcm_sender_id',
            'input_id' => 'gcm_sender_id',
            'Value' => $manifest['gcm_sender_id'] ?? ($info['gcm_sender_id'] ?? ''),
        ],
    ];


    /**
     * Tab: Screenshots
     *
     * A field_repeater so any number of screenshots can be added. Each row
     * posts as pwa[screenshots][{index}][...]; generate_pwa_manifest() reads
     * that and detects the real image size from the uploaded file itself
     * (no fixed "WxH" preset needed anymore).
     */
    $screenshots_childs = [
        [
            'type_field'    => 'field_repeater',
            'name'          => 'screenshots',
            'label'         => 'Screenshots',
            'add_btn_title' => 'Add screenshot',
            'Value'         => pwa_get_screenshots_src($manifest['screenshots'] ?? ($info['screenshots'] ?? [])),
            'childs'        => [
                [
                    'type_field' => 'upload',
                    'type'       => 'images',
                    'size'       => 'col-md-6',
                    'label'      => 'Image',
                    'name'       => 'pwa[image]',
                    'og_name'    => 'src',
                    'Src'        => 'pwa-images',
                    'Required'   => true,
                    'obs'        => 'PNG image. Its size is auto-detected and used as the manifest "sizes" value.',
                ],
                [
                    'type_field' => 'selection_type',
                    'size'       => 'col-md-4',
                    'label'      => 'Form Factor',
                    'name'       => 'pwa[form_factor]',
                    'og_name'    => 'form_factor',
                    'Options'    => [
                        ['value' => 'narrow', 'display' => 'Narrow (mobile)'],
                        ['value' => 'wide', 'display' => 'Wide (desktop)'],
                    ],
                ],
                [
                    'type_field' => 'basic',
                    'size'       => 'col-md-2',
                    'label'      => 'Label',
                    'name'       => 'pwa[label]',
                    'og_name'    => 'label',
                    'obs'        => 'Optional accessibility label for this screenshot.',
                ],
            ],
        ],
    ];


    /**
     * Tab: Shortcuts
     *
     * Also a field_repeater — each row is one "quick action" shown when the
     * user long-presses the installed app's icon. Rows post as
     * pwa[shortcuts][{index}][...].
     */
    $shortcuts_childs = [
        [
            'type_field'    => 'field_repeater',
            'name'          => 'shortcuts',
            'label'         => 'Shortcuts',
            'add_btn_title' => 'Add shortcut',
            'Value'         => pwa_get_shortcuts_src($manifest['shortcuts'] ?? ($info['shortcuts'] ?? [])),
            'childs'        => [
                [
                    'type_field' => 'basic',
                    'size'       => 'col-md-4',
                    'label'      => 'Name',
                    'name'       => 'pwa[name]',
                    'og_name'    => 'name',
                    'Required'   => true,
                ],
                [
                    'type_field' => 'basic',
                    'size'       => 'col-md-4',
                    'label'      => 'URL',
                    'name'       => 'pwa[url]',
                    'og_name'    => 'url',
                    'Required'   => true,
                    'obs'        => 'Relative path this shortcut opens, e.g. /search',
                ],
                [
                    'type_field' => 'basic',
                    'size'       => 'col-md-4',
                    'label'      => 'Description',
                    'name'       => 'pwa[description]',
                    'og_name'    => 'description',
                ],
                [
                    'type_field' => 'upload',
                    'type'       => 'images',
                    'size'       => 'col-md-6',
                    'label'      => 'Icon',
                    'name'       => 'pwa[icon]',
                    'og_name'    => 'icon_src',
                    'Src'        => 'pwa-images',
                    'obs'        => 'Optional PNG icon for this shortcut.',
                ],
            ],
        ],
    ];


    /**
     * Assemble the tabs for tabs_form(). Each tab needs 'title' + 'childs';
     * a childless element (like the hidden 'mode' field below) is rendered
     * once, after the tabs, still inside the <form>.
     */
    $tabs = [
        [ 'title' => 'Info', 'childs' => $info_childs ],
        [ 'title' => 'Settings', 'childs' => $settings_childs ],
        [ 'title' => 'Icons', 'childs' => $icons_childs ],
        [ 'title' => 'Plataform', 'childs' => $plataform_childs ],
        // [ 'title' => 'Screenshots', 'childs' => $screenshots_childs ],
        // [ 'title' => 'Shortcuts', 'childs' => $shortcuts_childs ],
        [ 'type_field' => 'hidden', 'name' => 'mode', 'Value' => $mode ],
        [
          'depth' => 0,
          'type_field' => 'submit_button',
          'name' => 'process-form',
          'Value' => 'Generate',
          'class' => 'btn btn-st',
        ],
    ];

    $html = form([
        'view_mode'     => 'tabs_form',
        'type_crud'     => 'update',
        'form_method'   => 'POST',
        'form_action'   => rest_api_route_url('generate-pwa-manifest'),
        'attributes'    => 'enctype:(multipart/form-data);',
        'without_reload'=> ($mode == 'plugin'),
        'navtabs_class' => 'pwa-form',
        'contents'      => [
            'inputs' => $tabs,
            'data'   => $info,
        ],
    ]);

    echo $html;
}
