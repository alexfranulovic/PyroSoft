<?php
if(!isset($seg)) exit;


/**
 * Loads plugin files based on the specified action.
 *
 * This function iterates over the plugin directories located in the PLUGINS_ABSOLUTE_PATH folder.
 * For each activated plugin (as specified in the configuration), it attempts to include a PHP file
 * corresponding to the given action (e.g., 'api', 'cronjobs', 'index', 'install', or 'uninstall').
 *
 * @param string $action The action to perform. Allowed values: 'api', 'cronjobs', 'index', 'install', 'uninstall'. Defaults to 'index'.
 * @param bool $debug If true, outputs debug information about the plugin files being loaded. Defaults to false.
 * @return bool Returns true if plugin files were processed successfully, or false if an error occurred.
 */
load_plugins();
function load_plugins(string $action = 'index', string $which_plugin = 'all', bool $force = false, bool $debug = false)
{
    global $seg, $config, $info;

    $plugins_folder = PLUGINS_ABSOLUTE_PATH;

    $allowed_actions = [
        'api',
        'cronjobs',
        'index',
        'install',
        'uninstall'
    ];

    if (!in_array($action, $allowed_actions)) {
        echo 'Load plugin action does not exist. You can do: '. implode(', ', $allowed_actions);
        return false;
    }

    $plugin_dirs = array_filter(glob($plugins_folder . '/*'), 'is_dir');
    foreach ($plugin_dirs as $dir)
    {
        $plugin_name = basename($dir);
        if ($which_plugin == 'all' OR $which_plugin == $plugin_name)
        {
            if ($force || in_array($plugin_name, $config['activated_plugins']))
            {
                $plugin_file = "{$dir}/{$action}.php";

                if (file_exists($plugin_file))
                {
                    if ($debug) var_dump($plugin_file);
                    include_once $plugin_file;
                }
            }
        }
    }

    return true;
}

function file_url(string $path, bool $in_bucket = false, string $filename = '')
{
    if ($in_bucket)
    {
        feature('aws-s3');

        $prefix = s3_site_prefix();
        $key = "{$prefix}/{$path}/{$filename}";

        return s3_presigned_url($key);
    }

    else {
        return site_url("/uploads/{$path}/{$filename}");
    }
}


function plugin_path(string $path = '', $mode = 'dir')
{
    if ($mode == 'url') {
        return site_url() .'/'. PLUGINS_PATH .'/'. $path;
    }
    return PLUGINS_ABSOLUTE_PATH.'/'. $path;
}

function feature_path(string $path = '', $mode = 'dir')
{
    if ($mode == 'url') {
        return site_url() .'/'. FEATURES_PATH .'/'. $path;
    }
    return FEATURES_ABSOLUTE_PATH.'/'. $path;
}


/**
 * Lists all available plugins by scanning the plugins directory.
 *
 * This function scans the PLUGINS_ABSOLUTE_PATH folder and builds an array of plugins.
 * For each plugin directory found, it assigns a 'value' key using the directory's basename.
 * If an "info.json" file exists in the plugin directory, the function decodes it to extract
 * plugin information and sets the 'display' attribute based on the plugin's name.
 *
 * @param bool $for_selects Optional. If true, the output may be used for select options (not currently utilized). Defaults to false.
 * @return array An array of plugins with their associated information.
 */
function list_plugins(bool $for_selects = false)
{
    global $config;

    $plugins_folder     = PLUGINS_ABSOLUTE_PATH;
    $activated_plugins  = $config['activated_plugins'] ?? [];

    $plugins = [];
    $plugin_dirs = array_filter(glob($plugins_folder . '/*'), 'is_dir');
    $counter = 0;
    foreach ($plugin_dirs as $dir)
    {
        $plugin = $plugin_info = [];

        if (file_exists("{$dir}/info.json"))
        {
            $plugin_info = file_get_contents("{$dir}/info.json");
            $plugin_info = json_decode($plugin_info, true);

            $plugin_info['slug'] = basename($dir);
            $plugin_info['display'] = $plugin_info['name'];
            $plugin_info['activated'] = (in_array(basename($dir), $activated_plugins));
        }

        if ($for_selects)
        {
            $plugin['value']    = basename($dir);
            $plugin['display']  = $plugin_info['name'] ?? $plugin['value'];

            if (in_array($plugin['value'], $activated_plugins)) {
                $plugin['checked'] = true;
            }

            unset($plugin_info);
        }

        $plugins[] = array_merge(($plugin??[]), ($plugin_info??[]));

        $counter++;
    }

    return $plugins;
}


