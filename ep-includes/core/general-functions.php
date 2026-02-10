<?php
if(!isset($seg)) exit;


/**
 * Check if a number is even.
 *
 * @param int $number The number to check.
 * @return bool Returns true if the number is even, false otherwise.
 */
function is_even($number)
{
    return ($number % 2 == 0) ? true : false;
}


/**
 * Converts a numeric value to 'Sim' or 'Não'.
 *
 * @param int $value The numeric value to convert.
 * @return string Returns 'Sim' if the value is 1, 'Não' if the value is 0 or 2.
 */
function yes_or_no($value)
{
    return ($value == 0 OR $value == 2) ? 'Não' : 'Sim';
}


/**
 * Get general status data.
 *
 * @param bool $for_selects Optional. Whether to format the data for select options. Default is false.
 * @return mixed|string|array The general status data. If $for_selects is true, returns a formatted string. Otherwise, returns an array of objects.
 */
function general_status( bool $for_selects = false )
{
    global $general_status;

    $res = $general_status;

    if ($for_selects == true)
    {
        $res = [];
        foreach($general_status as $stats)
        {
            $res[] = [
                'value' => $stats['id'],
                'display' => $stats['name'],
            ];
        }
    }

    return $res;
}

$all_status[] = [
    'function' => 'general_status',
    'name' => 'Geral'
];



/**
 * Get type status data.
 *
 * @param bool $for_selects Optional. Whether to format the data for select options. Default is false.
 * @return mixed|string|array The type status data. If $for_selects is true, returns a formatted string. Otherwise, returns an array of objects.
 */
function type_status(bool $for_selects = false)
{
    global $all_status;

    $res = $all_status;

    if ($for_selects == true)
    {
        $res = [];
        foreach($all_status as $status)
        {
            $res[] = [
                'value' => $status['function'],
                'display' => $status['name'],
            ];
        }
    }

    return $res;
}


/**
 * Retrieves the name of a status based on the provided ID or slug.
 *
 * @param mixed $status The ID or slug of the status to retrieve the name for.
 * @param string $type (optional) The type of status to search in (default: 'general_status').
 * @return string The name of the status if found; otherwise, "Not defined.".
 */
function general_stats($status, string $type = 'general_status')
{
    // var_dump($type());
    if (function_exists($type))
    {
        foreach ($type(false) as $k => $i)
        {
            if ($i['id'] == $status || $i['slug'] == $status) return "<span class='badge rounded-pill text-bg-{$i['color']}'>{$i['name']}</span>";
        }
    }

    return "The function '$type' with status '$status' does not exist.";
}


/**
 * Generates a status button based on the given parameters.
 *
 * @param int $id The ID of the button.
 * @param int $status The status ID to determine the button's style and text.
 * @param string $mode The mode for the button.
 * @param string $status_type (optional) The type of status (default is 'general_status').
 * @return string The HTML markup for the generated status button.
 */
function status_buttons($id, $status, string $mode, string $status_type = 'general_status')
{
    // var_dump($status_type());
    if (function_exists($status_type))
    {
        foreach ($status_type() as $i)
        {
            if ($i['id'] == $status || $i['slug'] == $status)
            return "
            <button status-button='{$status_type}' data-mode='{$mode}' class='btn btn-status-button' type='button' id='status-{$id}'>
                <span class='badge rounded-pill text-bg-{$i['color']}'>{$i['name']}</span>
            </button>";
        }
    }

    return "The function '$status_type' with status '$status' does not exist.";
}

/**
 * Escapes string for safe HTML output.
 *
 * @param mixed $value
 * @return string
 */
function e($value)
{
    global $config;
    return htmlspecialchars((string) $value, ENT_QUOTES, $config['charset']);
}

function safe_url(?string $url): string {
  $url = trim((string)$url);
  if ($url === '') return '';
  if (preg_match('/^\s*(javascript|vbscript|data)\s*:/i', $url)) return '';
  $parts = parse_url($url);
  if (isset($parts['scheme']) && !in_array(strtolower($parts['scheme']), ['http','https'], true)) return '';
  return htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function j($data): string {
  return json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}

function is_localhost()
{
    global $config;
    $host = $_SERVER['HTTP_HOST'] ?? '';

    if ($config['is_localhost'] == 1) {
        return true;
    }

    return (
        strpos($host, 'localhost') !== false ||
        strpos($host, '127.0.0.1') !== false ||
        strpos($host, '::1') !== false
    );
}

