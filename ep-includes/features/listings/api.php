<?php
if (!isset($seg)) exit;

/**
 * REST route — sibling of `get-crud-list`, same request/response contract.
 * Now resolves the listing dynamically via load_custom_listing($type, $key)
 * instead of looking it up in a pre-populated global array — only the one
 * file for the requested listing is ever included.
 *
 * NOTE ON PERMISSIONS: mirrors get-crud-list's own
 * `permission_callback => '__return_true'`, i.e. exactly as open as the
 * existing CRUD listing route already is — not a new gap, but worth
 * revisiting both together if that's not the intended access level.
 */
register_rest_route('get-custom-listing', [
  'methods'  => 'POST',
  'callback' => function ()
  {
    feature('listings');

    $listing_type = $_POST['listing_type'] ?? null;
    $listing_key  = $_POST['listing_key'] ?? null;
    $draw         = intval($_POST['draw'] ?? 0);

    if (empty($listing_type) || empty($listing_key)) {
      return ['draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'listing_type/listing_key not provided.'];
    }

    $listing = load_custom_listing($listing_type, $listing_key);
    if (empty($listing)) {
      return ['draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'Listing not found.'];
    }

    $fields  = $listing['fields'] ?? [];
    $actions = $listing['actions'] ?? [];
    $order   = $_POST['order'][0] ?? [];

    $order_field = null;
    if (isset($order['column'], $fields[$order['column']])) {
      $order_field = $fields[$order['column']]['name'];
    }

    // Decoded from the base64(json) `data-listing-params` attribute — see
    // custom_listing_table()'s docblock. Only ever set by our own rendered
    // markup, but json_decode()'d defensively (empty array on anything
    // malformed) since it's still client-supplied input.
    $extra = [];
    if (!empty($_POST['listing_params'])) {
      $decoded = json_decode(base64_decode($_POST['listing_params']), true);
      if (is_array($decoded)) {
        $extra = $decoded;
      }
    }

    $request = [
      'start'        => intval($_POST['start'] ?? 0),
      'length'       => intval($_POST['length'] ?? 10),
      'search_value' => $_POST['search']['value'] ?? '',
      'order_field'  => $order_field,
      'order_dir'    => $order['dir'] ?? null,
      'extra'        => $extra,
    ];

    $result = ($listing['get_data_by'] ?? 'table') === 'function'
      ? query_custom_listing_function($listing['function_name'] ?? '', $fields, $actions, $request)
      : query_custom_listing_table($listing['table'] ?? '', $fields, $actions, $request);

    $head = array_map(fn($field) => $field['label'] ?? $field['name'], $fields);
    if (!empty($actions)) {
      $head[] = 'Ações';
    }

    return array_merge(['draw' => $draw, 'head' => $head], $result);
  },
  'permission_callback' => '__return_true',
]);

/**
 * Register a REST API route for reordering records (drag & drop).
 *
 * This code registers a REST API route named 'order-record'. Generic,
 * same two resolution paths as delete-record: either a CRUD id
 * (?crud=) or a forced table via an explicit permission id
 * (?permission_id=&table=). This is the "generic order-record" route
 * custom_listing_table() points a listing's `data-order-record` at by
 * default (a listing can override it per-listing via `order_endpoint`).
 *
 * Request:
 *   POST ids[]     — ids of the reordered rows, already in their new
 *                     sequence (top to bottom). Every id present gets
 *                     `{order_field} = posição (1-based)`.
 *   GET  crud       — CRUD id (Path 1) OR
 *   GET  permission_id + table (Path 2, mirrors delete-record)
 *   GET  order_field — nome da coluna de ordenação (default 'order_reg').
 *
 * NOTE: named `ids` (not `order`) to avoid colliding with DataTables'
 * own sort-by-column payload (`order[0][column]/[dir]`), which
 * get-custom-listing above already reads from the same param name for
 * a completely different purpose.
 */
register_rest_route('order-record', [
  'methods' => ['POST'],
  'callback' => function()
  {
    global $seg;

    $permission_id = $_GET['permission_id'] ?? null;
    $order_field   = $_GET['order_field']   ?? 'order_reg';
    $ids           = $_POST['ids']          ?? [];

    // --- Path 1: CRUD-based reordering (uses CRUD ID) ------------------
    if (isset($_GET['crud']))
    {
      $crud_id = (int) $_GET['crud'];

      // Same convention as delete-record's 'delete' mode, one per action.
      if (!load_permission($crud_id, 'order')) {
        return invalid_permission_response();
      }

      $crud  = get_result("SELECT table_crud FROM tb_cruds WHERE id = {$crud_id}");
      $table = $crud['table_crud'] ?? null;
    }
    // --- Path 2: explicit permission id + table ------------------------
    elseif (!empty($permission_id))
    {
      if (!load_permission($permission_id, 'custom')) {
        return invalid_permission_response();
      }

      $table = $_GET['table'] ?? null;
    }
    // --- No CRUD nor explicit permission id: forbidden ------------------
    else {
      return invalid_permission_response();
    }

    if (empty($table) || empty($ids) || !is_array($ids)) {
      return [
        'code'   => 'error',
        'detail' => [
          'type' => 'toast',
          'msg'  => alert_message('ER_TO_ORDER', 'toast'),
        ],
      ];
    }

    // order_field vem da querystring — confirma que é uma coluna de
    // verdade da tabela antes de usar (mesma checagem que
    // query_custom_listing_table() já faz para o order vindo do DataTables).
    if (!in_array($order_field, show_columns($table), true)) {
      return [
        'code'   => 'error',
        'detail' => [
          'type' => 'toast',
          'msg'  => alert_message('ER_TO_ORDER', 'toast'),
        ],
      ];
    }

    $ok = true;
    foreach (array_values($ids) as $position => $id)
    {
      $result = update($table, [
        'data'  => [ $order_field => $position + 1 ],
        'where' => where_equal_id($id),
      ]);

      // update() devolve o objeto de query_it(): checa o code de execução,
      // não affected_rows() — uma linha que já estava na posição certa não
      // muda valor (affected_rows = 0) e isso não é uma falha real.
      if (($result->code ?? 'error') !== 'success') {
        $ok = false;
      }
    }

    return [
      'code'   => $ok ? 'success' : 'error',
      'detail' => [
        'type' => 'toast',
        'msg'  => alert_message($ok ? 'SC_TO_ORDER' : 'ER_TO_ORDER', 'toast'),
      ],
    ];
  },
  'permission_callback' => '__return_true',
]);
