---
description: Scaffold a new PyroSoft plugin under this-system/plugins/
argument-hint: <slug> ["Display Name"] ["short description"]
---

Create a new plugin skeleton at `this-system/plugins/<slug>/` following this project's conventions (see CLAUDE.md).

Arguments: `$ARGUMENTS`
- First token: plugin slug (kebab-case, e.g. `wishlist`). Required — ask the user if missing.
- Remaining tokens: display name and/or description. If not given, derive a Title Case display name from the slug and leave description generic.

Steps:

1. Verify `this-system/plugins/<slug>/` does not already exist. If it does, stop and tell the user.
2. Create these files, exactly as templated below (replace `{{slug}}`, `{{Name}}`, `{{description}}`, `{{author}}` — use the git user name or "Alex Franulovic" if unknown):

`this-system/plugins/{{slug}}/info.json`
```json
{
    "name": "{{Name}}",
    "description": "{{description}}",
    "version": "1.0.0",
    "plugin_uri": "",
    "author": "{{author}}"
}
```

`this-system/plugins/{{slug}}/index.php`
```php
<?php
if (!isset($seg)) exit;

global $tables;
// $tables['tb_{{slug_snake}}'] = '{{Name}}';
```

`this-system/plugins/{{slug}}/api.php`
```php
<?php
if (!isset($seg)) exit;

// register_rest_route('{{slug}}-action', [
//   'methods'  => ['POST'],
//   'callback' => function () {
//     return ['code' => 'success'];
//   },
//   'permission_callback' => '__return_true',
// ]);
```

`this-system/plugins/{{slug}}/cronjobs.php`
```php
<?php
if (!isset($seg)) exit;
```

`this-system/plugins/{{slug}}/install.php`
```php
<?php
if (!isset($seg)) exit;

/**
 * {{Name}} Plugin – Installation / Bootstrap Script
 */

// $sql = "
// CREATE TABLE tb_{{slug_snake}} (
//   id BIGINT AUTO_INCREMENT PRIMARY KEY,
//   created_at DATETIME NOT NULL,
//   updated_at DATETIME NOT NULL
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
// ";
// query_it($sql);
```

`this-system/plugins/{{slug}}/uninstall.php`
```php
<?php
if (!isset($seg)) exit;

/**
 * {{Name}} Plugin – Uninstallation Script
 */

// query_it("DROP TABLE IF EXISTS tb_{{slug_snake}}");
```

3. Do NOT create a `src/` folder or `assets/` folder unless the user asked for logic beyond a single file — keep it as empty as the simplest real plugins in this repo (`carts`, `facial-input`).
4. After creating the files, tell the user: the plugin won't run until it's activated from **Admin → Plugin Manager** (this triggers `install.php`). Files on disk alone do nothing.
5. Do not register REST routes, tables, or admin pages with real content unless the user described what the plugin should actually do — this command only produces the skeleton.
