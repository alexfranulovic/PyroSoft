<?php
if(!isset($seg)) exit;


global $rest_api_routes;
$rest_api_routes = [];



/**
 * Register a REST API route.
 *
 * This function is used to register a REST API route with specified parameters.
 *
 * @param string $route    The URL endpoint for the route.
 * @param array  $args     An array of arguments and configuration for the route.
 *                         Possible keys in $args:
 *                         - 'methods' (string): HTTP methods allowed (default is 'GET').
 *                         - 'callback' (callable): The callback function to handle the request (default is null).
 *                         - 'args' (array): Additional arguments for the route (default is an empty array).
 * @param bool   $override Whether to override an existing route if it has the same name (default is false).
 *
 * @global array $rest_api_routes A global array to store registered REST API routes.
 *
 * @return array The updated $rest_api_routes array with the new route.
 */
function register_rest_route($route, $args = [], $override = false)
{
    global $rest_api_routes;

    $defaults = [
        'methods'    => ['GET'],
        'need_login' => false,
        'callback'   => null,
        'args'       => [],
    ];

    return $rest_api_routes += [ $route => array_merge($defaults, $args) ];
}


function login_required_response()
{
    return
    [
        'code' => 'error',
        'detail' => [
            'type' => 'modal',
            'msg' => login_modal(),
            'code' => 'IF_ONLY_LOGGED_USERS',
        ],
    ];
}


function invalid_permission_response()
{
    return
    [
        'code'   => 'error',
        'detail' => [
            'type' => 'toast',
            'msg'  => alert_message('ER_INVALID_PERMISSION', 'toast'),
        ],
    ];
}


/**
 * Retrieve a list of all available REST API routes.
 *
 * This function returns an array containing the names of all registered REST API routes.
 *
 * @global array $rest_api_routes A global array that stores registered REST API routes.
 *
 * @return array An array of route names.
 */
function all_rest_api_routes_available()
{
    global $rest_api_routes;
    return array_keys($rest_api_routes);
}


/**
 * Generate a URL for a REST API route.
 *
 * This function generates a URL for a REST API route based on the provided URL path.
 * It appends the path to the REST API base path and returns the complete URL.
 *
 * @param string $url The path to the REST API route (e.g., 'posts' or 'users').
 *
 * @return string The complete URL for the REST API route.
 */
function rest_api_route_url(string $url = '')
{
    return base_url .'/'. REST_API_BASE_ROUTE .'/'. $url;
}


/**
 * Loads REST API options by including the necessary API files.
 *
 * This function includes the `api.php` file located in the `ep-includes/core/` directory.
 * It also declares the global variable `$seg`, which can be used within the included file.
 */
function load_rest_api_options()
{
    global $seg;
    include "ep-includes/core/api.php";
}


/**
 * Handle REST API requests and return JSON responses.
 *
 * This function processes REST API requests, executes the corresponding callback function,
 * and returns JSON responses. It sets the appropriate Content-Type header for JSON.
 *
 * @global array $slug An array containing URL path components.
 * @global array $rest_api_routes A global array that stores registered REST API routes.
 */
function rest_api_json_page()
{
    global $slug;
    global $rest_api_routes;

    header('Content-Type: application/json; charset=UTF-8');

    $error = false;

    $res = [
        'code'   => 'rest_no_route',
        'status' => 404,
        'detail' => [
            'type' => 'toast',
            'msg'  => 'No REST API picked.'
        ]
    ];

    $route = $slug[1] ?? null;

    if (!$route) {
        $error = true;
        $status = 400;
        $res['status'] = $status;
        $res['code']   = 'rest_missing_route';
        $res['detail']['msg'] = 'No REST API picked.';
    }

    elseif (isset($rest_api_routes[$route]))
    {
        $callback = $rest_api_routes[$route]['callback'];

        if (!is_user_logged_in() && ($rest_api_routes[$route]['need_login'] ?? false)) {
            $error = true;
            $status = 401;
            $res['status'] = $status;
            $res['code']   = 'rest_not_logged';
            $res['detail']['msg'] = 'You must be logged in to use this endpoint.';
        }

        $current_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $allowed_methods = $rest_api_routes[$route]['methods'] ?? 'GET';

        if (!is_array($allowed_methods)) {
            $allowed_methods = [$allowed_methods];
        }

        $allowed_methods = array_map('strtoupper', $allowed_methods);
        if (!$error && !in_array($current_method, $allowed_methods))
        {
            $error = true;
            $status = 405;
            $res['status'] = $status;
            $res['code']   = 'rest_forbidden_method';
            $res['detail']['msg'] = 'This HTTP method is not allowed for this endpoint.';
        }

        if (!$error)
        {
            if (is_callable($callback)) {
                $res = $callback();
                $status = 200;
            }

            else
            {
                $error = true;
                $status = 500;
                $res['status'] = $status;
                $res['code']   = 'rest_invalid_callback';
                $res['detail']['msg'] = 'Callback not callable.';
            }
        }
    }

    else {
        $error = true;
        $status = 404;
        $res['status'] = $status;
        $res['code']   = 'rest_no_route';
        $res['detail']['msg'] = 'This REST API Route does not exist.';
    }

    http_response_code($status);

    echo json_encode($res);
}