/**
 * Load all areas' functions.
 *
 * Scans "AREAS_ABSOLUTE_PATH/*" and, for each area directory found,
 * includes "AREAS_ABSOLUTE_PATH/{area}/include/functions.php" if present.
 *
 * @param bool $debug When true, prints which files are being included (or missing).
 * @return bool True on success, false if the areas root is missing or unreadable.
 */
function load_this_system_functions(string $action = 'functions', bool $debug = false): bool
{
    // Keep globals available if included files rely on them
    global $seg, $config, $info;

    $root = __DIR__ . '/this-system';

    if (!is_dir($root)) {
        if ($debug) echo "this-system root not found: {$root}\n";
        return false;
    }

    $allowed_actions = [
        'api',
        'cronjobs',
        'functions',
    ];

    if (!in_array($action, $allowed_actions, true)) {
        echo 'Load action does not exist. You can do: ' . implode(', ', $allowed_actions);
        return false;
    }

    // Find: **/include/{action}.php
    $pattern = $root . '/*/include/' . $action . '.php';

    // Recursive glob without helpers: use RecursiveDirectoryIterator
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    $included_any = false;

    foreach ($iterator as $item)
    {
        if (!$item->isDir()) {
            continue;
        }

        // Only folders named "include" matter
        if ($item->getFilename() !== 'include') {
            continue;
        }

        $file = $item->getPathname() . DIRECTORY_SEPARATOR . $action . '.php';

        if (file_exists($file)) {
            $included_any = true;

            if ($debug) var_dump("'{$file}' included.");
            include_once $file;
        } else {
            if ($debug) var_dump("Missing: '{$file}'");
        }
    }

    return $included_any;
}



/**
 * Scans areas root and the `custom-pages` folders of features and plugins,
 * building a list of PHP files in the "value/display" format.
 *
 * Structure:
 * [
 *   'areas'    => [ '<area>'    => [ [ 'value' => <file>, 'display' => <file> ], ... ], ... ],
 *   'features' => [ '<feature>' => [ [ 'value' => <file>, 'display' => <file> ], ... ], ... ],
 *   'plugins'  => [ '<plugin>'  => [ [ 'value' => <file>, 'display' => <file> ], ... ], ... ],
 * ]
 *
 * @param bool $debug (optional) Print debug info.
 * @return array The mapping described above.
 */
