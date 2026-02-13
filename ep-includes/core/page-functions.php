<?php
if(!isset($seg)) exit;

/**
 * Generates an HTML page with content from the specified template.
 *
 * @param string $template The path to the file containing the page content.
 * @return string The generated HTML page.
 */
function page(string $file)
{
    global $seg,
        $conn,
        $current_user,
        $slug,
        $info,
        $area,
        $config,
        $user,
        $cleaned_url,
        $page_path,
        $page,
        $max_upload;

    $page_template = basename($page['page_template'] ?? '');
    $page_template = explode('.php', $page_template)[0];
    $slug = $page['slug'] ?? '';

    $storage_theme_color = $_COOKIE['theme_color'] ?? $area['theme_color'];
    $area_color = $area['theme_color'];

    if ($area['allow_change_color_mode'])
    {
        $area_color = ($storage_theme_color == 'auto')
            ? $area['theme_color']
            : $storage_theme_color;
    }

    $area_color_icon = [
        'auto' => 'fas fa-circle-half-stroke',
        'dark' => 'fas fa-moon',
        'light' => 'fas fa-sun',
    ];

    $html_class = html_class();

    echo "
    <!DOCTYPE html>
    <html lang='". ($config['lang'] ?? '') ."' class='area-$page_path page-{$slug} template-{$page_template} {$html_class}' data-bs-theme='$area_color'>";
        include "$file";
    echo "
    </body>
    </html>";
}

/**
 * Print Page Content
 *
 * This function is responsible for retrieving and displaying the content of a page.
 *
 * @param int|null $page_id The ID of the page for which the content should be displayed. If not provided, the default value is null.
 *
 * @return void
 */
