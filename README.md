# PyroSoft CMS
**by EUPHORIA SYSTEMS**

PyroSoft is a modular PHP-based CMS/framework designed to build medium-to-complex systems with high customization, strong separation of concerns, and update safety.

It follows a layered architecture inspired by platforms like WordPress, where **core logic is isolated from project-specific customizations**.

---

## Core principle (important)

> **You should only edit files inside `this-system/`.**
>
> Modifying core files (`ep-includes/`, `index.php`, `load.php`, etc.) is **not recommended** and may cause breaking changes during future updates.

---

## High-level architecture

```
index.php
 └── load.php
     ├── config.php
     ├── ep-includes/      (core system – DO NOT EDIT)
     └── this-system/      (your project – SAFE TO EDIT)
```

- **Core (`ep-includes/`)**
  Provides routing, CRUD engine, permissions, authentication, blocks, plugins system, REST API, CRON, and internal helpers.

- **Project layer (`this-system/`)**
  Contains all custom logic, templates, plugins, variables, and assets specific to your system.

---

## Directory overview

### Root
- `index.php` – main entry point (router)
- `load.php` – bootstrap loader
- `config.php` – environment and database config
- `cron.php` – CRON execution entry
- `.env` – environment variables
- `composer.json` – PHP dependencies
- `package.json`, `webpack.config.js` – front-end build

### `ep-includes/` (core – do not edit)
Contains the CMS engine and built-in features:
- Core functions (CRUD, users, permissions, pages, API, CRON)
- UI blocks and modules
- Feature packs (S3, inputs, plugins manager, sitemap, security, etc.)
- Compiled assets (`dist/`)

### `this-system/` (customization layer)
This is the **only folder intended for customization**.
- `variables.php` – project constants and defaults
- `functions.php` – project registrations and extensions
- `areas/` – application areas (e.g. `admin`, `app`)
- `plugins/` – custom project plugins

---

## Getting started

### 1. Database setup
Create your database using the provided SQL file:

```
db_pyrosoft.sql
```

After creating the database, set the credentials in the `.env` file.

### 2. Environment configuration

Create a `.env` file in the project root:

```ini
database.host = "localhost"
database.user = "root"
database.password = "password"
database.name = "pyrosoft"
```

### 3. Install dependencies and start development

From the project root, run:

```bash
composer install
yarn install
yarn start
```

This will install PHP and front-end dependencies and start Webpack in watch mode.

---

## Front-end build

For production builds:

```bash
yarn build
```

Build output:
```
dist/scripts/
dist/styles/
```

---

## Integrations & ecosystem

- PyroSoft is **easy to integrate with automation tools like n8n**.
- Native support for **webhooks** makes external integrations straightforward.
- A **dedicated area for chatbots and AI features is planned and coming soon**.

### UI stack
- By default, `this-system/` is integrated with **Bootstrap 5**.
- It is **easy to replace or create a new `this-system/` using Tailwind CSS** or any other UI framework.

---

## CRON

CRON jobs can be executed via `cron.php` or through internal routing handled by the core system.

---

## Customization rules (summary)

### ✅ Safe to edit
- `this-system/**`
- area templates and assets
- custom plugins

### ❌ Avoid editing
- `ep-includes/**`
- `index.php`, `load.php`, `config.php`

Prefer plugins or project-level extensions when deeper customization is required.

---

## License / ownership

PyroSoft is a proprietary system developed by **EUPHORIA SYSTEMS**.
# PyroSoft
