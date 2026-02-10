<?php
if(!isset($seg)) exit;

$sql = "
CREATE TABLE IF NOT EXISTS `tb_user_biometrics`
(
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `descriptor_json` JSON NOT NULL,
    `photo_path` VARCHAR(255) DEFAULT NULL,
    `model_version` VARCHAR(20) DEFAULT '1.0',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT `fk_tb_user_biometrics_user`
        FOREIGN KEY (`user_id`)
        REFERENCES `tb_users`(`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

query_it($sql);
