---
description: Add a REST route to an existing plugin's api.php following this project's standard pattern
argument-hint: <plugin-slug> <route-name> [methods, e.g. GET,POST] [permission-slug]
---

Add a new `register_rest_route()` block to `this-system/plugins/<plugin-slug>/api.php`.

Arguments: `$ARGUMENTS`
- Plugin slug: must already exist under `this-system/plugins/`. If missing or the plugin doesn't exist, stop and ask.
- Route name: the string passed to `register_rest_route()`. If missing, ask.
- HTTP methods: default to `['POST']` if not given.
- Permission slug: optional. If given, gate the route with `load_permission('<slug>', 'custom')`; if not given, use `'permission_callback' => '__return_true'` and no internal permission check (matches how most existing routes in this codebase work, e.g. `pyrosales`'s `create-order`).

**Important**: the core dispatcher (`rest_api_json_page()`) already runs `$_POST = read_json_payload();` before any callback executes, merging form-data and JSON-body payloads into `$_POST` automatically. Never add manual `file_get_contents('php://input')` / `json_decode()` parsing inside the callback — just read `$_POST`. (Older code in this repo, e.g. `pyrosales/api.php`'s `create-order` route, still has that redundant manual parsing; don't copy that part of its style — it's legacy, not the pattern to follow.)

Before writing, read the target plugin's existing `api.php` to match local style for everything else (how it structures responses, whether it dispatches on `action`/`$_POST` keys like `carts/api.php`, etc.). If the file is empty, use this default shape:

```php
register_rest_route('<route-name>', [
  'methods'  => [<methods>],
  'callback' => function () {
    global $current_user;

    // $permission = load_permission('<permission-slug>', 'custom');
    // if (!$permission) {
    //   return invalid_permission_response();
    // }

    // $_POST is already populated (form-data or JSON body) by the dispatcher.
    $example = $_POST['example'] ?? null;

    // ... business logic here, or call a function defined in index.php / src/ ...

    return ['code' => 'success'];
  },
  'permission_callback' => '__return_true',
]);
```

When the route needs to return something to the user (not just an internal ok/fail), use the standard shape with `alert_message()` instead of a hand-built message array:
```php
return [
  'code'   => 'error',
  'detail' => [
    'type' => 'toast',
    'msg'  => alert_message($msg, 'toast'),
  ],
];
```

Rules:
- Never put real business logic inline in the callback beyond a few lines — if the operation is non-trivial, add a named function in `index.php` or `src/` and call it from the callback (this is the pattern `pyrosales` and `carts` both use: `create_order()`, `cart_add()`, etc.).
- Append the new block at the end of the existing `api.php` file; don't reorder or touch existing routes.
- If the plugin's `api.php` currently only has the `$seg` guard, add a blank line after it before the new route.
- Do not invent a permission slug — if the user didn't name one and the route needs auth, ask which permission to use rather than guessing.
- Before adding any inline logic, check whether an existing core function already does it (e.g. `get_result()`, `insert()`, `update()`, `delete_record()`) — don't reinvent something the system already exposes.
