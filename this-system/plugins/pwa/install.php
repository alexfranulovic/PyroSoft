<?php
if(!isset($seg)) exit;


/**
 * PERMISSIONS
 *
 * Registers a custom permission used by the plugin to manager the PWA.
 * This requires the permissions feature to be loaded.
 */
feature('permissions-management');

$payload = [
  'allowed'         => [1],
  'name'            => 'PWA manager',
  'slug'            => 'pwa-manager',
  'permission_type' => 'only_these',
  'type'            => 'permission',
];
update_permissions($payload, false);


feature('page-crud-management-system');
/**
 * ADMIN PAGE
 *
 * Registers two admin pages (restricted to allowed users):
 * - pwa-manager    :
 */
$page = [
  'title' => 'PWA manager',
  'slug' => 'pwa-manager',
  'page_type' => 'not_essential',
  'status_page_id' => 1,
  'page_area' => 'admin',
  'permission_type' => 'only_these',
  'allowed' => [1],
  'page_template' => PLUGINS_PATH . '/pwa/custom-pages/pwa-manager.php',
];
manage_page_system($page, 'insert');


/**
 * ADMIN MENU
 *
 * Notes:
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
      'title' => 'Mobile',
      'slug' => 'pwa-manager',
    ],
    [
      'position' => 16,
      'depth' => '1',
      'title' => 'WebApp',
      'icon' => 'fas fa-shopping-bag',
      'which_users' => 'logged_in',
      'style' => 'generic',
      'page_id' => get_page_id_by_slug('pwa-manager'),
      'type' => 'page',
      'slug' => 'pwa-manager',
    ],
  ],
], $debug);
