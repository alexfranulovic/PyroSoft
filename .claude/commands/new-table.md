---
description: Add a new database table to an existing plugin, mirrored across install.php and uninstall.php
argument-hint: <plugin-slug> <table-name> [column description]
---

Add a new table to an existing plugin at `this-system/plugins/<plugin-slug>/`.

Arguments: `$ARGUMENTS`
- Plugin slug: must already exist. If missing, ask.
- Table name: must be prefixed `tb_` (project/plugin tables never use the core's `ep_tb_` prefix). If the user gave a name without the prefix, add it.
- Column description: whatever the user described (fields, types, relations). If vague, ask for the columns before generating SQL — don't invent business fields.

Steps:

1. Read the target plugin's current `install.php` and `uninstall.php` to match its existing SQL style (quoting, `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`, index/FK conventions — see `pyrosales/install.php` for the fullest example: `created_at`/`updated_at` DATETIME columns, `INDEX idx_<table>_<col>` naming, `CONSTRAINT fk_<table>_<ref> FOREIGN KEY (...) REFERENCES ... ON DELETE ...`).
2. Append to `install.php`:
```php
$sql = "
CREATE TABLE <table-name> (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  -- columns here, based on what the user described
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
query_it($sql);
```
3. Append to `uninstall.php` (in the DROP TABLE section, before any option/permission cleanup, and in the reverse order of creation if there are foreign keys pointing at other new tables):
```php
query_it("DROP TABLE IF EXISTS <table-name>");
```
4. Add a label for the table to `index.php`'s `$GLOBALS['tables']` (or `$tables`, matching whatever the plugin already uses) so it shows up in the admin table list:
```php
$tables['<table-name>'] = '<human label>';
```

Rules:
- Never edit or drop an existing table's structure with this command — it only adds a brand-new table. For altering an existing table, do it explicitly and call out that the change isn't reversible via a simple mirrored uninstall (existing data in the column being altered may need a migration plan, not just an `ALTER`/reverse-`ALTER` pair).
- If the table has a foreign key to another `tb_*` table, verify that table exists first.
- The project supports both PostgreSQL and MariaDB/MySQL via the `DB_ENGINE` constant. Write plain, portable SQL by default (standard types, no engine-specific functions). If a column or constraint genuinely needs an engine-specific construct, branch on `DB_ENGINE` and build the two variants of the `$sql` string dynamically rather than hardcoding one engine's dialect.
- If the plugin has already been activated (check `install.php` isn't gated behind an `if` that's already run), remind the user that adding a `CREATE TABLE` to `install.php` alone doesn't create it in the live database — they need to re-run install (deactivate/reactivate in Plugin Manager, or run the new `query_it($sql)` once manually) since `install.php` isn't re-executed automatically after activation.
