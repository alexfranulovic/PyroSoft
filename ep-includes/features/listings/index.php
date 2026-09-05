<?php
if (!isset($seg)) exit;

/**
 * ============================================================================
 * Feature: listings
 * ============================================================================
 *
 * Replaces the old single custom-listings.php global registry
 * ($GLOBALS['custom_tables']) with on-demand, per-listing files:
 *
 *   feature/{feature-name}/custom-listings/{listing}.php
 *   plugin/{plugin-name}/custom-listings/{listing}.php
 *   this-system/custom-listings/{listing}.php
 *
 * Each listing file just `return`s its config array (table/get_data_by/
 * fields/actions/insert_button — same shape as before). Nothing is loaded
 * or evaluated until custom_listing_table() or the get-custom-listing REST
 * route actually asks for that one specific listing.
 *
 * Usage:
 *
 *   feature('listings');
 *   echo custom_listing_table('feature/page-crud-management', 'pages');
 *   echo custom_listing_table('plugin/pyrosales', 'orders');
 *   echo custom_listing_table('this-system', 'ads');
 * ============================================================================
 */

/**
 * ----------------------------------------------------------------------
 * Base paths — using the real constants your system already defines
 * (FEATURES_ABSOLUTE_PATH, PLUGINS_ABSOLUTE_PATH, and __BASE_DIR__ +
 * THIS_SYSTEM_PATH for the direct this-system/custom-listings/ case).
 * No local guessed constants needed anymore.
 * ----------------------------------------------------------------------
 */

/**
 * Resolves a listing's file path from its `$type` ("feature/{name}",
 * "plugin/{name}", or "this-system") and `$listing_name`. Returns null for
 * an unrecognized type, so callers can fail gracefully instead of a fatal.
 */
function resolve_custom_listing_path(string $type, string $listing_name): ?string
{
    $parts    = explode('/', $type, 2);
    $category = $parts[0];
    $owner    = $parts[1] ?? null;

    switch ($category) {
        case 'feature':
            if (empty($owner)) return null;
            $base = FEATURES_ABSOLUTE_PATH . "/{$owner}";
            break;

        case 'plugin':
            if (empty($owner)) return null;
            $base = PLUGINS_ABSOLUTE_PATH . "/{$owner}";
            break;

        case 'this-system':
            // THIS_SYSTEM_PATH already ends in a slash ("this-system/"),
            // unlike FEATURES_PATH/PLUGINS_PATH — rtrim below normalizes
            // both cases before appending custom-listings/.
            $base = __BASE_DIR__ . THIS_SYSTEM_PATH;
            break;

        default:
            return null;
    }

    return rtrim($base, '/') . "/custom-listings/{$listing_name}.php";
}

/**
 * Loads (and in-request caches) a single listing's config array by
 * `include`-ing its file. The file is expected to `return [...]` its
 * config — nothing is registered into a global, so only the listing
 * actually asked for ever gets loaded/parsed.
 */
function load_custom_listing(string $type, string $listing_name): ?array
{
    global $seg;
    static $cache = [];
    $cache_key = "{$type}:{$listing_name}";

    if (array_key_exists($cache_key, $cache)) {
        return $cache[$cache_key];
    }

    $path = resolve_custom_listing_path($type, $listing_name);

    if (empty($path) || !is_file($path)) {
        return $cache[$cache_key] = null;
    }

    $listing = include $path;

    return $cache[$cache_key] = (is_array($listing) ? $listing : null);
}

/** Calls a field's `function_view` (if any) as `$callable($value, $row)`, else returns the raw value. */
function render_listing_cell(array $field, $value, array $row)
{
    if (!empty($field['function_view']) && is_callable($field['function_view'])) {
        return call_user_func($field['function_view'], $value, $row) ?? ($value ?? '-');
    }

    return $value ?? '-';
}

/**
 * Builds one table row (array of rendered cells) from a data row, calling
 * render_listing_cell() per field and appending the actions cell if any.
 *
 * No longer embeds a data-row-id marker cell — the front-end
 * (tables-gridjs-init.js) now detects the id column generically from the
 * `head` array (whichever label starts/ends with "id") and sets
 * `data-row-id` on the <tr> itself, which works uniformly for CRUD
 * listings and custom listings alike without any per-row markup here.
 */
function build_listing_row(array $fields, array $actions, array $data): array
{
    $row = [];

    foreach ($fields as $field) {
        $row[] = render_listing_cell($field, $data[$field['name']] ?? null, $data);
    }

    if (!empty($actions)) {
        $row[] = build_table_actions($actions, $data['id'] ?? null);
    }

    return $row;
}

