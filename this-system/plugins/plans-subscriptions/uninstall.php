<?php
if (!isset($seg)) exit;

query_it("
ALTER TABLE `tb_user_role_assignments`
    DROP FOREIGN KEY `fk_tb_user_role_assignments_plan_id`,
    DROP INDEX `uq_tb_user_role_assignments_user_plan`,
    DROP INDEX `idx_tb_user_role_assignments_plan_id`,
    DROP COLUMN `plan_id`");
query_it("DROP TABLE IF EXISTS tb_plan_proposal_lock");
query_it("DROP TABLE IF EXISTS tb_plan_roles");
query_it("DROP TABLE IF EXISTS tb_plan_user_subscriptions");
query_it("DROP TABLE IF EXISTS tb_plans");

/**
 * OPTIONS CLEANUP
 *
 * Removes configuration flags stored in the options table.
 */

delete_option('subscriptions_business_model');

feature('permissions-management');

delete_permission([
    'type' => 'permission',
    'slug' => 'plan-manager',
]);

delete_permission([
    'type' => 'permission',
    'slug' => 'subscription-manager',
]);

delete_record([
    'table'           => 'tb_pages',
    'foreign_key'     => 'page_id',
    'where_field'     => 'slug',
    'where_value'     => 'plans',
    'tables_to_action'=> '-f',
]);

delete_record([
    'table'           => 'tb_pages',
    'foreign_key'     => 'page_id',
    'where_field'     => 'slug',
    'where_value'     => 'plan-manager',
    'tables_to_action'=> '-f',
]);

delete_record([
    'table'           => 'tb_pages',
    'foreign_key'     => 'page_id',
    'where_field'     => 'slug',
    'where_value'     => 'subscriptions',
    'tables_to_action'=> '-f',
]);

delete_record([
    'table'           => 'tb_pages',
    'foreign_key'     => 'page_id',
    'where_field'     => 'slug',
    'where_value'     => 'subscription-manager',
    'tables_to_action'=> '-f',
]);

delete_record([
    'table'           => 'tb_pages',
    'foreign_key'     => 'page_id',
    'where_field'     => 'slug',
    'where_value'     => 'my-subscriptions',
    'tables_to_action'=> '-f',
]);

delete_record([
    'table'           => 'tb_pages',
    'foreign_key'     => 'page_id',
    'where_field'     => 'slug',
    'where_value'     => 'cancel-subscription',
    'tables_to_action'=> '-f',
]);

// Remove PyroSales menu entry
delete_record([
    'table'           => 'tb_menus',
    'foreign_key'     => 'menu_id',
    'where_field'     => 'slug',
    'where_value'     => 'plan-manager',
    'tables_to_action'=> '-f',
]);

// Remove "Subscriptions" admin menu entry
delete_record([
    'table'           => 'tb_menus',
    'foreign_key'     => 'menu_id',
    'where_field'     => 'slug',
    'where_value'     => 'subscriptions',
    'tables_to_action'=> '-f',
]);

// Remove PyroSales settings CRUD
delete_record([
    'table'           => 'tb_cruds',
    'foreign_key'     => 'crud_id',
    'where_field'     => 'slug',
    'where_value'     => 'plans-subscriptions-settings',
    'tables_to_action'=> '-f',
]);
