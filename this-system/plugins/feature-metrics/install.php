<?php
if (!isset($seg)) exit;


/**
 * Create table
 */
$sql = "
CREATE TABLE IF NOT EXISTS tb_feature_metrics (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    feature_name VARCHAR(191) NOT NULL,
    user_id INT UNSIGNED NULL,
    type TINYINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_feature (feature_name),
    KEY idx_user (user_id),
    KEY idx_type (type),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
";
query_it($sql);



/**
 * Register custom permission for managing feature metrics.
 * To be called inside this plugin's install script.
 */
feature('permissions-management');

$payload = [
    'allowed'         => [1],
    'name'            => 'Manage feature metrics',
    'slug'            => 'manage-feature-metrics',
    'permission_type' => 'only_these',
    'type'            => 'permission',
];
update_permissions($payload, false);


feature('page-crud-management-system');

$page = [
    'title' => 'Feature metrics',
    'slug' => 'feature-metrics',
    'page_type' => 'not_essential',
    'status_page_id' => 1,
    'page_area' => 'admin',
    'permission_type' => 'only_these',
    'allowed' => [1],
    'page_template' => PLUGINS_PATH .'/feature-metrics/custom-pages/feature-metrics-results.php',
];
manage_page_system($page, 'insert');