/**
 * Calls a field's `function_process` (if any) on the raw search term before
 * it's used to build that field's own WHERE clause. Falls back to the raw
 * term when no function_process is set.
 */
function resolve_field_search_value(array $field, string $raw_value): string
{
    if (!empty($field['function_process']) && is_callable($field['function_process'])) {
        return (string) call_user_func($field['function_process'], $raw_value);
    }

    return $raw_value;
}

/**
 * Table-backed listing: search across visible+existing columns (each field's
 * own function_process applied to the term first), single-column order,
 * SQL-level pagination, cells rendered via render_listing_cell().
 */
function query_custom_listing_table(string $table, array $fields, array $actions, array $request): array
{
    $available_columns = show_columns($table);

    $where = [];
    if (!empty($request['search_value'])) {
        foreach ($fields as $field) {
            if (!in_array($field['name'], $available_columns)) continue;

            $term = resolve_field_search_value($field, $request['search_value']);
            if ($term === '') continue;

            $where[] = ['field' => $field['name'], 'operator' => 'LIKE', 'value' => "%{$term}%"];
        }
    }

    $order_by = [];
    if (!empty($request['order_field']) && in_array($request['order_field'], $available_columns)) {
        $order_by[] = ['field' => $request['order_field'], 'way' => $request['order_dir'] === 'desc' ? 'DESC' : 'ASC'];
    }

    $total_records  = count_results("SELECT * FROM {$table}");
    $total_filtered = count_results_by_array(['table' => $table, 'where' => $where, 'where_logic' => 'OR']);

    $length = max(1, (int) $request['length']);
    $rows   = get_results(query_builder([
        'table'              => $table,
        'where'              => $where,
        'where_logic'        => 'OR',
        'order_by'           => $order_by,
        'current_page'       => floor($request['start'] / $length) + 1,
        'registers_per_page' => $length,
    ]));

    $body = [];
    foreach ($rows as $data) {
        $body[] = build_listing_row($fields, $actions, $data);
    }

    return [
        'recordsTotal'    => $total_records,
        'recordsFiltered' => $total_filtered,
        'data'            => $body,
    ];
}

/**
 * Function-backed listing. Two modes, auto-detected by the function's own
 * signature (via Reflection — no extra config needed):
 *
 *   - Takes 1+ parameters: an "efficient" source that does its own
 *     SQL-level filtering/sorting/pagination. Called with ONE standardized
 *     args array (search / sort_field / sort_dir / limit / offset) and
 *     must return ['total' => int, 'data' => array-of-rows]. Since
 *     `function_name` can itself be a closure, this is the escape hatch
 *     for adapting a function whose own param names/shape don't match ours
 *     (see plugin/pyrosales/custom-listings/orders.php for a real example).
 *
 *   - Takes 0 parameters: returns the WHOLE dataset; search/sort/pagination
 *     happen here in PHP. Fine for small-to-medium datasets.
 */
