---
description: Scaffold a full admin CRUD (list/create/edit) for a table, from a field list and context
argument-hint: <plugin-slug> <table-name> <what it manages> [fields...]
---

Scaffold an admin CRUD definition for `<table-name>` inside `this-system/plugins/<plugin-slug>/install.php` (with the matching teardown in `uninstall.php`), using the core `page-crud-management-system` feature (`manage_crud_system()`, `manage_page_system()`, `manage_page_modules()`).

Arguments: `$ARGUMENTS`
- Plugin slug: must already exist. If missing, ask.
- Table name: the `tb_*` table this CRUD manages. Must already exist (created by `/new-table` or already in `install.php`) — this command does not create tables.
- What it manages / fields: get the actual field list, types, and validation from the user. **Do not invent business fields.** If the user only names the table, read its `CREATE TABLE` in `install.php` and propose a field for each column, but confirm before generating.

**Before writing anything**, this system's CRUD engine is genuinely more involved than a single function call, and it's core-owned — read the current source of `ep-includes/features/page-crud-management-system/crud.php` (function `manage_crud_system()`) and `ep-includes/features/page-crud-management-system/page.php` (function `manage_page_system()`, and how a CRUD gets attached to a page via `manage_page_modules()`) to confirm today's exact signature and required fields before generating code — don't rely purely on the pattern below, since this is a core feature that may have evolved.

What's confirmed from existing working code in this repo (`pyrosales/install.php`, `plans-subscriptions/install.php`):

- There are two shapes of CRUD in use:
  1. **Settings-style CRUD** (`type_crud => 'update'`, `related_to => 'system_info'` or a table, no `table_crud`) — a single form attached to an existing page via `manage_page_modules()`. This is the simpler, proven pattern — use it when the ask is "a settings/config screen", not a full list+create+edit of table rows.
  2. **Table-backed master CRUD** (`type_crud => 'master'`, `table_crud => '<table-name>'`, `foreign_key => '<id-column>'`) with separate child CRUD pieces (`type_crud` = `list` / `insert` / `update` / `view`, each pointing back to the master via `crud_id`) and a `pages_list` JSON on the master mapping each piece to an admin page (`{"list_pg": "<page_id>", "insert": {"mode": "page", "page": "<page_id>", "piece": "<piece_crud_id>"}, "update": {...}, "view": {...}}`). This is what the admin's own CRUD builder produces. Building this by hand from `install.php` requires: (a) creating the admin pages first, (b) creating the child CRUD pieces (getting each `crud_id` back from `manage_crud_system(..., 'insert')`), (c) creating the master with those piece/page IDs in `pages_list`, in that dependency order.

Ask the user which shape fits before generating: a **settings-style single form** (fast, low risk, proven in this codebase) or a **full table CRUD** (list/create/edit/view of rows — more moving pieces, cross-check `crud.php` carefully, and tell the user to verify the result by opening the CRUD in Admin before relying on it).

Field types (`Fields[]` entries), confirmed from `pyrosales/install.php`'s working settings CRUD — reuse these `type_field` values and don't invent new ones:
- `divider` — section heading (`title`, `depth: 0`)
- `basic` — plain text/number input (`type: 'text'|'number'`, `name`, `label`, `Required`, `size`)
- `textarea` — multiline text (`attributes: 'rows:(N);'`)
- `selection_type` — radio / switch / search select (`type: 'radio'|'switch'|'search'`, `variation`, `Options: [['value'=>..,'display'=>..]]` or `options_resolver: 'some_function()'` for dynamic options, e.g. `get_pages_for_select('id')`)
- `submit_button` — form submit (`name: 'process-form'`, `Value`, `class: 'btn btn-st'`)

Rules:
- Every table/permission/page/CRUD piece this command creates in `install.php` must have a matching `delete_record()`/`delete_permission()`/`delete_option()` call added to `uninstall.php` (see `/plugin-audit`'s checklist — this command's output must pass it).
- Gate the CRUD's use behind a permission via `feature('permissions-management')` + `update_permissions()`, following the `pyrosales`/`order-manager` example, unless the user says the CRUD should be open to all logged-in users.
- Don't guess at `manage_page_modules()`'s exact parameters either — re-read its usage in `pyrosales/install.php` (attaching to `system-settings`) as the reference, and adapt the target page slug to whatever the user wants this CRUD attached under (or a new admin page created via `manage_page_system()` if it needs its own page).
- After generating, tell the user this needs the plugin (re-)activated in Admin → Plugin Manager to actually run `install.php`, and to open the resulting CRUD in the admin panel to confirm it renders and saves correctly — this feature has enough moving parts that a dry read-through isn't a substitute for checking it in the panel.
