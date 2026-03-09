<?php
if (!isset($seg)) exit;

/**
 * PyroSales Plugin – Installation / Bootstrap Script
 *
 * This installer provisions the PyroSales (Orders) module by:
 * - Creating all required database tables (orders, items, payments, coupons, fees)
 * - Initializing plugin options (API status, sandbox mode, payment methods)
 * - Registering a custom permission ("create-order")
 * - Registering an admin CRUD settings panel and attaching it to System Settings
 * - Creating admin pages used by the plugin (List orders, Order manager)
 * - Adding menu entries under the Admin main menu
 *
 * IMPORTANT:
 * - This script is destructive only in the sense of creating structures; it does not drop tables.
 * - It must run inside the system context (`$seg` guard).
 * - It assumes the availability of framework helpers like `query_it()`, `feature()`, and all
 *   `manage_*` functions used below.
 *
 * ---------------------------------------------------------------------
 * DATABASE SCHEMA
 * ---------------------------------------------------------------------
 *
 * Tables created:
 * - tb_orders          : One row per order (customer snapshot + totals + audit)
 * - tb_order_items     : One row per item inside an order (product/plan/one-off)
 * - tb_order_payments  : One row per payment attempt/record for an order
 * - tb_order_coupons   : Applied coupons snapshot per order (unique per code)
 * - tb_order_fees      : Extra fees / adjustments applied to an order
 */

/**
 * DATABASE SCHEMA
 */
$sql = "
CREATE TABLE tb_orders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  status_id INT NOT NULL,
  -- Order classification
  order_type ENUM('product','plan','one_off','mixed')
    NOT NULL DEFAULT 'product',
  -- Customer Snapshot
  customer_first_name VARCHAR(255) NOT NULL,
  customer_last_name VARCHAR(255) NOT NULL,
  customer_email VARCHAR(190) NOT NULL,
  customer_phone VARCHAR(40) NULL,
  customer_document_type ENUM('CPF','CNPJ','PASSPORT','OTHER') NULL,
  customer_document_number VARCHAR(32) NULL,
  -- Address snapshot (billing/shipping merged for simplicity)
  requires_address TINYINT(1) NOT NULL DEFAULT 0,
  address JSON NULL,
  -- Request / Environment Snapshot
  ip_address VARCHAR(45) NULL,           -- IPv4 / IPv6
  user_agent VARCHAR(500) NULL,
  referrer VARCHAR(1000) NULL,
  origin VARCHAR(500) NULL,
  device_type ENUM('desktop','mobile','tablet','bot','unknown') NULL,
  -- Financial Snapshot
  currency CHAR(3) NOT NULL DEFAULT 'BRL',
  subtotal_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  fee_amount      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  shipping_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  tax_amount      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total_amount    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  -- Marketplace / affiliate commission snapshot
  commission_amount DECIMAL(10,2) NULL,
  vendor_id BIGINT UNSIGNED NULL,
  notes TEXT NULL,
  -- Audit
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  -- Indexes
  INDEX idx_orders_user (user_id),
  INDEX idx_orders_vendor (vendor_id),
  INDEX idx_orders_status (status_id),
  INDEX idx_orders_email (customer_email),
  INDEX idx_orders_created (created_at)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
query_it($sql);


