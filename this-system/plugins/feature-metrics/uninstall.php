<?php
if(!isset($seg)) exit;

query_it("DROP TABLE IF EXISTS tb_feature_metrics");


/**
 * Delete custom permission for managing feature metrics.
 */
feature('permissions-management');

delete_permission([
    'type' => 'permission',
    'slug' => 'manage-feature-metrics',
]);


delete_record([
    'table' => 'tb_pages',
    'foreign_key' => 'page_id',
    'where_field' => 'slug',
    'where_value' => 'feature-metrics',
    'tables_to_action' => '-f',
]);
