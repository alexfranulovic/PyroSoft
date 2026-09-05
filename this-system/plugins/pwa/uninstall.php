<?php
if(!isset($seg)) exit;

/**
 * PERMISSIONS CLEANUP
 *
 * Ensures the permissions management feature is loaded before deletion.
 * Then removes the custom permission created by the plugin.
 */
feature('permissions-management');

delete_permission([
    'type' => 'permission',
    'slug' => 'pwa-manager',
]);

// Remove "PWA Manager" page
delete_record([
    'table'           => 'tb_pages',
    'foreign_key'     => 'page_id',
    'where_field'     => 'slug',
    'where_value'     => 'pwa-manager',
    'tables_to_action'=> '-f',
]);

// Remove PWA menu entry
delete_record([
    'table'           => 'tb_menus',
    'foreign_key'     => 'menu_id',
    'where_field'     => 'slug',
    'where_value'     => 'pwa-manager',
    'tables_to_action'=> '-f',
]);
