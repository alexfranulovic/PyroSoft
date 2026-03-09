<?php
if (!isset($seg)) exit;

/**
 * PyroSales Plugin – Uninstallation Script
 *
 * This script is executed during the complete removal of the PyroSales
 * (Orders Management) plugin.
 *
 * Responsibilities:
 * - Drop all plugin-specific database tables.
 * - Remove stored configuration options.
 * - Delete custom permissions created by the plugin.
 * - Remove related pages, CRUD definitions, and menu entries.
 *
 * IMPORTANT:
 * This operation is destructive and irreversible.
 * All order-related data will be permanently deleted.
 *
 * Execution context:
 * - Must run inside the system environment (`$seg` guard required).
 * - Assumes helper functions like `query_it()`, `delete_option()`,
 *   `delete_permission()`, and `delete_record()` are available.
 *
 * DATABASE CLEANUP
 *
 * Drops all tables related to the order system.
 * Uses `IF EXISTS` to avoid fatal errors if tables were already removed.
 */

query_it("DROP TABLE IF EXISTS tb_orders");
query_it("DROP TABLE IF EXISTS tb_order_items");
query_it("DROP TABLE IF EXISTS tb_order_payments");
query_it("DROP TABLE IF EXISTS tb_order_coupons");
query_it("DROP TABLE IF EXISTS tb_order_fees");

/**
 * OPTIONS CLEANUP
 *
 * Removes configuration flags stored in the options table.
 * These control API mode, sandbox status, and active payment methods.
 */

delete_option('default_currency');
delete_option('pyrosales_api_status');
delete_option('pyrosales_is_sandbox');
delete_option('active_payment_methods');
delete_option('fee_mode');
delete_option('max_interest_free_installments');
delete_option('surcharge_percent');
delete_option('surcharge_fixed');

/**
 * PERMISSIONS CLEANUP
 *
 * Ensures the permissions management feature is loaded before deletion.
 * Then removes the custom permission created by the plugin.
 */

feature('permissions-management');

delete_permission([
    'type' => 'permission',
    'slug' => 'order-manager',
]);

/**
 * CMS STRUCTURE CLEANUP
 *
 * Removes pages, CRUD definitions, and menu entries created by the plugin.
 *
 * `tables_to_action => '-f'` indicates forced cascade deletion
 * (removes related records linked by the foreign key).
 */

// Remove "List Orders" page
delete_record([
    'table'           => 'tb_pages',
    'foreign_key'     => 'page_id',
    'where_field'     => 'slug',
    'where_value'     => 'list-orders',
    'tables_to_action'=> '-f',
]);

// Remove "Order Manager" page
delete_record([
    'table'           => 'tb_pages',
    'foreign_key'     => 'page_id',
    'where_field'     => 'slug',
    'where_value'     => 'order-manager',
    'tables_to_action'=> '-f',
]);

// Remove PyroSales settings CRUD
delete_record([
    'table'           => 'tb_cruds',
    'foreign_key'     => 'crud_id',
    'where_field'     => 'slug',
    'where_value'     => 'pyrosales-settings',
    'tables_to_action'=> '-f',
]);

// Remove PyroSales menu entry
delete_record([
    'table'           => 'tb_menus',
    'foreign_key'     => 'menu_id',
    'where_field'     => 'slug',
    'where_value'     => 'pyrosales',
    'tables_to_action'=> '-f',
]);
