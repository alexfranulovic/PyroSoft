# conquiste.me — PyroSoft CMS

SaaS platform built on **PyroSoft CMS** (proprietary PHP framework by EUPHORIA SYSTEMS), WordPress-inspired: core is isolated from project customizations.

## Core rule

```
index.php → load.php → config.php
                     → ep-includes/   (core — edit here)
                     → this-system/   (project — edit here)
```

**Never edit `ep-includes/`, `index.php`, `load.php`, or `config.php`.** All business logic lives in `this-system/`, mostly under `this-system/plugins/`.

`this-system/` is fully customizable — swapping Bootstrap for Tailwind, adding Vue, or replacing the whole front-end stack is fine. What must stay fixed is the **folder structure** (`plugins/`, `areas/`, `blocks/`, `modules/`) and the contract each folder has with the core (file names, required guards, function naming). Don't invent a different folder for something that already has a home.

## Database

- Prefix `ep_tb_*` = core tables. Prefix `tb_*` = plugin/project tables.
- Access helpers: `query_it($sql)` (raw SQL), `get_result()` (single row), `get_results()` (multiple rows), `get_col()` (single value), `insert()` + `inserted_id()`, `update()`.
- Every table creation uses `CREATE TABLE ... ENGINE=InnoDB DEFAULT CHARSET=utf8mb4` inside `install.php`, mirrored by `DROP TABLE IF EXISTS` in `uninstall.php`.
- Config options via `update_option($key, $default, $autoload?)` / `delete_option($key)` — always in install/uninstall pairs.
- **Cross-engine SQL**: the project supports both PostgreSQL and MariaDB/MySQL, selected via the `DB_ENGINE` constant (from `.env`'s `database.engine`, see `config.php`). Write SQL that works on both by default. If you must use an engine-specific function or syntax, branch on `DB_ENGINE` and build the query string dynamically so it stays plug-and-play across engines — never hardcode one engine's dialect.

## Plugin structure (`this-system/plugins/<slug>/`)

| File | Role |
|---|---|
| `info.json` | Metadata (`name`, `description`, `version`, `author`) |
| `index.php` | Registers tables in `$GLOBALS['tables']`, defines functions, `require_once`s `src/` |
| `api.php` | REST routes via `register_rest_route()` |
| `cronjobs.php` | Plugin's CRON jobs (can be empty) |
| `install.php` | Creates tables, options, permissions, admin pages, menus |
| `uninstall.php` | Reverts **exactly** what `install.php` created |
| `src/` | Auxiliary logic (optional, `require_once`d from `index.php`) |

**Every plugin PHP file starts with:**
```php
<?php
if (!isset($seg)) exit;
```

A plugin only runs `install.php` when it's **activated** in Admin → Plugin Manager (`load_plugins('install', $plugin, true)`, tracked in the `activated_plugins` option). Creating the files isn't enough — it must be activated from the panel to actually test it.

### Payment plugins

Payment plugins (`mercadopago`, `pagbank`, ...) must follow the same folder/function-naming contract so `pyrosales` (the order engine) can call into any of them interchangeably:

- `src/process-payment.php` defines `{provider}_{method}_process_payment(array $data, bool $debug = false): array` — this is what `pyrosales`'s `create_order()` calls via `plugin_path("{provider}/src/process-payment.php")`.
- `index.php` defines `{provider}_{method}_head()` — loaded by `checkout_load_gateways_head()` to inject the gateway's checkout JS SDK.
- Common `src/` files across existing gateways: `checkout-fields.php`, `payment-methods.php` (or `payment.php`), `customer.php`, `cancel-refund.php`, `status.php`, `notifications.php`.
- A new payment plugin should mirror `mercadopago` or `pagbank`'s file layout, not invent a new one.

## REST routes (`api.php`)

The dispatcher (`rest_api_json_page()` in core) already runs `$_POST = read_json_payload();` **before** any route callback executes — this merges form-data and JSON-body payloads into `$_POST` automatically. **Never** manually re-parse `php://input` inside a route callback; just read `$_POST` (or `$_GET` for query params). If you need that merge behavior *outside* a REST route context, call the existing `read_json_payload()` helper — don't reimplement it.

```php
register_rest_route('route-name', [
  'methods'  => ['POST'],
  'callback' => function () {
    global $current_user;

    $permission = load_permission('permission-slug', 'custom');
    if (!$permission) {
      return invalid_permission_response();
    }

    // $_POST is already populated (form-data or JSON body) by the dispatcher.
    $product_id = $_POST['product_id'] ?? null;

    // ... logic ...

    return ['code' => 'success'];
  },
  'permission_callback' => '__return_true',
]);
```

**Standard API return shape** — whenever a route returns something meant to be shown to the user, use `alert_message()` (resolves a key from `$GLOBALS['alerts']`, or renders an inline array) instead of hand-building the message array:
```php
return [
  'code'   => 'success', // or 'error'
  'detail' => [
    'type' => 'toast', // or 'modal'
    'msg'  => alert_message($msg, 'toast'),
  ],
];
```

## Email templates

Templates render inside `email_default_layout()`'s wrapper (branding header + shared CSS: `.btn`, `.align-itens-center`, etc.) and are resolved by `resolve_template_path()` from a logical name:

| Template lives in | Reference as |
|---|---|
| `this-system/emails/{name}.php` | `'template' => '{name}'` |
| `this-system/plugins/{slug}/emails/{name}.php` | `'template' => 'plugin/{slug}/{name}'` |
| `ep-includes/features/{feature}/emails/{name}.php` | `'template' => 'feature/{feature}/{name}'` |

`this-system/emails/{name}.php` always wins as an override, whatever form you referenced — this is the sanctioned way to override a plugin/feature's default email without touching its folder.

**Known core bug**: the `feature/` lookup in `ep-includes/core/email-functions.php` has a path typo (`ep-incldues` instead of `ep-includes`), so a feature-owned template placed at its real location never actually resolves. Since `ep-includes/` can't be edited, ship feature-related email templates through `this-system/emails/{name}.php` instead of relying on the `feature/{feature}/{name}` path.

**Template file contract:**
```php
<?php
if (!isset($seg)) exit;

$body = "
<p>Olá, <strong>{$params['first_name']}</strong>,</p>
<p>...</p>
<div class='align-itens-center'>
    <a class='btn' href='{$params['url']}'>Ver detalhes</a>
</div>";

$email_data = [
    'to'        => $payload['to'],
    'subject'   => 'Assunto do e-mail',
    'body'      => $body,
    'signature' => ['humanized' => false],
];
```
- `$params` — the caller's `template_params`. `$payload` — the full args passed to `build_email_message()` (has `to`, `template_params`); templates just forward `$payload['to']`.
- Must set `$email_data` with `to`, `subject`, `body`, and optionally `signature`: `['humanized' => false]` for the system signature, or `['humanized' => true, 'image' => ..., 'name' => ..., 'task' => ..., 'contact' => ...]` for a person's.
- No fixed filename convention — `this-system/emails/` uses kebab-case (`password-link-recover.php`), `pyrosales` uses snake_case (`customer_subscription_renewed.php`); match whatever the target folder already uses.

**Sending:** always queue, never call `send_email()` directly — `queue_message()` writes to `tb_queue_messages` and the cron (`process_queue()`) sends it with retry:
```php
queue_message([
    'template'        => 'plugin/{slug}/{name}',
    'provider'        => 'brevo',
    'to'              => [['name' => $name, 'email' => $email]],
    'template_params' => ['first_name' => $name, 'url' => $link],
]);
```

## Areas (`this-system/areas/`)

- `app/` — public area (home, checkout, receipt, login). `admin/` — admin panel.
- Each area has `common.php`, `include/functions.php`, `include/menu.php`, `include/head.php`.
- Default UI: **Bootstrap 5**. Reusable components via `block('type', [...])`, icons via `icon('fas fa-...')`.

## Blocks (`this-system/blocks/`)

- `cmps/` — small standalone components, one function each (e.g. `badge()`, `modal()`, `toast()`), called directly via `block('name', [...])`.
- `modules/` — composite content blocks used on pages/CRUD builder. Each is a thin dispatcher (e.g. `hero()`) that resolves a `variation` key and delegates to it via `variation($name, $Attr)`.
- `variations/` — concrete implementations of a module for a given visual style, named `{module}_{variation}` (e.g. `hero_default`, `hero_blur` implement `hero`).
- New UI building blocks go in the matching one of these three folders — don't add ad-hoc rendering functions elsewhere.

## Front-end

- `yarn start` — Webpack watch (dev). `yarn build` — production build (`dist/scripts/`, `dist/styles/`).
- Sass + Bootstrap 5 by default (swappable, see above). JS deps via Yarn, PHP deps via Composer.
- **CSS**: write clean, purposeful CSS. Avoid reaching for utility classes from the CSS framework (Bootstrap's `d-flex`, `mt-3`, etc.) as a default habit — prefer the CMS's own classes/blocks first, and when a framework class is genuinely the simplest option, use as few of them as possible rather than stacking many per element.

## Dependencies

Think twice before adding a JS library or PHP SDK. Check whether the system already exposes a helper/feature that does the job, and whether the need is big enough to justify the dependency (don't bring in a cannon to kill an ant if a simpler hand-rolled bit of code — the "sandal" — does the job just as well). When genuinely unsure, present the options (add the dependency vs. build it small) to the developer before installing anything.

## Code conventions

- **Code and comments are always in English** — identifiers, function names, docblocks, inline comments. Only user-facing strings (toasts, labels, page content, admin UI copy) are in Brazilian Portuguese (pt-BR), matching what the surrounding file already does for that content.
- **Prefer existing system functions over new logic.** Before writing a helper, check whether `ep-includes/` already exposes one (e.g. `read_json_payload()`, `alert_message()`, `get_result()`/`get_results()`, `manage_*()` functions). Think twice before creating a new helper — duplicating logic that already exists as a core function is a bug waiting to diverge.
- Prefer `$GLOBALS['variable']` over `global $variable;` when reading/writing a global inside a function.
- No premature abstractions — simple plugins (`carts`, `products`) have nearly empty `index.php`/`api.php`/`cronjobs.php` (just the `$seg` guard); don't add structure the plugin doesn't use.
- `install.php`/`uninstall.php` must mirror each other 1:1: every table, option, permission, page, or menu item created in install has a corresponding removal in uninstall.
- Keep the folder/naming conventions consistent across `features`, `inputs`, `blocks`, and `plugins` — each has its own established shape (see above for `blocks/` and `plugins/`); don't reorganize or introduce a parallel structure for the same concern.

## Available commands

- `/new-plugin` — scaffold a new plugin
- `/new-endpoint` — add a REST route to an existing plugin
- `/new-table` — add a new table (mirrored install + uninstall)
- `/new-crud` — scaffold a full admin CRUD from a field list
- `/plugin-audit` — audit an existing plugin against these conventions
