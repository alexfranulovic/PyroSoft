<?php
if (!isset($seg)) exit;

/**
 * Rollback columns / indexes / FKs added by this plugin
 */

/**
 * 1) Drop foreign keys first
 */
$sql = "
ALTER TABLE `tb_user_role_assignments`
  DROP FOREIGN KEY `fk_ura_user`,
  DROP FOREIGN KEY `fk_ura_role`;
";
query_it($sql);

$sql = "
ALTER TABLE `tb_user_roles`
  DROP FOREIGN KEY `fk_user_roles_owner`;
";
query_it($sql);


/**
 * 2) Drop indexes / unique keys
 */
$sql = "
ALTER TABLE `tb_user_role_assignments`
  DROP INDEX `uq_user_role`,
  DROP INDEX `idx_role`,
  DROP INDEX `idx_status`,
  DROP INDEX `idx_next_billing`;
";
query_it($sql);

$sql = "
ALTER TABLE `tb_user_roles`
  DROP INDEX `idx_user_id`;
";
query_it($sql);


/**
 * 3) Drop added columns
 */
$sql = "
ALTER TABLE `tb_user_role_assignments`
  DROP COLUMN `provider_subscription_id`,
  DROP COLUMN `provider`,
  DROP COLUMN `canceled_at`,
  DROP COLUMN `next_billing_at`,
  DROP COLUMN `current_period_end`,
  DROP COLUMN `current_period_start`,
  DROP COLUMN `trial_ends_at`,
  DROP COLUMN `started_at`,
  DROP COLUMN `status`;
";
query_it($sql);

$sql = "
ALTER TABLE `tb_user_roles`
  DROP COLUMN `user_id`,
  DROP COLUMN `is_visible`,
  DROP COLUMN `auto_renew`,
  DROP COLUMN `grace_days`,
  DROP COLUMN `trial_days`,
  DROP COLUMN `interval_count`,
  DROP COLUMN `interval_unit`,
  DROP COLUMN `regular_price`,
  DROP COLUMN `sale_price`,
  DROP COLUMN `currency`;
  DROP COLUMN `activation_function`;
  DROP COLUMN `allows_proposal_lock`;
  DROP COLUMN `fee_mode`;
";
query_it($sql);
