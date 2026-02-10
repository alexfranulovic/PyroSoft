<?php
if (!isset($seg)) exit;

/**
 * NOTE: relies on your existing helpers:
 * - get_results() / insert() / update()
 * - is_json($value)
 * If you have a prepared-query helper (e.g., get_results_prepared),
 * this file will use it automatically; otherwise it falls back to
 * a safely-quoted IN list.
 */


/** Safely builds an SQL IN list for string keys when no prepared helper exists. */
function _sql_in_list(array $keys): string
{
    return implode(',', array_map(static fn($k) => "'" . addslashes((string)$k) . "'", $keys));
}


/** Executes a SELECT for tb_info by a list of option names (prefers prepared helper if available). */
function _fetch_tb_info_by_keys(array $keys): array
{
    if (empty($keys)) return [];
    // Prefer a prepared helper if your stack provides one
    if (function_exists('get_results_prepared'))
    {
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $sql = "SELECT option_name, option_value, type FROM tb_info WHERE option_name IN ($placeholders) ORDER BY type DESC";
        return get_results_prepared($sql, $keys) ?: [];
    }
    // Fallback: quoted IN list
    $in = _sql_in_list($keys);
    $sql = "SELECT option_name, option_value, type FROM tb_info WHERE option_name IN ($in) ORDER BY type DESC";
    return get_results($sql) ?: [];
}


/** Normalizes booleans to DB-friendly 0/1/NULL. */
function _normalize_bool_db($v): ?int
{
    if (is_bool($v)) return $v ? 1 : 0;
    if ($v === 1 || $v === 0 || $v === '1' || $v === '0') return (int)$v;
    return null;
}

// --- Initialize globals defensively ---
$GLOBALS['info']   = $GLOBALS['info']   ?? [];
$GLOBALS['config'] = $GLOBALS['config'] ?? [];
$info   = &$GLOBALS['info'];
$config = &$GLOBALS['config'];


/**
 * Autoload options into $info / $config.
 * Values that are JSON strings are decoded into arrays.
 */
$__autoload = get_results("SELECT option_name, option_value, type FROM tb_info WHERE autoload = '1' ORDER BY type DESC") ?: [];
foreach ($__autoload as $row)
{
    $val = $row['option_value'];
    if (is_json($val)) $val = json_decode($val, true);
    if ($row['type'] === 'info')
    	{ $info[$row['option_name']]   = $val; }
    elseif ($row['type'] === 'config')
    	{ $config[$row['option_name']] = $val; }
}


/**
 * Retrieves 1 or N options by name, filling $info/$config.
 * Return semantics mirror your original code:
 *  - If $key is array: returns true (data is loaded into globals).
 *  - If $key is string: returns the raw DB value (string) or null if not found.
 */
function get_system_info($key = '')
{
    global $info, $config;

    $keys = is_array($key) ? array_values(array_unique(array_filter($key, 'strlen'))) : [(string)$key];
    if (empty($keys) || $keys === ['']) return is_array($key) ? true : null;

    $rows = _fetch_tb_info_by_keys($keys);

    // Fill globals, decoding JSON where applicable
    foreach ($rows as $row)
    {
        $val = $row['option_value'];
        if (is_json($val)) $val = json_decode($val, true);
        if ($row['type'] === 'info')
        	{ $info[$row['option_name']]   = $val; }
        elseif ($row['type'] === 'config')
        	{ $config[$row['option_name']] = $val; }
    }

    if (is_array($key))
    {
        // Keep original semantics: just indicate it loaded
        return true;
    }

    // Single key: return the raw DB value (not decoded), or null
    foreach ($rows as $row)
    {
        if ($row['option_name'] === $key)
        {
            return $row['option_value'] ?? null;
        }
    }
    return null;
}

/**
 * Creates or updates an option.
 * Arrays/objects are stored as JSON; strings are stored as-is.
 * $autoload is normalized to 0/1/NULL. $type defaults to 'config'.
 */
function update_option($option, $value, bool $autoload = null, $type = null, bool $debug = false)
{
    $storeValue = (is_array($value) || is_object($value))
        ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        : $value;

    $autoloadDB = _normalize_bool_db($autoload);

    $exists = get_system_info($option); // returns string|null for single key
    if ($exists === null)
    {
        return insert('tb_info', [
            'option_name'  => $option,
            'option_value' => $storeValue,
            'type'         => $type ?? 'config',
            'autoload'     => $autoloadDB,
        ], $debug);
    } else
    {
        $data = [
            'option_name'  => $option,
            'option_value' => $storeValue,
        ];
        if (!empty($type))        $data['type'] = $type;
        if ($autoloadDB !== null) $data['autoload'] = $autoloadDB;

        return update('tb_info', [
            'data'  => $data,
            'where' => [[ 'field' => 'option_name', 'operator' => '=', 'value' => $option ]],
        ], $debug);
    }
}


