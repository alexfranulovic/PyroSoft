<?php
if(!isset($seg)) exit;


/**
 * Executes a MySQL query using the global database connection.
 *
 * @param string $sql The SQL query to be executed.
 * @param bool $debug (optional) If true, debugging information will be displayed. Default is false.
 * @return mixed|false The result of the query or false on failure.
 */
function query_it(string $sql, bool $show_warnigs = false, bool $debug = false)
{
    global $conn;

    $code    = 'success';
    $message = '';
    $mysqli  = false;

    if ($debug) echo "<pre>$sql</pre><hr><br>";

    try {
        $mysqli = mysqli_query($conn, $sql);
    }

    catch (Throwable $e)
    {
        $code = 'error';
        $message = "<strong>MySQL Error:</strong> <i>" . htmlspecialchars($e->getMessage()) . "</i><br>";

        return (object)[
            'code' => $code,
            'message' => $message,
            'mysqli' => $mysqli
        ];
    }

    if ($show_warnigs)
    {
        $warnings = mysqli_get_warnings($conn);
        if ($warnings && $warnings instanceof mysqli_warning)
        {
            $code    = 'alert';
            $message = "<strong>Warning:</strong><br>";

            while ($warnings)
            {
                if (!empty($warnings->errno)) $message .= "#{$warnings->errno} ";
                if (!empty($warnings->message)) $message .= "<i>{$warnings->message}</i><br><br>";

                $next = $warnings->next();
                if ($next) $warnings = $next;
                else break;
            }
        }
    }

    return (object) [
        'code' => $code,
        'message' => $message,
        'mysqli' => $mysqli
    ];
}



function db_escape($value)
{
    global $conn;

    $value = (string) ($value ?? '');

    return mysqli_real_escape_string($conn, $value);
}



/**
 * Executes an SQL query and returns the results as an associative array.
 *
 * @param string $sql The SQL query to be executed.
 * @return array The query results as an associative array.
 */
function get_result(string $sql, $associative = 'default', bool $debug = false)
{
    $query   = query_it($sql, false, $debug);

    $results = ($query->mysqli != false)
        ? mysqli_fetch_assoc($query->mysqli)
        : null;

    $results = ($results != null)
        ? array_and_object_converter($results, $associative)
        : [];

    return $results;
}


function safe_where(string $field, string $operator, $value, $skip_sanitize = false)
{
    global $conn;

    $allowed_operators = ['=', '!=', '<', '>', '<=', '>=', 'LIKE', 'IN'];

    if (!$skip_sanitize) {
        $field = preg_replace('/[^a-zA-Z0-9_]/', '', $field);
    }

    $operator = in_array(strtoupper($operator), $allowed_operators) ? strtoupper($operator) : '=';

    if (is_array($value) && strtoupper($operator) === 'IN') {
        $escaped_values = array_map(fn($v) => "'" . db_escape($v) . "'", $value);
        return "(`$field` IN (" . implode(',', $escaped_values) . "))";
    }

    $value = db_escape($value);

    return $skip_sanitize
        ? "($field $operator '$value')"
        : "(`$field` $operator '$value')";
}


/**
 * Retrieves multiple rows of results from a database query.
 *
 * @param string|null $sql The SQL query to execute. If null, an empty array is returned.
 * @return array The fetched rows as an array of associative arrays.
 */
function get_results(string $sql = null, $associative = 'default', bool $debug = false)
{
    $rows = [];
    if ($sql !== null)
    {
        $query = query_it($sql, false, $debug);

        if (!$query->mysqli OR $query->mysqli == null) return [];
        while ($row = mysqli_fetch_assoc($query->mysqli)) {
            $rows[] = $row;
        }
    }

    return array_and_object_converter($rows, $associative);
}


/**
 * Checks if a table with the given name exists in the database.
 *
 * @param string $table The name of the table to check for existence.
 * @return bool True if the table exists, false otherwise.
 */