$sql = "
CREATE TABLE tb_order_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NULL,
  plan_id BIGINT UNSIGNED NULL,
  item_type ENUM('product','plan','one_off') NOT NULL,
  item_name VARCHAR(255) NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  regular_unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  line_discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  -- Snapshot totals per line (safe if you freeze them)
  line_subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  line_total    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  meta_json JSON NULL,
  activation_function VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,

  CONSTRAINT fk_order_items_order
    FOREIGN KEY (order_id) REFERENCES tb_orders(id)
    ON DELETE CASCADE,

  INDEX idx_items_order (order_id),
  INDEX idx_items_product (product_id),
  INDEX idx_items_plan (plan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
query_it($sql);



$sql = "
CREATE TABLE tb_order_payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  status_id VARCHAR(30) DEFAULT NULL,
  method VARCHAR(32) NOT NULL,
  provider VARCHAR(32) NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'BRL',
  amount DECIMAL(10,2) NOT NULL,        -- gross charged
  gateway_fee DECIMAL(10,2) NULL,
  net_amount DECIMAL(10,2) NULL,
  installments TINYINT UNSIGNED NULL,
  installment_amount DECIMAL(10,2) NULL,
  payment_link VARCHAR(500) NULL,
  raw_response_json JSON NULL,
  idempotency_key VARCHAR(100) NULL,
  provider_reference VARCHAR(150) NULL,
  provider_payment_id VARCHAR(220) NULL,
  provider_type_code INT NULL,
  provider_status VARCHAR(50) NULL,
  paid_at DATETIME NULL,
  expires_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,

  CONSTRAINT fk_payments_order
    FOREIGN KEY (order_id) REFERENCES tb_orders(id)
    ON DELETE CASCADE,

  INDEX idx_payments_order (order_id),
  INDEX idx_payments_status (status_id),
  INDEX idx_payments_provider_pid (provider, provider_payment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
query_it($sql);



$sql = "
CREATE TABLE tb_order_coupons (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  code VARCHAR(64) NOT NULL,
  -- Snapshot of coupon configuration at the time it was applied
  discount_type ENUM('percent','fixed_cart','fixed_item') NOT NULL DEFAULT 'fixed_cart',
  discount_value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  -- Amount effectively applied to this order
  discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  meta_json JSON NULL,
  created_at DATETIME NOT NULL,

  CONSTRAINT fk_order_coupons_order
    FOREIGN KEY (order_id) REFERENCES tb_orders(id)
    ON DELETE CASCADE,

  UNIQUE KEY uq_order_coupon (order_id, code),
  INDEX idx_coupon_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
query_it($sql);



$sql = "
CREATE TABLE tb_order_fees (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  -- Can be negative (manual discount) or positive (extra charge)
  amount DECIMAL(10,2) NOT NULL,
  tax_status ENUM('none','taxable') NOT NULL DEFAULT 'none',
  meta_json JSON NULL,
  created_at DATETIME NOT NULL,

  CONSTRAINT fk_order_fees_order
    FOREIGN KEY (order_id) REFERENCES tb_orders(id)
    ON DELETE CASCADE,

  INDEX idx_fee_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
query_it($sql);


/**
 * PLUGIN OPTIONS (DEFAULTS)
 *
 * Initializes configuration options used by PyroSales.
 *
 * - `pyrosales_api_status`     : Whether the API integration is active.
 * - `pyrosales_is_sandbox`     : Whether gateway calls should use sandbox mode.
 * - `active_payment_methods`  : List of enabled payment gateways/methods.
 *
 * NOTE:
 * If `active_payment_methods` is meant to be plugin-scoped, consider prefixing
 * it (e.g., `pyrosales_active_payment_methods`) to avoid collisions with other plugins.
 */
update_option('default_currency', 'BRL', 1);
update_option('pyrosales_api_status', '0');
update_option('pyrosales_is_sandbox', '1', 1);
update_option('active_payment_methods', [], 1);
update_option('fee_mode', 'merchant');
update_option('max_interest_free_installments', '1');
update_option('surcharge_percent', 0.05);
update_option('surcharge_fixed', 1);

/**
 * PERMISSIONS
 *
 * Registers a custom permission used by the plugin to authorize order creation.
 * This requires the permissions feature to be loaded.
 */
feature('permissions-management');

$payload = [
  'allowed'         => [1],
  'name'            => 'Order manager',
  'slug'            => 'order-manager',
  'permission_type' => 'only_these',
  'type'            => 'permission',
];
update_permissions($payload, false);

/**
 * ADMIN SETTINGS CRUD (System Settings integration)
 *
 * Creates a CRUD definition (`pyrosales-settings`) linked to `general-settings`.
 * The form uses the built-in API `form-processor` and renders as tabs.
 *
 * Fields:
 * - API status (radio)
 * - Sandbox toggle (radio)
 * - Active payment methods (switch; options resolved by `format_payment_gateways()`)
 */
feature('page-crud-management-system');

$crud = [
  'crud_id' => get_crud_id_by_slug('general-settings'),
  'piece_name' => 'PyroSales settings',
  'slug' => 'pyrosales-settings',
  'type_crud' => 'update',
  'status_id' => 1,
  'login_required' => 1,
  'related_to' => 'system_info',

  'form_settings' => [
    'without_reload' => 1,
    'view_mode' => 'tabs_form',
  ],

  'form_action' => [
    'type' => 'api',
    'action' => 'form-processor'
  ],

  'Fields' => [
    [
      'depth' => 0,
      'type_field' => 'divider',
      'title' => 'PyroSales',
    ],
    [
      'depth' => 1,
      'type_field' => 'selection_type',
      'label' => 'PyroSales API status',
      'name' => 'pyrosales_api_status',
      'type' => 'radio',
      'variation' => 'inline',
      'Required' => 1,
      'status_id' => 1,
      'Options' => [
        [ 'value' => 'active', 'display' => 'Active' ],
        [ 'value' => 0,        'display' => 'Inactive' ],
      ]
    ],
    [
      'depth' => 1,
      'type_field' => 'selection_type',
      'label' => 'Is sandbox?',
      'name' => 'pyrosales_is_sandbox',
      'type' => 'radio',
      'variation' => 'inline',
      'Required' => 1,
      'status_id' => 1,
      'Options' => [
        [ 'value' => 1, 'display' => 'Yes' ],
        [ 'value' => 0, 'display' => 'No'  ],
      ]
    ],
    [
      'depth' => 1,
      'type_field' => 'selection_type',
      'label' => 'Active payment methods',
      'name' => 'active_payment_methods[]',
      'type' => 'switch',
      'variation' => 'inline',
      'Required' => 1,
      'status_id' => 1,
      'options_resolver' => 'format_payment_gateways()'
    ],
    [
      'depth' => 1,
      'type_field' => 'basic',
      'name' => "default_currency",
      'label' => 'Default currency',
      'Required' => true,
    ],
    [
      'depth' => 0,
      'type_field' => 'divider',
      'title' => 'Fees',
    ],
    [
      'depth' => 1,
      'type_field' => 'basic',
      'size' => 'col-6 col-md-3',
      'type' => 'number',
      'name' => "surcharge_fixed",
      'label' => 'Surcharge fixed',
      'Required' => true,
    ],
    [
      'depth' => 1,
      'type_field' => 'basic',
      'size' => 'col-6 col-md-3',
      'type' => 'number',
      'name' => "surcharge_percent",
      'label' => 'Surcharge percent',
      'attachment' => [
        'append' => '%'
      ],
      'Required' => true,
    ],
    [
      'depth' => 1,
      'type_field' => 'basic',
      'type' => 'number',
      'name' => "max_interest_free_installments",
      'label' => 'Max interest free installments',
      'Required' => true,
    ],
    [
      'depth' => 1,
      'type_field' => 'selection_type',
      'label' => 'Fee mode',
      'name' => 'fee_mode',
      'type' => 'radio',
      'variation' => 'inline',
      'Required' => 1,
      'status_id' => 1,
      'Options' => [
        [ 'value' => 'merchant' ],
        [ 'value' => 'customer'  ],
        [ 'value' => 'split'  ],
      ],
      'Required' => true,
    ],
    [
      'depth' => 0,
      'type_field' => 'submit_button',
      'name' => 'process-form',
      'old_name' => 'process-form',
      'Value' => 'Enviar',
      'class' => 'btn btn-st',
      'allow_schedule' => 1,
    ]
  ],
];

$crud = manage_crud_system($crud, 'insert');

/**
 * Attaches the newly created settings CRUD to the `system-settings` page modules.
 *
 * Notes:
 * - `from => out` indicates this update is executed from outside the page editor.
 * - `$debug` must exist in the installer context; otherwise it should be set/removed.
 */
manage_page_modules([
  'page_id' => get_page_id_by_slug('system-settings'),
  'mode' => 'update',
  'from' => 'out',
  'Modules' => [
    [
      'TypeModule' => 'crud',
      'crud_id' => $crud['crud_id'],
      'status_id' => 1,
    ]
  ],
], $debug);

/**
 * ADMIN PAGES
 *
 * Registers two admin pages (restricted to allowed users):
 * - list-orders    : list view of orders
 * - order-manager  : create/edit/manage a specific order
 */
$page = [
  'title' => 'List orders',
  'slug' => 'list-orders',
  'page_type' => 'not_essential',
  'status_page_id' => 1,
  'page_area' => 'admin',
  'permission_type' => 'only_these',
  'allowed' => [1],
  'page_template' => PLUGINS_PATH . '/pyrosales/custom-pages/list-orders.php',
];
manage_page_system($page, 'insert');

$page = [
  'title' => 'Order manager',
  'slug' => 'order-manager',
  'page_type' => 'not_essential',
  'status_page_id' => 1,
  'page_area' => 'admin',
  'permission_type' => 'only_these',
  'allowed' => [1],
  'page_template' => PLUGINS_PATH . '/pyrosales/custom-pages/order-manager.php',
];
manage_page_system($page, 'insert');

/**
 * ADMIN MENU
 *
 * Adds a "Shop" group and a "List orders" item under the admin main menu.
 *
 * Notes:
 * - `position` is used to force ordering within the menu.
 * - `depth` defines hierarchy (0 = group, 1 = child item).
 * - The "Shop" group is created with slug `pyrosales`.
 */
feature('menu-management');

manage_menu_items([
  'menu_id' => get_menu_id_by_slug('admin-main-menu'),
  'mode' => 'update',
  'from' => 'out',
  'menu_order' => [
    [
      'position' => 15,
      'depth' => '0',
      'type' => 'groups',
      'title' => 'Shop',
      'slug' => 'pyrosales',
    ],
    [
      'position' => 16,
      'depth' => '1',
      'title' => 'List orders',
      'icon' => 'fas fa-shopping-bag',
      'which_users' => 'logged_in',
      'style' => 'generic',
      'page_id' => get_page_id_by_slug('list-orders'),
      'type' => 'page',
      'slug' => 'pyrosales',
    ],
  ],
], $debug);
