<?php
if(!isset($seg)) exit;


/**
 * Check if a user is logged in.
 *
 * @param mixed $current_user The user data or ID to check (not used in the function).
 *
 * @return bool Returns true if the user is logged in, false otherwise.
 */
function is_user_logged_in()
{
    return isset($_SESSION['current_user']['id']) ? true : false;
}


/**
 * Check if a user exists in the database by login, email, or ID
 *
 * @param string|int $user The login, email, or ID of the user to check for
 * @return bool True if the user exists in the database, false otherwise
 */
function is_user($user)
{
    $query = count_results("SELECT * FROM tb_users WHERE (login = '{$user}') OR (email = '{$user}') OR id = '{$user}'");
    return ( $query > 0 ) ? true : false;
}


/**
 * Get user data from the database by login, email, or ID
 *
 * @param string|int $user The login, email, or ID of the user to get data for
 * @return array An associative array with the user data, or an empty array if no user is found
 */
function get_user($user)
{
    $sql = "
        SELECT
            u.*,
            GROUP_CONCAT(ura.role_id) AS roles
        FROM tb_users u
        LEFT JOIN tb_user_role_assignments ura
            ON ura.user_id = u.id
        WHERE (u.login = '{$user}' OR u.email = '{$user}' OR u.id = '{$user}')
        GROUP BY u.id
        LIMIT 1
    ";

    $user_data = get_result($sql);

    if (!empty($user_data)) {
        $user_data['roles'] = isset($user_data['roles'])
            ? explode(',', $user_data['roles'])
            : lowest_role_user();
    }

    return $user_data;
}


/**
 * Retrieves all user information from the database.
 *
 * @param mixed $user Unused parameter in this function.
 * @return array|null The user information as an array or null if no users found.
 */
function get_users()
{
    $sql = "
        SELECT
            u.*,
            GROUP_CONCAT(ura.role_id) AS roles
        FROM tb_users u
        LEFT JOIN tb_user_role_assignments ura
            ON ura.user_id = u.id
        GROUP BY u.id";

    $users = get_results($sql);

    foreach ($users as &$user) {
        $user['roles'] = isset($user['roles']) ? explode(',', $user['roles']) : [];
    }

    return $users;
}


/**
 * Returns user status.
 *
 * @param bool $for_selects Indicates whether the output should be formatted for selects.
 * @return mixed|string|array The user status.
 */
function user_status(bool $for_selects = false)
{
    global $user_status;

    $res = $user_status;

    if ($for_selects == true)
    {
        $res = [];
        foreach($user_status as $stats)
        {
            $res[] = [
                'value' => $stats['id'],
                'display' => $stats['name'],
            ];
        }
    }

    return $res;
}


/**
 * Extracts the first word from a given string, ignoring punctuation and extra spaces.
 *
 * This function:
 * - Trims leading/trailing whitespace.
 * - Replaces multiple spaces with a single space.
 * - Removes punctuation.
 * - Returns the first word in the string.
 *
 * @param string $value The input string to extract the first word from.
 * @return string|null The cleaned first word, or null if not found.
 */
function get_first_word(string $value = '')
{
    // Remove punctuation
    $value = preg_replace('/[[:punct:]]+/', '', $value);

    // Normalize spaces and trim
    $value = trim(preg_replace('/\s+/', ' ', $value));

    // Explode and return first word
    $words = explode(' ', $value);
    return $words[0] ?? null;
}


function validate_unique_user_email(string $email, $user_id = null, bool $debug = false)
{
    global $current_user;

    $email = addslashes(trim($email));

    $sql = "SELECT id FROM tb_users WHERE (email = '{$email}')";

    if (!empty($current_user['id'])) {
        $sql .= " AND (id != '". (int)$current_user['id'] ."')";
    }

    if (!is_null($user_id)) {
        $sql .= " AND (id != '{$user_id}')";
    }

    $user = get_result($sql);

    if ($debug) {
        print_r($user);
        print_r($sql);
        if (!empty($current_user['id'])) print_r('logged:'. $current_user['id']);
        if (!is_null($user_id)) print_r('user_id:'. $user_id);
    }

    if (!empty($user['id']))
    {
        $login_url = "<a href='" . site_url('/login') . "'>login</a>";
        $pass_recover_url = "<a href='" . site_url('/login?forgot-password') . "'>recuperar senha</a>";

        return [
            'error' => true,
            'msg'   => "Já existe uma conta com esse e-mail. Deseja fazer {$login_url} ou {$pass_recover_url}?"
        ];
    }
}



/**
 * Retrieves the user information based on the session's, if available.
 * If the session value is not set or the user is not found, an empty array is assigned to $user.
 */
$user = $current_user = [];
if (is_user_logged_in())
{
    $user = $current_user = get_user($_SESSION['current_user']['id']);
    if (empty($current_user)) {
        logout();
    }
}


$all_status[] = [
    'function' => 'user_status',
    'name' => 'Usuários'
];