function load_area_custom_pages(bool $debug = false): array
{
    global $seg, $config, $info;

    $result = [
        'areas'    => [],
        'features' => [],
        'plugins'  => [],
    ];

    /**
     * Normalize to project-root-relative path:
     * - If $full starts with __BASE_DIR__, strip it.
     * - Else, prepend $prefix (e.g., 'AREAS_ABSOLUTE_PATH/admin/') to the basename.
     * - Always return with forward slashes.
     */
    $toProjectRelative = function (string $full, string $prefix): string {
        $full = str_replace(['\\'], '/', $full);
        $base = str_replace(['\\'], '/', rtrim(__BASE_DIR__, '/')) . '/';

        if (strpos($full, $base) === 0) {
            $rel = substr($full, strlen($base));
            return ltrim($rel, '/');
        }

        // If it's not an absolute path, assume it's a filename and build from prefix
        $file = basename($full);
        $prefix = trim(str_replace(['\\'], '/', $prefix), '/');
        return ($prefix === '' ? $file : ($prefix . '/' . $file));
    };

    // -------------------- AREAS --------------------
    $areas_root = AREAS_PATH;
    if (!is_dir($areas_root)) {
        if ($debug) echo "[areas] Root not found: {$areas_root}\n";
    } else {
        $area_dirs = array_filter(glob($areas_root . '/*'), 'is_dir');
        foreach ($area_dirs as $dir) {
            $area_key = basename($dir);
            $files = function_exists('get_php_files_in') ? get_php_files_in($dir) : [];

            if ($debug) echo "[areas] {$area_key}: " . count($files) . " file(s)\n";

            $bucket = [];
            foreach ($files as $entry) {
                $valueAbs  = $toProjectRelative($entry, "{$areas_root}/{$area_key}");
                $display   = basename($entry);
                $bucket[]  = ['value' => $valueAbs, 'display' => $display];
            }
            $result['areas'][$area_key] = $bucket;
        }
    }

    // -------------------- FEATURES/custom-pages --------------------
    $features_root = FEATURES_PATH;
    if (!is_dir($features_root)) {
        if ($debug) echo "[features] Root not found: {$features_root}\n";
    } else {
        $feature_dirs = array_filter(glob($features_root . '/*'), 'is_dir');
        foreach ($feature_dirs as $fdir) {
            $feature_folder = basename($fdir);
            $cp_dir = $fdir . '/custom-pages';

            if (!is_dir($cp_dir)) {
                if ($debug) echo "[features] {$feature_folder}: no custom-pages\n";
                continue; // skip entirely
            }

            $files = function_exists('get_php_files_in')
                ? get_php_files_in("{$features_root}/{$feature_folder}/custom-pages")
                : [];

            if (empty($files)) {
                if ($debug) echo "[features] {$feature_folder}/custom-pages: empty\n";
                continue; // skip empty
            }

            if ($debug) echo "[features] {$feature_folder}/custom-pages: " . count($files) . " file(s)\n";

            $bucket = [];
            foreach ($files as $entry) {
                $valueAbs = $toProjectRelative(
                    $entry,
                    "{$features_root}/{$feature_folder}/custom-pages"
                );
                $display  = basename($entry);
                $bucket[] = ['value' => $valueAbs, 'display' => $display];
            }
            $result['features'][$feature_folder] = $bucket;
        }
    }

    // -------------------- PLUGINS/custom-pages --------------------
    $plugins_root = PLUGINS_PATH;
    if (!is_dir($plugins_root)) {
        if ($debug) echo "[plugins] Root not found: {$plugins_root}\n";
    } else {
        $plugin_dirs = array_filter(glob($plugins_root . '/*'), 'is_dir');
        foreach ($plugin_dirs as $pdir) {
            $plugin_folder = basename($pdir);
            $cp_dir = $pdir . '/custom-pages';

            if (!is_dir($cp_dir)) {
                if ($debug) echo "[plugins] {$plugin_folder}: no custom-pages\n";
                continue; // skip entirely
            }

            $files = function_exists('get_php_files_in')
                ? get_php_files_in("{$plugins_root}/{$plugin_folder}/custom-pages")
                : [];

            if (empty($files)) {
                if ($debug) echo "[plugins] {$plugin_folder}/custom-pages: empty\n";
                continue; // skip empty
            }

            if ($debug) echo "[plugins] {$plugin_folder}/custom-pages: " . count($files) . " file(s)\n";

            $bucket = [];
            foreach ($files as $entry) {
                $valueAbs = $toProjectRelative(
                    $entry,
                    "{$plugins_root}/{$plugin_folder}/custom-pages"
                );
                $display  = basename($entry);
                $bucket[] = ['value' => $valueAbs, 'display' => $display];
            }
            $result['plugins'][$plugin_folder] = $bucket;
        }
    }

    return $result;
}