function query_custom_listing_function($function_name, array $fields, array $actions, array $request): array
{
    if (is_string($function_name)) {
        $function_name = rtrim(trim($function_name), '()');
        if (!function_exists($function_name)) {
            return ['recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => "Function {$function_name} not found."];
        }
    } elseif (!is_callable($function_name)) {
        return ['recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'function_name is not callable.'];
    }

    $reflection   = new ReflectionFunction($function_name);
    $accepts_args = $reflection->getNumberOfParameters() > 0;

    if ($accepts_args) {
        $result = call_user_func($function_name, [
            'search'     => $request['search_value'] ?? '',
            'sort_field' => $request['order_field'] ?? null,
            'sort_dir'   => $request['order_dir'] ?? null,
            'limit'      => $request['length'],
            'offset'     => $request['start'],
            // Per-render custom params — see custom_listing_table()'s
            // `listing_params` and the plans.php example, which uses this
            // to scope one rendered table to a single target_audience +
            // interval_count + interval_unit group.
            'extra'      => $request['extra'] ?? [],
        ]);

        $rows  = $result['data'] ?? [];
        $total = (int) ($result['total'] ?? count($rows));
    } else {
        $rows  = (array) call_user_func($function_name);
        $total = count($rows);

        if (!empty($request['search_value'])) {
            $rows = array_values(array_filter($rows, function ($row) use ($fields, $request) {
                foreach ($fields as $field) {
                    $term = resolve_field_search_value($field, $request['search_value']);
                    if ($term === '') continue;
                    if (mb_strpos(mb_strtolower((string) ($row[$field['name']] ?? '')), mb_strtolower($term)) !== false) {
                        return true;
                    }
                }
                return false;
            }));
        }

        $total_filtered = count($rows);

        if (!empty($request['order_field'])) {
            $order_field = $request['order_field'];
            $order_dir   = $request['order_dir'] === 'desc' ? -1 : 1;
            usort($rows, fn($a, $b) => $order_dir * (($a[$order_field] ?? null) <=> ($b[$order_field] ?? null)));
        }

        $rows = array_slice($rows, (int) $request['start'], max(1, (int) $request['length']));
    }

    $body = [];
    foreach ($rows as $data) {
        $body[] = build_listing_row($fields, $actions, $data);
    }

    return [
        'recordsTotal'    => $total,
        'recordsFiltered' => $accepts_args ? $total : ($total_filtered ?? $total),
        'data'            => $body,
    ];
}

/**
 * Formats a listing's config into table()'s own $Attr shape and delegates
 * to it — table() itself is used completely unmodified. `table_attributes`
 * carries `data-listing-type` + `data-listing-key` (the front-end needs
 * both to know which file to ask get-custom-listing to load), plus, when
 * the listing config sets `orderable => true`, `data-order-record` and
 * (optionally) `data-order-endpoint` — letting a listing point drag
 * reordering at its own custom REST route instead of the generic
 * `order-record` one. All in the `key: (value);` syntax
 * parse_html_tag_attributes() already expects elsewhere in this codebase.
 * `crud_id` is left blank; harmless, our front-end checks the listing
 * attributes first regardless.
 *
 * `$attr['listing_params']` (optional array) lets the SAME listing config
 * be reused for several different renders with a different runtime filter
 * each time — e.g. calling custom_listing_table('plugin/pyrosales',
 * 'plans', ['listing_params' => ['target_audience' => 'escort', ...]])
 * once per plan segment, all backed by the one plans.php file. It's
 * base64(json_encode(...))'d into `data-listing-params` (avoids fighting
 * the `key: (value);` DSL over JSON's own colons/braces/quotes), read by
 * the front-end, sent back to get-custom-listing, and handed to the
 * listing's function_name closure as `$args['extra']`.
 */
function custom_listing_table(string $type, string $listing_name, array $attr = []): string
{
    $listing = load_custom_listing($type, $listing_name);
    if (empty($listing)) {
        return "<!-- custom listing '{$type}/{$listing_name}' not found -->";
    }

    $fields = $listing['fields'] ?? [];
    $crud_panel = $listing['crud_panel'] ?? [];
    $title  = $attr['title'] ?? ($listing['title'] ?? ucwords(str_replace('_', ' ', $listing_name)));

    $head = array_map(fn($field) => $field['label'] ?? $field['name'], $fields);
    if (!empty($listing['actions'])) {
        $head[] = 'Ações';
    }

    $hooks_out = [];
    $insert = $listing['insert_button'] ?? null;
    if (!empty($insert['permission'])) {
        $hooks_out[] = [
            'title'    => $insert['title'] ?? 'Cadastrar',
            'url'      => $insert['url'] ?? '',
            'color'    => $insert['color'] ?? 'outline-success',
            'pre_icon' => $insert['pre_icon'] ?? 'fas fa-plus',
        ];
    }

    $table_attributes = "data-listing-type: ({$type}); data-listing-key: ({$listing_name});";
    if (!empty($listing['orderable'])) {
        $table_attributes .= ' data-order-record: (1);';
        if (!empty($listing['order_endpoint'])) {
            $table_attributes .= " data-order-endpoint: ({$listing['order_endpoint']});";
        }
    }
    if (!empty($attr['listing_params'])) {
        $encoded = base64_encode(json_encode($attr['listing_params']));
        $table_attributes .= " data-listing-params: ({$encoded});";
    }

    return table([
        'div_attributes'   => $attr['div_attributes'] ?? null,
        'table_attributes' => $table_attributes,
        'data_table'       => true,
        'settings'         => ['data_table_async'],
        'crud_id'          => '',
        'head'             => $head,
        'body'             => [], // always empty — rows are fetched via get-custom-listing
        'crud_panel'       => [
            'show_panel' => $crud_panel['show_panel'] ?? false,
            'show_name'  => $crud_panel['show_name'] ?? false,
            'form_name'  => $title,
            'hooks_out'  => $hooks_out,
            'hooks_in'   => [],
        ],
    ]);
}