/**
 * Serializes tb_info rows into a single record (array or JSON).
 *
 * Params (all optional):
 * - keys            array|string|null  Limit by option_name(s) (IN).
 * - autoload_only   bool|null          If true, only autoload=1; if false, only autoload=0; if null, ignore.
 * - group           'type'|'none'      Group by type ('info'/'config') or flatten everything. Default: 'type'.
 * - decode_json     bool               Decode JSON values to arrays. Default: true.
 * - as_json         bool               Return json string instead of array. Default: false.
 * - flatten_prefix  bool               When group='none', prefix keys with type ("config.base_url"). Default: false.
 */
function tb_info_serialize(array $params = [])
{
    $keys          = $params['keys']          ?? null;
    $autoload_only = $params['autoload_only'] ?? null;
    $group         = ($params['group'] ?? 'type') === 'none' ? 'none' : 'type';
    $decode_json   = array_key_exists('decode_json', $params) ? (bool)$params['decode_json'] : true;
    $as_json       = !empty($params['as_json']);
    $flatten_prefix= !empty($params['flatten_prefix']);

    // Build WHERE
    $where = [];
    if ($keys) {
        if (is_string($keys)) $keys = [$keys];
        $keys = array_values(array_unique(array_filter($keys, 'strlen')));
        if ($keys) {
            $in = implode(',', array_map(static fn($k) => "'" . addslashes((string)$k) . "'", $keys));
            $where[] = "option_name IN ($in)";
        }
    }
    if ($autoload_only === true)  $where[] = "autoload = '1'";
    if ($autoload_only === false) $where[] = "autoload = '0'";

    $sql  = "SELECT option_name, option_value, type FROM tb_info";
    if ($where) $sql .= " WHERE " . implode(' AND ', $where);
    $sql .= " ORDER BY type DESC, option_name ASC";

    $rows = get_results($sql) ?: [];

    // Assemble single record
    $out = ($group === 'type') ? ['info' => [], 'config' => []] : [];

    foreach ($rows as $r) {
        $name  = $r['option_name'];
        $value = $r['option_value'];
        $type  = ($r['type'] === 'config') ? 'config' : 'info';

        if ($decode_json && is_json($value)) {
            $value = json_decode($value, true);
        }

        if ($group === 'type') {
            $out[$type][$name] = $value;
        } else {
            $key = $flatten_prefix ? "{$type}.{$name}" : $name;
            $out[$key] = $value;
        }
    }

    return $as_json
        ? json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        : $out;
}


// /**
//  * Checks if the current environment is CLI and sets base URLs accordingly.
//  */
// $is_cli = (php_sapi_name() === 'cli');

// if ($is_cli) {
//     $base_url = $config['base_url'];
//     $actual_pg = $base_url . '/cli';
// }

// else
// {
//     $REQUEST_SCHEME = $_SERVER['REQUEST_SCHEME'] ?? 'https';
//     $base_url = "{$REQUEST_SCHEME}://{$_SERVER['HTTP_HOST']}" . preg_replace('/\/index\.php$/', '', $_SERVER['PHP_SELF']);
//     $actual_pg = "{$REQUEST_SCHEME}://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

//     if ($base_url != $config['base_url']) {
//     	update_option('base_url', $base_url);
//     }
// }

// define('pg', $base_url);			// Will be removed in future.
// define('base_url', $base_url);
// define('actual_pg', $actual_pg);


/**
 * Checks if the current environment is CLI and sets base URLs accordingly.
 */
$is_cli = (php_sapi_name() === 'cli');

if ($is_cli) {
    $base_url  = rtrim($config['base_url'], '/');
    $actual_pg = $base_url . '/cli';
} else {

    $REQUEST_SCHEME = $_SERVER['REQUEST_SCHEME']
        ?? ( (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http' );

    // --- NORMALIZA HOST (remove www.)
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $host = preg_replace('/^www\./i', '', $host);

    // --- BASE PATH (remove index.php)
    $base_path = preg_replace('/\/index\.php$/', '', $_SERVER['PHP_SELF']);

    $base_url  = "{$REQUEST_SCHEME}://{$host}{$base_path}";
    $actual_pg = "{$REQUEST_SCHEME}://{$host}{$_SERVER['REQUEST_URI']}";

    // normaliza barras
    $base_url = rtrim($base_url, '/');

    if ($base_url !== rtrim($config['base_url'], '/')) {
        update_option('base_url', $base_url);
    }
}

define('pg', $base_url);       // legado
define('base_url', $base_url);
define('actual_pg', $actual_pg);


/**
 * Returns the full URL for a given site path.
 *
 * @param string $url Path relative to site root.
 * @return string Full URL with base path prepended.
 */
function site_url(string $url = '')
{
    return pg . $url;
}


// Set absolute URLs for favicon and main page config
$info['favicon'] = site_url('/uploads/images/brand/' . $info['favicon']);
$config['main_page']['url'] = site_url('/' . $config['main_page']['slug']);
$config['activated_plugins'] = is_array($config['activated_plugins'])
    ? $config['activated_plugins']
    : [];