function is_table(string $table)
{
    $query = "SHOW TABLES LIKE '$table'";
    return (count_results($query) > 0) ? true : false;
}


/**
 * Inserts data into a database table.
 *
 * @param string $table The name of the table where data will be inserted.
 * @param array $data An associative array containing column names as keys and their respective values.
 * @param bool $show_warnings (optional) Show warnings if true.
 * @param bool $debug (optional) Print SQL query if true.
 */
function insert(string $table, array $data, bool $show_warnings = false, bool $debug = false)
{
    $columns = array_keys($data);

    $values = array_map(function ($value) {

        // SQL NULL (do not quote)
        if ($value === null) {
            return 'NULL';
        }

        // JSON for arrays/objects
        if (is_array($value) || is_object($value)) {
            return "'" . db_escape(json_encode($value, JSON_UNESCAPED_UNICODE)) . "'";
        }

        // Normalize scalar to string for checks/escaping
        $raw = (string)$value;

        // Keep NOW() unquoted
        if (strtoupper($raw) === 'NOW()') {
            return 'NOW()';
        }

        return "'" . db_escape($raw) . "'";
    }, array_values($data));

    $sql = "INSERT INTO {$table} (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ")";

    return query_it($sql, $show_warnings, $debug);
}



/**
 * Retrieves the next AUTO_INCREMENT value for a given database table.
 *
 * This function uses the `SHOW TABLE STATUS` SQL statement to fetch
 * the next auto-increment ID that will be used on the specified table.
 *
 * @param string $table The name of the table to inspect.
 * @return int|null The next auto-increment value, or null if it could not be determined.
 */
function get_next_auto_increment_id(string $table): ?int
{
    $status = get_result("SHOW TABLE STATUS LIKE '{$table}'");

    if (is_array($status) && isset($status['Auto_increment'])) {
        return (int) $status['Auto_increment'];
    }

    return null;
}


/**
 * Updates data in a database table.
 *
 * @param string $table The name of the table where data will be updated.
 * @param array $args An associative array containing 'data' (fields to update) and 'where' (conditions).
 * @param bool $show_warnings (optional) Show warnings if true.
 * @param bool $debug (optional) Print SQL query if true.
 */
function update(string $table, array $args, bool $show_warnings = false, bool $debug = false)
{
    if (!isset($args['data']) || empty($args['data'])) {
        return 'No data provided for update.';
    }

    // if (!array_key_exists('updated_at', $args['data'])) {
    //     $args['data']['updated_at'] = 'NOW()';
    // }

    $setClauses = array_map(function ($field, $value) {
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }

        // Se for uma string e contiver "NOW()", mantém sem aspas
        return "`$field` = " . (is_string($value) && strtoupper(trim($value)) === 'NOW()' ? 'NOW()' : "'" . db_escape($value) . "'");
    }, array_keys($args['data']), $args['data']);

    $sql = "UPDATE {$table} SET " . implode(', ', $setClauses);

    if (isset($args['where']) && is_array($args['where']) && !empty($args['where']))
    {
        $whereClauses = array_map(function ($clause) {
            return safe_where($clause['field'], $clause['operator'], $clause['value']);
        }, $args['where']);

        $sql .= " WHERE " . implode(' AND ', $whereClauses);
    }

    return query_it($sql, $show_warnings, $debug);
}


/**
 * Retrieves the number of rows returned by a database query.
 *
 * @param string $sql The SQL query to execute.
 * @return int The number of rows returned by the query.
 */
function count_results(string $sql, bool $debug = false)
{
    if ($debug) echo $sql;
    return mysqli_num_rows(query_it($sql)->mysqli);
}