/**
 * Load UI ui/components for the active area.
 *
 * - Looks for "AREAS_ABSOLUTE_PATH/{page_path}/include/{file}".
 * - Includes only if the file exists.
 * - If the included file returns an array/object (e.g., config), it is returned.
 *   Otherwise returns true on success, false on failure.
 *
 * @param string $page_path Area path/slug (e.g., "admin", "public").
 * @param bool   $debug     When true, prints debug messages.
 * @param string $file      Filename to include (default: "ui.php").
 * @return array|object|bool
 */
function load_area_ui(string $page_path, string $file = 'ui.php', bool $debug = false)
{
    // Keep globals available if area functions rely on them
    global $seg, $config, $info;

    // Basic sanitization to avoid traversal
    $page_path = trim($page_path, '/');
    if ($page_path === '' || strpos($page_path, '..') !== false) {
        if ($debug) echo "Invalid page_path.\n";
        return false;
    }

    $full = AREAS_ABSOLUTE_PATH ."/{$page_path}/include/{$file}";

    if (!file_exists($full)) {
        if ($debug) echo "Formatting file not found: {$full}\n";
        return false;
    }

    // Include once to avoid redeclaration; capture return if provided
    $ret = include_once $full;

    return (is_array($ret) || is_object($ret)) ? $ret : true;
}


/**
 * Loads feature files based on the specified feature and action.
 *
 * This function iterates over the feature directories located in "ep-includes/core/features"
 * and includes the PHP file corresponding to the given action. It can load a specific feature
 * or all features if "all" is specified.
 *
 * @param string $which_feature The specific feature to load, or 'all' to load all features. Defaults to 'all'.
 * @param string $action The action to perform for each feature. Allowed values: 'api', 'cronjobs', 'index'. Defaults to 'index'.
 * @param bool $debug If true, outputs debug information about the feature files being loaded. Defaults to false.
 * @return bool Returns true if feature files were processed successfully, or false if an error occurred.
 */
function feature(string $which_feature = 'all', string $action = 'index', bool $debug = false)
{
    global $seg, $config, $info;

    $features_folder = FEATURES_ABSOLUTE_PATH;

    $allowed_actions = [
        'api',
        'cronjobs',
        'index',
    ];

    if (!in_array($action, $allowed_actions)) {
        echo 'Load feature action does not exist. You can do: '. implode(', ', $allowed_actions);
        return false;
    }

    $feature_dirs = array_filter(glob($features_folder . '/*'), 'is_dir');
    foreach ($feature_dirs as $dir)
    {
        $feature_name = basename($dir);
        if ($which_feature == 'all' OR $which_feature == $feature_name)
        {
            $feature_file = "{$dir}/{$action}.php";

            if (file_exists($feature_file))
            {
                if ($debug) var_dump("'$feature_file' added.");
                include_once $feature_file;
            }
        }
    }

    return true;
}

function load_area_info($page_path)
{
    $area = file_get_contents(AREAS_ABSOLUTE_PATH ."/$page_path/include/area-info.json");
    return json_decode($area, true);
}


/**
 * Adds an asset to a specified location if it hasn't been added yet.
 *
 * @param string $where The location where the asset should be added (e.g., 'head' or 'footer').
 * @param string $asset The asset (e.g., CSS or JavaScript snippet) to add.
 * @return void
 */
function add_asset(string $where, string $asset)
{
    global $assets_to_load;

    if (!in_array($asset, $assets_to_load[$where])) {
        $assets_to_load[$where][] = $asset;
    }
}


function add_html_class(string $class)
{
    global $html_classes;

    if (!in_array($class, $html_classes)) {
        $html_classes[] = $class;
    }
}


function html_class()
{
    global $html_classes;
    return implode(' ', $html_classes);
}


/**
 * Outputs all assets that have been added for the head section.
 *
 * @return void
 */
function head()
{
    global $assets_to_load;
    foreach ($assets_to_load['head'] as $asset) {
        echo $asset;
    }
}

/**
 * Outputs all assets that have been added for the footer section.
 *
 * @return void
 */
function footer()
{
    global $assets_to_load;
    foreach ($assets_to_load['footer'] as $asset) {
        echo $asset;
    }
}
