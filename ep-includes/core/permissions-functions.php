<?php
if(!isset($seg)) exit;


/**
 * Check if a user or the current user is in the development environment.
 *
 * This function determines whether a specific user or the current user is in the development environment.
 * It checks if the user's access level is set to 1, which typically represents the development environment.
 *
 * @param int|null $user_given The ID of the user to check. Defaults to the current user if not provided.
 *
 * @return bool Returns true if the user is in the development environment, otherwise false.
 */
function is_dev($user_given = null)
{
    global $current_user;

    $uid = $user_given !== null
        ? (int)$user_given
        : (isset($current_user['id']) ? (int)$current_user['id'] : 0);

    if ($uid <= 0) {
        return false;
    }

    // Check assigned roles for 'developer' or role id = 1
    $dev_role = get_result("
        SELECT r.id, r.slug
        FROM tb_user_role_assignments ura
        INNER JOIN tb_user_roles r ON r.id = ura.role_id
        WHERE ura.user_id = '{$uid}'
          AND (r.slug = 'developer' OR r.id = 1)
        LIMIT 1
    ");

    return !empty($dev_role);
}

function highest_role_user($user_id = 0)
{
    $role = get_col("
        SELECT
            GROUP_CONCAT(ura.role_id) AS roles
        FROM tb_users u
        LEFT JOIN tb_user_role_assignments ura
            ON ura.user_id = u.id
        WHERE u.id = '{$user_id}'
        GROUP BY u.id
        LIMIT 1
    ");

    if (empty($role)) {
        $role = implode(',',lowest_role_user());
    }

    $role = get_result("
        SELECT *
        FROM tb_user_roles
        WHERE id IN ({$role})
        ORDER BY COALESCE(order_reg, 0) ASC, id ASC
        LIMIT 1
    ");

    return $role;
}

/**
 * Retrieves a page's information based on its address and permission settings.
 *
 * @global array $slug The URL slug parameters.
 * @return array|null An array containing the page's information or null if no page is found.
 */
function is_user_allowed_to_access_area(bool $bool_return = true, bool $debug = false)
{
    global $current_user, $slug, $page_path;

    $page_area = page_area($page_path, 'path');

    $roles = load_roles();

    $ids = implode(",", $roles);

    $sql = "
    SELECT COUNT(DISTINCT page.id) AS total_pages
    FROM tb_pages page
    LEFT JOIN tb_user_role_permissions permission
        ON permission.page_id = page.id
        AND permission.role_id IN ({$ids})
    WHERE page.page_area = '{$page_area}'
    AND (
        (
            page.permission_type = 'except_these'
            AND (permission.allowed IS NULL OR permission.allowed != 1)
        )
        OR (
            page.permission_type = 'only_these'
            AND permission.allowed = 1
        )
    )";

    if ($debug) echo "<pre>$sql</pre>";

    $result = get_col($sql);

    return !$bool_return
        ? $result
        : ($result > 0);
}

function load_roles()
{
    global $current_user;

    return !empty($current_user['roles'])
        ? $current_user['roles']
        : lowest_role_user();
}


/**
 * Check user permission for a specific resource.
 *
 * This function checks if the logged-in user has permission to access a specific resource or page.
 * It takes into account the user's access level, the resource type (page or CRUD), and the resource identifier (key).
 *
 * @param string $key The identifier of the resource or page.
 * @param string $type The type of the resource (page or CRUD). Defaults to 'page'.
 *
 * @return bool Returns true if the user has permission to access the resource, otherwise false.
 */
function load_permission($key, string $type = 'page', bool $debug = false)
{
    global $current_user;

    $roles = load_roles();

    $ids = implode(",", $roles);
    $status_id = is_dev() ? 'method.status_id != 2' : 'method.status_id = 1';

    $method = [
        'page' => [
            'table' => "tb_pages",
            'foreign_key' => "page_id",
            'entries' => "OR method.slug = '{$key}'",
            'where' => "AND $status_id",
        ],
        'crud' => [
            'table' => "tb_cruds",
            'foreign_key' => "crud_id",
            'entries' => "",
            'where' => "AND action_trigger = '{$type}' AND $status_id",
        ],
        'custom' => [
            'table' => "tb_user_role_permissions",
            'foreign_key' => "permission_id",
            'entries' => "OR method.slug = '{$key}'",
            'where' => "AND method.action_trigger = 'custom'",
        ]
    ];

    $method = $method[$type] ?? $method['crud'];

    $sql = "
    SELECT method.id
    FROM {$method['table']} method
    LEFT JOIN tb_user_role_permissions permission
        ON permission.{$method['foreign_key']} = method.id
        AND permission.role_id IN ({$ids})
    WHERE (method.id = '{$key}' {$method['entries']})
    {$method['where']}
    AND (
        (
            method.permission_type = 'except_these'
            AND (permission.allowed IS NULL OR permission.allowed != 1)
        )
        OR (
            method.permission_type = 'only_these'
            AND permission.allowed = 1
        )
    )
    LIMIT 1";

    if ($debug) echo "<pre>$sql</pre>";

    $res = count_results($sql);

    return $res > 0;
}



/**
 * Retrieves permission data from the database based on the given ID and type.
 *
 * This function queries the permissions system and retrieves data for custom permissions,
 * CRUD permissions, or page permissions. If an ID and type are provided, it returns a single
 * permission object; otherwise, it returns a list of all permissions.
 *
 * @param int|null $id Optional. The specific ID of the permission to retrieve.
 * @param string $type Optional. The type of the permission ('Custom', 'Crud', or 'Page').
 * @param bool $debug Optional. If true, prints the generated SQL query for debugging purposes.
 * @return array Returns a single permission array if ID and type are provided, or an array of permissions otherwise.
 */
function get_permissions($id = null, string $type = '', bool $debug = false)
{
    $specfic_data = (!empty($id) AND !empty($type))
        ? "AND ". strtolower($type) .".id = '{$id}'"
        : '';

    $sql = "
    SELECT
    custom.id AS custom_id,
    custom.name AS custom_name,
    custom.slug AS custom_slug,
    custom.permission_type AS custom_permission_type,
    crud.id AS crud_id,
    crud.piece_name AS crud_name,
    crud.slug AS crud_slug,
    crud.permission_type AS crud_permission_type,
    page.id AS page_id,
    page.title AS page_title,
    page.slug AS page_slug,
    page.permission_type AS page_permission_type,
    page.page_area

    FROM tb_user_role_permissions permission
    LEFT JOIN tb_pages AS page ON page.id = permission.page_id
    LEFT JOIN tb_cruds AS crud ON crud.id = permission.crud_id
    LEFT JOIN tb_user_role_permissions AS custom ON custom.id = permission.permission_id

    WHERE
    ((crud.type_crud = 'master' AND permission.crud_id IS NOT NULL)
        OR (permission.page_id IS NOT NULL)
        OR (permission.permission_id IS NOT NULL))

    {$specfic_data}

    GROUP BY permission.page_id, permission.crud_id, permission.permission_id";

    if ($debug) echo "<pre>$sql</pre>";

    $results = get_results($sql);

    foreach ($results as $data)
    {
        $row = [];
        if (!empty($data['custom_id']))
        {
            $row['id']   = $data['custom_id'];
            $row['type'] = "Custom";
            $row['name'] = $data['custom_name'] ?? '';
            $row['slug'] = $data['custom_slug'] ?? '';
            $row['permission_type'] = $data['custom_permission_type'] ?? '';
        }

        elseif (!empty($data['crud_id']))
        {
            $row['id']   = $data['crud_id'];
            $row['type'] = "Crud";
            $row['name'] = $data['crud_name'] ?? '';
            $row['slug'] = $data['crud_slug'] ?? '';
            $row['permission_type'] = $data['crud_permission_type'] ?? '';
        }

        elseif (!empty($data['page_id']))
        {
            $row['id']   = $data['page_id'];
            $row['type'] = "Page";
            $row['name'] = $data['page_title'] ?? '';
            $row['slug'] = $data['page_slug'] ?? '';
            $row['permission_type'] = $data['page_permission_type'] ?? '';
        }

        $body[] = $row;
    }

    return (!empty($id) AND !empty($type))
        ? $body[0] ?? []
        : $body ?? [];
}


/**
 * Synchronizes the assigned roles of a user with a given list of role IDs.
 *
 * This function updates the user's role assignments by:
 * - Adding new roles that the user currently does not have.
 * - Removing roles that the user no longer should have.
 *
 * @param int $user_id The ID of the user whose roles will be updated.
 * @param array $roles An array of role IDs that should be assigned to the user.
 * @return bool Always returns true after synchronization.
 */
function edit_user_role_assignments($user_id = null,  $roles = [])
{
    if (empty($user_id)) {
        return false;
    }

    if (!is_array($roles)) {
        return false;
    }

    $roles = array_filter(array_unique($roles), fn($id) => is_numeric($id) && $id > 0);

    $existing_roles = get_cols("SELECT role_id FROM tb_user_role_assignments WHERE user_id = '{$user_id}'");

    $to_add = array_diff($roles, $existing_roles);
    $to_remove = array_diff($existing_roles, $roles);

    if (empty($to_add) && empty($existing_roles))
    {
        $to_add = lowest_role_user();
    }

    // Add new permissions
    foreach ($to_add as $role_id)
    {
        insert('tb_user_role_assignments', [
            'user_id'    => $user_id,
            'role_id'    => $role_id,
            // 'started_at' => date('Y-m-d H:i:s'),
            // 'expires_at' => null
        ]);
    }

    // Remove unusual permissions
    if (!empty($to_remove)) {
        $ids = implode(',', $to_remove);
        query_it("DELETE FROM tb_user_role_assignments WHERE user_id = '{$user_id}' AND role_id IN ({$ids})");
    }

    return true;
}


/**
 * Retrieve the lowest level of access from the database.
 *
 * This function queries the database to retrieve the lowest level of access. It selects the
 * level with the highest order value from the 'tb_user_roles' table.
 *
 * @return array|false An associative array containing the lowest level's ID and order value, or false if no data is found.
 */
function lowest_role_user()
{
    global $config;

    $lowest_role = is_array($config['lowest_role'])
        ? $config['lowest_role']
        : [$config['lowest_role']];

    return $lowest_role;
}


/**
 * Retrieves all levels access.
 *
 * @param mixed $product Unused parameter in this function.
 * @return array The products information as an array of associative arrays.
 */
function get_roles_by_user_id($user_id = 0)
{
    $lowest = lowest_role_user();

    $user_id = ($user_id > 0) ? $user_id : id_by_get();

    $query = "
        SELECT
            r.id     AS value,
            r.name   AS display,
            (ura.user_id IS NOT NULL) AS checked
        FROM tb_user_roles r
        LEFT JOIN tb_user_role_assignments ura
            ON ura.role_id = r.id AND ura.user_id = '{$user_id}' AND ura.user_id != 0
        ORDER BY r.name ASC";

    return get_results($query);
}


/**
 * Returns a comma-separated string with all the user's role names.
 *
 * - If the user has roles assigned → returns their names.
 * - If the user has NO roles → fallback to lowest_role_user().
 *
 * @param int    $user_id
 * @param string $glue
 * @return string
 */
function user_roles_to_string($user_id = 0, string $glue = ', '): string
{
    $uid = $user_id > 0 ? (int)$user_id : (int) id_by_get();

    // 1. Fetch roles actually assigned to user
    $sql = "
        SELECT r.id, r.name AS role_name
        FROM tb_user_roles r
        INNER JOIN tb_user_role_assignments ura
            ON ura.role_id = r.id
        WHERE ura.user_id = '{$uid}' AND ura.user_id != 0
        ORDER BY r.name ASC
    ";

    $rows = get_results($sql) ?: [];

    // 2. If user HAS roles → return them
    if (!empty($rows)) {
        $names = array_map(static fn($row) => $row['role_name'], $rows);
        return implode($glue, $names);
    }

    // 3. If user has NO roles → use fallback roles
    $fallback_ids = lowest_role_user();   // e.g. [1,2]

    if (empty($fallback_ids))  return '';

    // Build safe IN() clause
    $in_clause = implode(',', array_map('intval', $fallback_ids));

    $fallback = get_results("
        SELECT name AS role_name
        FROM tb_user_roles
        WHERE id IN ({$in_clause})
        ORDER BY name ASC
    ") ?: [];

    // Return fallback names
    $names = array_map(static fn($row) => $row['role_name'], $fallback);

    return implode($glue, $names);
}




/**
 * Get an administrative description for a general status value.
 *
 * This function takes a general status value and returns an optional administrative description.
 * If the value is 3, it appends ' - (Análise)' to indicate that the status is under analysis.
 *
 * @param int $value The general status value.
 * @return string The administrative description, or an empty string if no description is needed.
 */
function general_status_admin($value)
{
    return ($value==3) ? ' - (Análise)' : '';
}