function count_results_by_array(array $params, bool $debug = false)
{
    $table = $params['table'] ?? false;
    if (!$table) return 0;

    $sql = "SELECT COUNT(*) AS total FROM {$table}";

    if (isset($params['where']) && is_array($params['where']) && !empty($params['where']))
    {
        // Get logic operator (default AND)
        $where_logic = strtoupper($params['where_logic'] ?? 'AND');
        if (!in_array($where_logic, ['AND', 'OR'])) $where_logic = 'AND';

        $whereClauses = array_map(function ($clause) {
            return safe_where($clause['field'], $clause['operator'], $clause['value']);
        }, $params['where']);

        $sql .= " WHERE " . implode(" {$where_logic} ", $whereClauses);
    }

    $result = get_col($sql);

    if ($debug) echo $sql;
    if ($debug) print_r($result);

    return $result ?? 0;
}



/**
 * Generates an array with WHERE clause conditions for matching a specific 'id' field.
 *
 * @param mixed $id The value to match against the 'id' field.
 * @return array An array with WHERE clause conditions for matching the 'id' field.
 */
function where_equal_id($id)
{
    return [
        [
            'field'    => 'id',
            'operator' => '=',
            'value'    => $id,
        ],
    ];
}


/**
 * Checks if a specific column exists in a table.
 *
 * @param string $Table The name of the table.
 * @param string $Col The name of the column.
 * @return array|null The result of the query as an associative array with the total count or null if the column doesn't exist.
 */
