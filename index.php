<?php

require_once 'load.php';

/**
 * Get the page content
 */
$page = get_page_with_permissions();

$path = $slug[0] ?? null;

/**
 * Check if it's the REST API Json page.
 */
if (isset($path) && $path == REST_API_BASE_ROUTE)
{
    load_rest_api_options();
    rest_api_json_page();
}


/**
 * Check if it's the CRON page.
 */
elseif (isset($path) && $path == CRON_BASE_ROUTE)
{
    cron_exec();
}


/* Here you can add custom routes to build pages as you want (). */

/*
elseif ($path == 'YOUR-URL')
{
    ...
}
*/

/* Yeah, now you must stop edit this file. */


/**
 * Load the page
 */
else
{
    /**
     * Packages of page's area.
     */
    $area = load_area_info($page_path);
    load_area_ui($page_path);

    if ($path == 'login')
    {
        $page = get_page('login');
        $file = AREAS_PATH ."/app/login.php";
    }

    /**
     * if there is not a chosen page, redirect to the main page
     */
    elseif (empty($path))
    {
        $page = get_page($config['main_page']['slug']);
        $file = $page['page_template'];
    }

    /**
     * Check if the page exists
     */
    elseif (!empty($page))
    {
        // Define de basic template
        $original_file = $file = __BASE_DIR__ . "/{$page['page_template']}";


        // Show the 404 page
        if ($page['page_template'] == NULL) {
            $file = AREAS_PATH ."/$page_path/error404.php";
        }


        // Check if the page's file exist
        elseif (file_exists($file) && $page['page_template'] !== null)
        {

            // status: Allow
            if ($page['status_id'] == 1)
            {
                if ((!is_user_logged_in()) AND ($page['is_public'] == 0 OR $page['is_public'] == 2))
                {
                    $_SESSION['msg'] = alert_message('ER_RESTRICTED_AREA', 'alert');

                    $redirect = pg .'/login?redirect_to='. urlencode(actual_pg);
                    header("Location: $redirect");
                }
            }


            // status: Unallowed
            elseif ($page['status_id'] == 2) $file = AREAS_PATH ."/$page_path/error404.php";


            // status: Review
            elseif ($page['status_id'] == 3)
            {
                if ((!is_user_logged_in()) OR (!is_dev()))
                {
                    $_SESSION['msg'] = alert_message('ER_MAINTENANCE_PAGE', 'alert');

                    $redirect = pg .'/login?redirect_to='. urlencode(actual_pg);
                    header("Location: $redirect");
                }
            }

        }

        // Show the ordinary page
        else $file = AREAS_PATH ."/$page_path/common.php";


        // Check if the site is closed
        if ($config['block_system'] == 1 && !is_dev() && $page['slug'] != 'login')
        {
            $redirect = pg .'/login';
            header("Location: $redirect");
        }


        // Plus 0 view to the page
        if ($file == $original_file) query_it("UPDATE tb_pages SET access_count=access_count+1 WHERE id='{$page['id']}'");
    }

    /**
     * if not, i'm so sorry...
     */
    else
    {
        $_SESSION['msg'] = alert_message('ER_INVALID_PERMISSION', 'alert');
        $redirect = pg .'/login';
        header("Location: $redirect");
    }//*/


    /**
     * Include the template.
     */
    if (isset($file)) {
        page($file);
    }
}

mysqli_close($conn);


$end_code = microtime(true);
$executation_time = round((($end_code - $start_code)*1000), 2);
// var_dump( $executation_time );

exit;
