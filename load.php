<?php
/**
 * Entry point of the application. Initializes base paths, environment,
 * configuration, libraries, and routing.
 */

$seg = true;
$start_code = microtime(true);


/** Setting the Session **/
session_start();
ob_start();


// Define the absolute base path of the project.
define('__BASE_DIR__', __DIR__ . '/');


/**
 * Loads a variable from the .env configuration file.
 *
 * @param string $var The name of the variable to retrieve.
 * @return string|null The value of the variable or null if not found.
 */
function env(string $var)
{
    $env = parse_ini_file(__BASE_DIR__ . '.env');
    return $env[$var] ?? '';
}


/**
 * Load core libraries and configuration
 */
require_once 'config.php';
require_once 'ep-includes/core/variables.php';
require_once 'this-system/variables.php';
require_once 'ep-includes/core/functions.php';
require_once 'this-system/functions.php';


/**
 * Set application timezone based on the loaded config.
 */
date_default_timezone_set("Etc/{$config['timezone']}");


/**
 * Process the incoming URL for routing.
 */
// raw input from query string
$url = filter_input(INPUT_GET, 'url', FILTER_UNSAFE_RAW) ?? '';

// sanitize against HTML injection
$url = strip_tags($url);

// custom cleaner
$cleaned_url = clean_url($url);

// split into segments
$params_url  = explode('/', $cleaned_url);

// canonical URL definition
define('canonical', pg . '/' . $cleaned_url);


/**
 * Determine the area (module) of the application from the first URL segment.
 * Default area is "app".
 */
$page_path = $params_url[0];


/**
 * Handle page access and rewrite routing as necessary.
 * If user has access, resolve full page path; otherwise fallback to 'Site'.
 */
$slug = page_area($page_path, 'path', true)
    ? array_slice($params_url, 1)
    : $params_url;

$page_path = is_user_allowed_to_access_area()
    ? page_area($page_path, 'path')
    : page_area('Site', 'path');


/**
 * Check if the required `curl` extension is available.
 * If not, display an error and terminate execution.
 */
if (!function_exists('curl_version')) {
    echo "Curl is required.";
    exit;
}
