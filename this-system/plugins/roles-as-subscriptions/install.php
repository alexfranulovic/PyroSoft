<?php
if (!isset($seg)) exit;

/**
 * USER ROLES – Subscription Plan Fields
 *
 * Extends `tb_user_roles` to support subscription-based roles
 * (type = 'signature').
 *
 * These fields allow a role to behave as a subscription plan,
 * including pricing, billing cycle, visibility and ownership.
 */
$sql = "
ALTER TABLE `tb_user_roles`
  ADD COLUMN `currency` CHAR(3) NULL DEFAULT NULL AFTER `status_id`,
  ADD COLUMN `regular_price` DECIMAL(10,2) NULL DEFAULT NULL AFTER `currency`,
  ADD COLUMN `sale_price` DECIMAL(10,2) NULL DEFAULT NULL AFTER `regular_price`,
  ADD COLUMN `interval_unit` ENUM('day','week','month','year') NULL DEFAULT NULL AFTER `sale_price`,
  ADD COLUMN `interval_count` SMALLINT UNSIGNED NULL DEFAULT NULL AFTER `interval_unit`,
  ADD COLUMN `trial_days` SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER `interval_count`,
  ADD COLUMN `grace_days` SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER `trial_days`,
  ADD COLUMN `auto_renew` TINYINT(1) NOT NULL DEFAULT 1 AFTER `grace_days`,
  ADD COLUMN `is_visible` TINYINT(1) NOT NULL DEFAULT 0 AFTER `auto_renew`,
  ADD COLUMN `activation_function` VARCHAR(255) DEFAULT NULL AFTER `is_visible`,
  ADD COLUMN `allows_proposal_lock` TINYINT(1) DEFAULT 0 AFTER `activation_function`,
  ADD COLUMN `fee_mode` enum('merchant','customer','split','') NOT NULL DEFAULT 'merchant' AFTER `allows_proposal_lock`,
  ADD COLUMN `user_id` BIGINT UNSIGNED NULL AFTER `fee_mode`;
";
query_it($sql);


/**
 * NOTE:
 * The following statement redundantly adds `user_id` again.
 * This may cause an error if executed after the previous block.
 * Should be reviewed for duplication.
 */
$sql = "
ALTER TABLE `tb_user_roles`
  ADD COLUMN `user_id` BIGINT UNSIGNED NULL AFTER `status_id`;
";
query_it($sql);


/**
 * Adds index for fast lookup of plans owned by a specific user.
 */
$sql = "
ALTER TABLE `tb_user_roles`
  ADD INDEX `idx_user_id` (`user_id`);
";
query_it($sql);


/**
 * Adds foreign key to enforce ownership integrity.
 *
 * If the owner user is deleted, the plan ownership becomes NULL
 * instead of deleting the role itself.
 */
$sql = "
ALTER TABLE `tb_user_roles`
  ADD CONSTRAINT `fk_user_roles_owner`
    FOREIGN KEY (`user_id`) REFERENCES `tb_users` (`id`)
    ON DELETE SET NULL;
";
query_it($sql);



/**
 * USER ROLE ASSIGNMENTS – Subscription State Fields
 *
 * Extends `tb_user_role_assignments` to support
 * subscription lifecycle management per user.
 *
 * This transforms the assignment into a subscription instance.
 */
$sql = "
ALTER TABLE `tb_user_role_assignments`
  ADD COLUMN `status_id` ENUM('active','trialing','past_due','canceled','expired') NULL DEFAULT NULL AFTER `role_id`,
  ADD COLUMN `started_at` DATETIME NULL AFTER `status_id`,
  ADD COLUMN `trial_ends_at` DATETIME NULL AFTER `started_at`,
  ADD COLUMN `current_period_start` DATETIME NULL AFTER `trial_ends_at`,
  ADD COLUMN `current_period_end` DATETIME NULL AFTER `current_period_start`,
  ADD COLUMN `next_billing_at` DATETIME NULL AFTER `current_period_end`,
  ADD COLUMN `canceled_at` DATETIME NULL AFTER `next_billing_at`,
  ADD COLUMN `provider` VARCHAR(32) NULL AFTER `canceled_at`,
  ADD COLUMN `provider_subscription_id` VARCHAR(220) NULL AFTER `provider`;
";
query_it($sql);


/**
 * Prevents duplicate role assignments for the same user.
 * Ensures one role per user per role_id.
 */
$sql = "
ALTER TABLE `tb_user_role_assignments`
  ADD UNIQUE KEY `uq_user_role` (`user_id`, `role_id`);
";
query_it($sql);


/**
 * Performance indexes for subscription queries:
 * - role lookup
 * - status filtering
 * - next billing cron processing
 */
$sql = "
ALTER TABLE `tb_user_role_assignments`
  ADD INDEX `idx_role` (`role_id`),
  ADD INDEX `idx_status` (`status_id`),
  ADD INDEX `idx_next_billing` (`next_billing_at`);
";
query_it($sql);


/**
 * Adds referential integrity:
 *
 * - Deletes role assignments when user is deleted
 * - Deletes role assignments when role is deleted
 */
$sql = "
ALTER TABLE `tb_user_role_assignments`
  ADD CONSTRAINT `fk_ura_user`
    FOREIGN KEY (`user_id`) REFERENCES `tb_users` (`id`)
    ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ura_role`
    FOREIGN KEY (`role_id`) REFERENCES `tb_user_roles` (`id`)
    ON DELETE CASCADE;
";
query_it($sql);