function page_content(int $page_id = null)
{
    //Define global variables
    global $page,
        $current_user,
        $info;

    $page_id = $page_id ?? $page['id'];       // Define page ID

    /**
     * Execute the query to build the form
     */
    $status_id = is_dev()
        ? ' status_id != 2'
        : ' status_id = 1';


    $query = get_results(
    "SELECT * FROM tb_page_content
    WHERE $status_id
    AND page_id = '" . $page_id . "'
    AND TypeModule IS NOT Null
    ORDER BY order_reg ASC");

    /**
     * List de content modules
     */
    $counter = 1;
    foreach ($query as $module)
    {
        $module = (array) $module['settings'] + $module;

        $module['counter'] = $counter;

        if ($module['TypeModule'] == 'hr' || $module['TypeModule'] == 'shortcode' || $module['TypeModule'] == 'break_line') {
            echo $module['TypeModule']($module);
            continue;
        }

        $subscribers_only = ($module['subscribers_only'] == 1) ? true : false;

        if ( $module['TypeModule'] == 'crud')
        {
            $params = [
                'piece_id' => $module['crud_id'],
                'subscribers_only' => $subscribers_only
            ];
            echo crud_piece($params);
        }

        // Others Modules
        else
        {
            if (is_json($module['contents']))
            {
                $contents = json_decode($module['contents']);
                unset($module['contents']);
                $module['contents'] = $contents;
            }

            echo block($module['TypeModule'], $module);
        }

        $counter++;
    }
}

/**
 * Retrieves a page's information based on its address and permission settings.
 *
 * @global array $slug The URL slug parameters.
 * @return array|null An array containing the page's information or null if no page is found.
 */
function get_page_with_permissions(bool $debug = false)
{
    global $current_user, $slug, $page_path;

    $page_area = page_area($page_path, 'path');

    $roles = load_roles();

    $ids = implode(",", $roles);

    $public = !is_user_logged_in() ? " AND page.is_public = 1" : "";

    $path = $slug[0] ?? '';

    $sql = "
    SELECT page.*
    FROM tb_pages page
    WHERE (page.slug = '{$path}' OR page.id = '{$path}')
    AND page.page_area = '{$page_area}'
    ORDER BY page.status_id ASC
    LIMIT 1";

    if ($debug) {
        var_dump($current_user);
        echo "<pre>$sql</pre>";
        die;
    }

    $res = get_result($sql);

    if (!empty($res['id'])) {
        $res['has_permission'] = load_permission($res['id']);
    }

    return $res;
}


/**
 * Checks if a page exists in the "tb_pages" table.
 *
 * @param mixed $page The page address or ID.
 * @return bool True if the page exists, false otherwise.
 */
function is_page($page)
{
    $query = count_results("SELECT * FROM tb_pages WHERE (slug = '{$page}') OR id = '{$page}'");
    return ( $query > 0 ) ? true : false;
}


/**
 * Retrieves a page from the "tb_pages" table based on address or ID.
 *
 * @param mixed $page The page address or ID.
 * @return array|null The page information as an array or null if no page found.
 */
function get_page($page)
{
    return get_result("SELECT * FROM tb_pages WHERE (slug = '{$page}') OR id = '{$page}'");
}

/**
 * Retrieves all pages from the "tb_pages" table in ascending order of "ordem" column.
 *
 * @return array The pages information as an array of associative arrays.
 */
function get_pages(array $attr = [])
{
    $return = $pages = get_results("SELECT * FROM tb_pages ORDER BY page_area DESC, title ASC");

    $page_area = (isset($attr['page_area']) && $attr['page_area'] == true)
        ? true
        : false;

    $value_is = $attr['value_is'] ?? 'id';

    $for_select = (isset($attr['for_select']) && $attr['for_select'] == true)
        ? true
        : false;

    $return = [];
    foreach ($pages as $page)
    {
        $page['title'] = $page_area
            ? $page['title']." - ".$page['page_area']
            : $page['title'];

        $row['id']           = $page['id'];
        $row['title']        = $page['title'];
        $row['slug']         = $page['slug'];
        $row['status_id']    = $page['status_id'];

        $return[] = $for_select
            ? [ 'value' => $page[$value_is], 'display' => $page['title'] ]
            : $row;
    }

    return $return;
}

function get_pages_for_select(string $value_is = 'id')
{
    return get_pages([
        'page_area' => true,
        'for_select' => true,
        'value_is' => $value_is
    ]) ?? [];
}


/**
 * Retrieves the URL of a page based on the provided page ID.
 *
 * @param string  $page       The ID of the page.
 * @param bool $complete_url  Indicates whether to include the complete URL or just the page address.
 * @return string|null The URL of the page or null if the page does not exist.
 */
function get_url_page($page, $complete_url = false)
{
    $page = get_result("SELECT slug, page_area FROM tb_pages WHERE ((slug = '{$page}') OR (id = '{$page}'))");

    $url = null;
    if ($page != null)
    {
        if (!$complete_url) $url = $page['slug'];

        else
        {
            if ($complete_url == 'path') $url = page_area($page['page_area'], 'url') . $page['slug'];
            elseif ($complete_url == 'full') $url = pg .'/'. page_area($page['page_area'], 'url') . $page['slug'];
        }

    }

    return ($url) ? $url : null;
}

/**
 * Checks if the string "pg" is present in the given URL.
 * If "pg" is not found in the URL, returns 'target="_blank"'.
 * If "#" is found in the URL, returns an empty string.
 * Otherwise, returns an empty string.
 *
 * @param string $url The URL to be checked.
 *
 * @return string The attribute 'target="_blank"', an empty string, or an empty string.
 */
function check_pg_in_url($url = '')
{
    if (!empty($url))
    {
        $res = "href='$url'";
        if (strpos($url, pg) === false) {
            $res.= " rel='external' target='_blank'";
        } elseif (strpos($url, '#') !== false) {
            // Do nothing.
        } else {
            // Do nothing.
        }
    }

    return $res ?? "href='#'";
}


/**
 * Scan all areas under "AREAS_ABSOLUTE_PATH" and collect their area-info.json.
 *
 * - Looks for "AREAS_ABSOLUTE_PATH/{area}/include/area-info.json"
 * - For each area that has the file, pushes an object into $arr:
 *     { id, name, slug, url, path, info }
 *   - id: from JSON if present, else sequential (1..N)
 *   - name/slug/url: from JSON if present; sensible fallbacks if absent
 *   - path: the directory name (area slug/folder)
 *   - info: full decoded JSON (as object) for extra fields you may need
 *
 * @param bool $ForSelects If true, returns "N|| path|| slug - name;" list; else returns array of objects.
 * @return array|string
 */
function page_areas(bool $ForSelects = false)
{
    $arr = [];
    $root = AREAS_ABSOLUTE_PATH;

    if (!is_dir($root)) {
        return $ForSelects ? '' : $arr;
    }

    $dirs = array_filter(glob($root . '/*'), 'is_dir');
    $seq  = 1;

    foreach ($dirs as $dir)
    {
        $page_path = basename($dir);
        $jsonFile  = $dir . '/include/area-info.json'; // <- singular, conforme seu path

        if (!is_file($jsonFile))  continue; // skip areas without area-info.json

        $raw  = @file_get_contents($jsonFile);
        $info = json_decode($raw ?: '[]', true);
        if (!is_array($info)) $info = [];

        // Map core fields with fallbacks
        $name = $info['name'] ?? ucfirst($page_path);
        $slug = $info['slug'] ?? $page_path;

        // If not provided, default url to "<area>/" except for "app" (site raiz)
        $url  = $info['url']  ?? ($page_path === 'app' ? '' : $page_path . '/');

        $arr[] = (object) [
            'id'   => $info['id'] ?? $seq,
            'name' => $name,
            'slug' => $slug,
            'url'  => $url,
            'path' => $page_path,
            'info' => (object) $info, // full JSON in case you need extra metadata
        ];

        $seq++;
    }

    if (!$ForSelects) return $arr;

    $out = [];
    $count = 1;
    foreach ($arr as $i)
    {
        $out[] = [
            'value' => $i->path,
            'display' => $i->slug . " - " . $i->name,
        ];
        $count++;
    }
    return $out;
}


/**
 * Retrieves information about a page type based on its ID, name, slug, or path.
 *
 * @param mixed $type The ID, name, slug, or path of the page type.
 * @param string $what (optional) The specific information to retrieve (e.g., 'name', 'slug', 'path').
 * @param bool $bool_return (optional) If set to true, the function returns a boolean indicating if the type exists.
 * @return string|bool|null The requested information about the page type, a boolean indicating existence, or 'Not defined' if not found.
 */
function page_area($type, string $what = 'name', bool $bool_return = false)
{
    foreach( page_areas(false) as $k => $i)
    {
        if ($i->id == $type || $i->name == $type || $i->slug == $type || $i->url == $type || $i->path == $type)
        {
            return !$bool_return ? $i->$what : true;
        }
    }

    return !$bool_return ? page_area('Site', $what) : false;
}


/**
 * Get the degree of the page based on the given value.
 *
 * @param int $degree The degree value.
 * @return string The description of the page degree.
 */
function degree_page($key)
{
    $degrees = [
        'essential' => 'Essencial',
        'not_essential' => 'Não essencial',
        'article' => 'Artigo',
        'landingpage' => 'Landing page',
    ];

    return $degrees[$key] ?? 'Not defined';
}

function page_link($data = null)
{
    return "
    <a href='". get_url_page($data['slug'], 'full') ."' target='_blank'>
        {$data['title']}
        ".icon('fas fa-arrow-up-right-from-square')."
    </a>";
}
