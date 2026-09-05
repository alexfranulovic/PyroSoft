---
description: Audit an existing plugin against this project's structural conventions
argument-hint: <plugin-slug>
---

Audit `this-system/plugins/<plugin-slug>/` against the conventions in CLAUDE.md. Read every file in the plugin folder (including `src/` recursively), then report findings — do not fix anything unless the user explicitly asks afterward.

Check for:

1. **Required files present**: `info.json`, `index.php`, `api.php`, `cronjobs.php`, `install.php`, `uninstall.php`. Flag any missing (empty stub files are fine, missing files are not).
2. **`$seg` guard**: every top-level `.php` file in the plugin (not files inside `custom-pages/` that are page templates, which have their own context) must start with `if (!isset($seg)) exit;` as its first statement.
3. **Table prefix**: every `CREATE TABLE` in `install.php` uses `tb_` prefix, never `ep_tb_`.
4. **install/uninstall mirroring** — for everything created in `install.php`, confirm a matching removal exists in `uninstall.php`:
   - Every `CREATE TABLE <name>` → `DROP TABLE IF EXISTS <name>` in uninstall.
   - Every `ALTER TABLE ... ADD COLUMN` on a foreign table (e.g. `tb_users`) → a matching `DROP COLUMN IF EXISTS` in uninstall.
   - Every `update_option('<key>', ...)` → `delete_option('<key>')` in uninstall.
   - Every `update_permissions([...'slug' => '<slug>'...])` → `delete_permission([...'slug' => '<slug>'...])` in uninstall.
   - Every `manage_page_system([...'slug' => '<slug>'...], 'insert')` → a `delete_record([...'where_value' => '<slug>'...])` targeting `tb_pages` in uninstall.
   - Every `manage_crud_system([...'slug' => '<slug>'...], 'insert')` → a `delete_record(...)` targeting `tb_cruds` in uninstall.
   - Every menu group/item added via `manage_menu_items()` → a `delete_record(...)` targeting `tb_menus` in uninstall.
5. **`$GLOBALS['tables']` / `$tables` registration**: every table created in `install.php` should have a human-readable label registered in `index.php` (or wherever the plugin registers it).
6. **API routes** (`api.php`): each `register_rest_route()` call — confirm it has a `permission_callback` and, if it performs a write/mutation, that it validates its payload rather than trusting `$_POST`/`$_GET` directly into a query. Flag any raw SQL string interpolation of user input that isn't passed through `addslashes()`/an escaping helper (SQL injection risk) — note this as a finding rather than fixing it silently, since fixing may require knowing the intended validation rules. Also flag any manual `file_get_contents('php://input')` / `json_decode()` parsing inside a callback — the dispatcher already merges the payload into `$_POST` via `read_json_payload()` before the callback runs, so this is always redundant (legacy code, e.g. `pyrosales/api.php`'s `create-order`, still does this — still worth flagging even there).
7. **No core edits**: confirm nothing in the plugin folder reaches outside `this-system/` (e.g. no `require` pointing into `ep-includes/` internals beyond calling public helper functions, which is normal and fine).
8. **Business logic isolation**: flag REST callbacks in `api.php` containing substantial inline logic (more than ~10-15 lines of actual logic, not counting payload parsing) instead of delegating to a named function in `index.php`/`src/`.
9. **Language**: all identifiers, docblocks, and inline comments must be in English. User-facing strings (toast/alert text, admin labels, page content) may be in Brazilian Portuguese — that's expected, not a finding. Flag Portuguese variable/function names or comments.
10. **`$GLOBALS['x']` vs `global $x;`**: prefer `$GLOBALS['x']` for reading/writing globals inside functions; flag heavy use of `global $x;` in new-looking code (this is a style preference, low severity — don't flag every occurrence in old files, just note the pattern if the plugin is clearly newer/actively maintained).
11. **Reinvented system functions**: flag any hand-rolled logic that duplicates something core already exposes (payload parsing instead of `read_json_payload()`, manual toast/modal array construction instead of `alert_message()`, raw `mysqli_query`-style loops instead of `get_result()`/`get_results()`/`insert()`/`update()`).
12. **Payment plugins only** (skip for non-payment plugins): confirm `src/process-payment.php` defines `{provider}_{method}_process_payment(array $data, bool $debug = false): array` and `index.php` defines `{provider}_{method}_head()`, matching the `mercadopago`/`pagbank` contract that `pyrosales` depends on.
13. **SQL portability**: flag any `CREATE TABLE`/`ALTER TABLE`/query in `install.php`/`uninstall.php` or `src/` that uses an engine-specific function or syntax without branching on the `DB_ENGINE` constant.

Report format: a short list of findings grouped by severity (missing-mirror / missing-guard / reinvented-core-function are high; language/style/isolation nits are low), each with the file and what's wrong. If nothing is wrong, say so plainly — don't invent nitpicks to fill space.