function if_exist_col_bd(string $Table, string $Col)
{
    return get_col("SELECT
    count(TABLE_SCHEMA) AS TOTAL
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = '" . trim($Table) . "'
    AND COLUMN_NAME = '" . trim($Col) . "'
    AND TABLE_SCHEMA = '".DB_NAME."'");
}


/**
 * Adds a column to a table in the database.
 *
 * @param string $Table The name of the table.
 * @param string $Col The name of the column.
 * @param string $DataType The data type of the column (optional).
 * @return mixed The result of the query.
 */
function add_col_bd(string $Table, string $Col, string $DataType = '')
{
    return query_it("ALTER TABLE
    " . trim($Table) . "
    ADD " . trim($Col) . "
    {$DataType} DEFAULT null");
}


function rename_col_bd(string $Table, string $OldCol, string $NewCol, string $DataType = '')
{
    return query_it("ALTER TABLE
    " . trim($Table) . "
    CHANGE " . trim($OldCol) . " " . trim($NewCol) . "
    {$DataType} DEFAULT null");
}


/**
 * Retrieves the table names that have a foreign key referencing the specified column.
 *
 * @param string $Col The name of the column.
 * @return array The array of table names.
 */
function select_foreign_key(string $Col)
{
    $sql = query_it(
        "SELECT TABLE_NAME
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE COLUMN_NAME = '" . trim($Col) . "'
        AND TABLE_SCHEMA = '".DB_NAME."'");

    $tables = [];
    while ($rows = mysqli_fetch_assoc($sql->mysqli)) {
        $tables[] = $rows['TABLE_NAME'];
    }

    return $tables;
}


/**
 * Retrieves the column names of a specified table.
 *
 * @param string $Table The name of the table.
 * @return array The array of column names.
 */
function show_columns(string $Table)
{
    $sql = query_it(
        "SELECT COLUMN_NAME
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = '" . trim($Table) . "'
        AND TABLE_SCHEMA = '".DB_NAME."'");

    $cols = [];
    while ($rows = mysqli_fetch_assoc($sql->mysqli)) {
        $cols[] = $rows['COLUMN_NAME'];
    }

    return $cols;
}


/**
 * Retrieves the ID generated by the most recent INSERT query.
 *
 * @global resource $conn The database connection resource.
 * @return int|string The ID generated by the most recent INSERT query.
 */
function inserted_id()
{
    global $conn;
    return mysqli_insert_id($conn);
}


/**
 * Retrieves the number of affected rows by the most recent database operation.
 *
 * @global resource $conn The database connection resource.
 * @return int The number of affected rows.
 */
function affected_rows()
{
    global $conn;
    return mysqli_affected_rows($conn);
}


/**
 * Generates a copied name for a record based on the given conditions.
 *
 * @param string $type_crud The type of form ('update' or other).
 * @param string $Table The table name.
 * @param string $Cols The comma-separated column names.
 * @param array $Data An array containing the column values.
 * @return string The generated copied name.
 */
function copy_name(string $type_crud, string $Table, string $Cols, array $Data)
{
    $Col = explode(", ", $Cols);
    $sql = "SELECT id, " . $Col[0] . " FROM " . $Table . " WHERE ";

    $needle = ' - Cópia - ';
    $Where = "";
    for ($k = 0; $k < count($Col); $k++)
    {
        if ($k > 0) {
            $Where = $Where . " AND ";
            $Where .= $Col[$k] . " = '" . $Data[$Col[$k]] . "'";
        } else {
            $Where .= "(" . safe_where($Col[$k], '=', $Data[$Col[$k]]) . " OR " . $Col[$k] . " LIKE '" . db_escape($Data[$Col[$k]] . $needle) . "%')";
        }
    }

    $sql = $sql . $Where;

    if ($type_crud == 'update') {
        $sql = $sql . " AND id != " . $Data['id'];
    }

    $sql = $sql . " ORDER BY id DESC LIMIT 1";
    $result = get_result($sql);

    if ($result['id'])
    {
        if (strpos($Data[$Col[0]], $needle) !== false)
        {
            // If the name already contains the needle, append a number to make it unique
            $return = $result[$Col[0]] . $needle . 1;
        }

        else {
            // If the name does not contain the needle, increment the number in the copied name
            $NameExploded = explode($needle, $result[$Col[0]]);
            $NameExploded[1]++;
            $return = $NameExploded[0] . $needle . $NameExploded[1];
        }
    }

    else {
        // If no matching record found, use the original name
        $return = $Data[$Col[0]];
    }

    return $return;
}


/**
 * Executes a SQL query and returns the first column of the first row in the result.
 *
 * @param string $sql SQL query to execute.
 * @return mixed|null The first value from the first row, or null if no result.
 */
function get_col($sql)
{
    $result = get_result($sql);

    if (!$result || !is_array($result)) return null;

    $row = reset($result);

    return $row;
}


/**
 * Executes a SQL query and returns the first column of all rows as an array.
 *
 * @param string $sql SQL query to execute.
 * @return array An array of the first column values from all rows, or an empty array on failure.
 */
function get_cols($sql)
{
    $result = get_results($sql);
    if (!$result || !is_array($result)) return [];

    $values = [];
    foreach ($result as $row)
    {
        if (is_array($row)) {
            $first_value = reset($row);
            $values[] = $first_value;
        }
    }

    return $values;
}


/**
 * Build a SQL query based on the provided parameters.
 *
 * This function constructs a SELECT query with optional clauses like DISTINCT, JOIN, WHERE, ORDER BY, and pagination.
 * It takes an associative array of parameters to build the query.
 *
 * @param array $params An associative array containing the query parameters.
 * 		Possible keys:
 * 		- 'table' (string): The name of the main table for the query (required).
 * 		- 'distinct' (string): Optional distinct clause.
 * 		- 'registers_per_page' (int): Number of records per page for pagination (optional).
 * 		- 'current_page' (int): The current page for pagination (optional).
 * 		- 'fields' (array): An array of fields to select (optional, defaults to '*').
 * 		- 'joins' (array): An array of JOIN clauses (optional).
 * 		- 'where' (array): An array of WHERE clauses (optional).
 * 		- 'order_by' (array): An array of ORDER BY clauses (optional).
 *
 * @throws Exception Throws an exception if the 'table' parameter is not provided.
 * @throws Exception Throws an exception if the 'order_by' parameter has an invalid structure.
 *
 * @return string The generated SQL query.
 */
function query_builder($params, bool $debug = false)
{
    if (!isset($params['table'])) throw new Exception("A tabela não foi especificada.");

    $query = "SELECT ";

    if (isset($params['distinct'])) $query .= "DISTINCT " . $params['distinct'] . " ";

    $registersPerPage = 0;
    $currentPage = 1;
    if (isset($params['registers_per_page']))
    {
        $registersPerPage = intval($params['registers_per_page']);
        $currentPage      = isset($params['current_page']) ? intval($params['current_page']) : 1;
    }

    $query .= (isset($params['fields'])) ? implode(', ', $params['fields']) : '*';
    $query .= " FROM " . $params['table'] . " ";

    // JOINs
    if (isset($params['joins']) && is_array($params['joins']))
    {
        foreach ($params['joins'] as $join)
        {
            $joinType      = isset($join['type']) ? strtoupper($join['type']) : "INNER";
            $joinTable     = isset($join['table']) ? $join['table'] : "";
            $joinCondition = isset($join['condition']) ? $join['condition'] : "";

            if (!empty($joinType) && !empty($joinTable) && !empty($joinCondition))
            {
                $query .= "$joinType JOIN $joinTable ON $joinCondition ";
            }
        }
    }

    // WHERE
    if (isset($params['where']) && is_array($params['where']))
    {
        $query .= "WHERE ";
        $clauses = [];
        foreach ($params['where'] as $clause)
        {
            $field          = $clause['field'];
            $value          = $clause['value'];
            $operator       = $clause['operator'] ?? '=';
            $skip_sanitize  = $clause['skip_sanitize'] ?? false;

            $clauses[] = safe_where($field, $operator, $value, $skip_sanitize);
        }

        // Get logic operator (default AND)
        $where_logic = strtoupper($params['where_logic'] ?? 'AND');
        if (!in_array($where_logic, ['AND', 'OR'])) $where_logic = 'AND';

        $query .= implode(" {$where_logic} ", $clauses) . " ";
    }

    // ORDER BY
    if (isset($params['order_by']) && is_array($params['order_by']))
    {
        $query .= "ORDER BY ";
        $orderClauses = [];
        foreach ($params['order_by'] as $order)
        {
            if (!isset($order['field']) || !isset($order['way'])) {
                throw new Exception("Invalid ORDER BY structure.");
            }
            $field = $order['field'];
            $way = strtoupper($order['way']) === 'DESC' ? 'DESC' : 'ASC';
            $orderClauses[] = "`$field` $way";
        }
        $query .= implode(', ', $orderClauses) . " ";
    }

    // LIMIT/OFFSET
    if ($registersPerPage > 0)
    {
        $offset = ($currentPage - 1) * $registersPerPage;
        $query .= "LIMIT $registersPerPage OFFSET $offset";
    }

    if ($debug) echo "{$query}<br><hr>";

    return $query;
}



/**
 * Truncate a database table.
 *
 * Example usage:
 *   // Call the function to truncate a table named 'users'.
 *   truncate_table('users'); // Truncates the 'users' table and displays a success or error message accordingly.
 *
 * @param string $Table The name of the database table to truncate.
 * @param bool $debug (Optional) Enable debug mode. If set to true, the function outputs the SQL query instead of executing it.
 * @return bool Returns true if the table was successfully truncated or if debug mode is enabled, otherwise returns false.
 */
function truncate_table($Table, $debug = false)
{
    $sql = "TRUNCATE ". $Table;

    if($debug) {echo $sql;br();hr();}
	else query_it($sql);

    if (($debug) OR query_it($sql)) {
        //$_SESSION['msg'] = alert_message("SC_TO_TRUNCATE", 'alert');
        return true;
    } else {
        //$_SESSION['msg'] = alert_message("ER_TO_TRUNCATE", 'alert');
        return false;
    }
}


/**
 * Delete one record by id and optionally cascade delete in related tables.
 *
 * Expected params:
 * - table (string)               : main table name (required)
 * - id (scalar)                  : primary id to delete (required)
 * - foreign_key (string|null)    : FK column name present in related tables (optional)
 * - tables_to_action (array|null): list of tables allowed to cascade delete (optional)
 * - debug (bool)                 : when true, prints SQL instead of executing (optional)
 *
 * @return bool True on success or when debug is enabled; false if required params are missing or nothing was deleted.
 */
function delete_record(array $params, bool $debug = false)
{
    // --- Extract and normalize ------------------------------------------------
    $Table            = $params['table']            ?? '';
    $id               = $params['id']               ?? null;
    $Foreign_key      = $params['foreign_key']      ?? null;
    $tables_to_action = $params['tables_to_action'] ?? null;

    // Optional: custom where (when no ID is provided)
    $where_field      = $params['where_field']      ?? null;
    $where_value      = $params['where_value']      ?? null;

    if ($Table === '') {
        return false; // required table missing
    }

    // --- If no ID, but a custom where is provided, resolve IDs first ----------
    if ($id === null && $where_field !== null && $where_value !== null)
    {
        // Busca todos os IDs que batem com esse critério
        $sql = "SELECT id FROM {$Table} WHERE " . safe_where($where_field, '=', $where_value);
        $rows = get_results($sql) ?? [];

        if (empty($rows)) {
            if ($debug) {
                echo "No records found in {$Table} for {$where_field} = ";
                var_dump($where_value);
                echo "<br>";
            }
            return false;
        }

        $anyDeleted = false;

        foreach ($rows as $row) {
            $rid = $row['id'] ?? null;
            if ($rid === null) {
                continue;
            }

            // Chama a própria função, AGORA com ID resolvido e SEM where_field/where_value
            $subParams = $params;
            $subParams['id'] = $rid;
            unset($subParams['where_field'], $subParams['where_value']);

            if (delete_record($subParams)) {
                $anyDeleted = true;
            }
        }

        return $anyDeleted;
    }

    // Se ainda assim não tiver ID, não tem o que fazer
    if ($id === null) {
        return false;
    }

    // --- Main delete by ID ----------------------------------------------------
    $sql = "DELETE FROM {$Table} WHERE " . safe_where('id', '=', $id);

    if ($debug) {
        echo $sql; br(); hr();
    } else {
        query_it($sql);
    }

    // If in debug OR something was actually deleted, handle cascades (when configured)
    if ($debug || affected_rows())
    {
        if (!empty($Foreign_key))
        {
            // Tables that actually contain this foreign key in the schema
            $Foreign_key_table = select_foreign_key($Foreign_key);

            if (!empty($Foreign_key_table))
            {
                // Detect force mode: '-f' → delete in ALL tables that have this FK
                $forceAll = false;
                if ($tables_to_action === '-f') {
                    $forceAll = true;
                } elseif (is_array($tables_to_action) && in_array('-f', $tables_to_action, true)) {
                    $forceAll = true;
                }

                if ($forceAll) {
                    // Use *all* tables returned by select_foreign_key
                    $tables = $Foreign_key_table;
                } elseif (!empty($tables_to_action)) {
                    // Normalize to array (legacy behavior)
                    $tables = is_array($tables_to_action) ? $tables_to_action : [$tables_to_action];
                } else {
                    // Nothing to do if neither forceAll nor tables_to_action were provided
                    $tables = [];
                }

                foreach ($tables as $table)
                {
                    // Only touch tables that are confirmed to have the FK
                    if (in_array($table, $Foreign_key_table, true)) {

                        $sql = "DELETE FROM {$table} WHERE " . safe_where($Foreign_key, '=', $id);

                        if ($debug) {
                            echo $sql; br(); hr();
                        } else {
                            query_it($sql);
                        }
                    }
                }
            }
        }

        return true;
    }

    return false;
}



/**
 * Duplicate one record by id and (optionally) duplicate its related rows.
 *
 * Expected params:
 * - table (string)               : main table name (required)
 * - id (scalar)                  : parent id to duplicate (required)
 * - foreign_key (string|null)    : FK column name used by child tables (optional)
 * - tables_to_action (array|null): list of child tables allowed to be duplicated (optional)
 * - debug (bool)                 : when true, delegates printing to your helpers (optional)
 *
 * Notes:
 * - Skips columns: id, updated_at. Sets created_at = NOW() if that column exists.
 * - Child duplication runs only when the parent insert actually executes
 *   (i.e., not in debug mode, because we don't have the new parent id).
 *
 * @return bool True on success (or when debug is true and insert is printed); false otherwise.
 */
function duplicate_record(array $params)
{
    // --- Extract & validate ---------------------------------------------------
    $Table            = $params['table']            ?? '';
    $id               = $params['id']               ?? null;
    $Foreign_key      = $params['foreign_key']      ?? null;
    $tables_to_action = $params['tables_to_action'] ?? null;
    $debug            = (bool)($params['debug']     ?? false);

    if ($Table === '' || $id === null) return false; // required inputs missing

    // --- Load source row ------------------------------------------------------
    $data = get_result("SELECT * FROM {$Table} WHERE " . safe_where('id', '=', $id) . " LIMIT 1");
    if (empty($data)) {
        return false; // nothing to duplicate
    }

    // --- Build new row (mirror existing columns) ------------------------------
    $columns  = show_columns($Table);
    $new_data = [];

    foreach ($columns as $column)
    {
        if (in_array($column, ['id','updated_at'], true)) {
            continue;
        }
        if ($column === 'created_at') {
            $new_data[$column] = 'NOW()';
            continue;
        }
        if (array_key_exists($column, $data)) {
            $new_data[$column] = $data[$column];
        }
    }

    // insert($table, $assocData, $raw, $debug)
    insert($Table, $new_data, true, $debug);

        // In debug mode we cannot know the new parent id reliably,
        // so we stop here after printing the parent INSERT.
    if ($debug) {
        return true;
    }

    $parent_id = inserted_id();
    if (!$parent_id) {
        return false; // insert failed
    }

    // --- Duplicate children (only when we have a new parent id) --------------
    if (!empty($Foreign_key))
    {
        $FK_tables = select_foreign_key($Foreign_key); // tables that actually contain this FK
        if (!empty($tables_to_action) && !empty($FK_tables)) {

            $whitelist = is_array($tables_to_action) ? $tables_to_action : [$tables_to_action];

            foreach ($whitelist as $childTable)
            {
                if (!in_array($childTable, $FK_tables, true)) {
                    continue; // skip tables that don't hold this FK
                }

                $records = get_results(
                    "SELECT * FROM {$childTable} WHERE " . safe_where($Foreign_key, '=', $id) . " ORDER BY id"
                );
                if (empty($records)) {
                    continue;
                }

                $childCols = show_columns($childTable);

                foreach ($records as $record)
                {
                    $entry = [];
                    foreach ($childCols as $col)
                    {
                        if (in_array($col, ['id','updated_at'], true)) {
                            continue;
                        }
                        if ($col === $Foreign_key) {
                            $entry[$col] = $parent_id; // rebind to the new parent
                            continue;
                        }
                        if ($col === 'created_at') {
                            $entry[$col] = 'NOW()';
                            continue;
                        }
                        if (array_key_exists($col, $record)) {
                            $entry[$col] = $record[$col];
                        }
                    }

                    insert($childTable, $entry, true, $debug);
                }
            }
        }
    }

    return true;
}




/**
 *  Set the charset, collate and details of languages
 */
query_it("SET NAMES 'utf8mb4'");
query_it('SET character_set_connection=utf8mb4');
query_it('SET character_set_client=utf8mb4');
query_it('SET character_set_results=utf8mb4');
