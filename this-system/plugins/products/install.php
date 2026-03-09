<?php
if (!isset($seg)) exit;


/**
 * Create table
 */
$sql = "
CREATE TABLE `tb_products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NULL,

  -- Basic
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NULL,
  `description` TEXT NULL,
  `seo` VARCHAR(255) NULL,
  `view_count` INT UNSIGNED NOT NULL DEFAULT 0,

  -- Pricing
  `regular_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `sale_price` DECIMAL(10,2) NULL,
  `item_cost` DECIMAL(10,2) NULL,

  -- Inventory
  `manage_stock` TINYINT(1) NOT NULL DEFAULT 0,
  `stock_qty` INT NULL,
  `backorder` TINYINT(1) NOT NULL DEFAULT 0,
  `min_cart_qty` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `max_cart_qty` SMALLINT UNSIGNED NULL DEFAULT NULL,

  -- Shipping (physical)
  `requires_shipping` TINYINT(1) NOT NULL DEFAULT 1,
  `weight` DECIMAL(10,3) NULL,
  `length` DECIMAL(10,3) NULL,
  `width`  DECIMAL(10,3) NULL,
  `height` DECIMAL(10,3) NULL,
  `delivery_time_days` SMALLINT NULL,

  -- Codes
  `sku` VARCHAR(100) NULL,
  `barcode` VARCHAR(50) NULL,

  -- Downloadable
  `download_url` VARCHAR(1500) NULL,
  `download_name` VARCHAR(255) NULL,
  `download_limit` INT UNSIGNED NULL,
  `download_expiry_days` INT UNSIGNED NULL,

  -- Ownership / misc
  `product_type` ENUM('simple','variable','downloadable') NOT NULL DEFAULT 'simple',
  `user_id` INT UNSIGNED NULL,
  `status_id` INT UNSIGNED NULL,
  `is_visible` TINYINT(1) NOT NULL DEFAULT 0,

  -- Timestamps
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),

  -- Indexes
  INDEX `idx_parent` (`product_id`),
  INDEX `idx_type` (`product_type`),
  INDEX `idx_status_id` (`status_id`),
  INDEX `idx_user` (`user_id`),
  INDEX `idx_is_visible` (`is_visible`),

  CONSTRAINT `fk_products_parent`
    FOREIGN KEY (`product_id`) REFERENCES `tb_products` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;
";
query_it($sql);


$sql = "
CREATE TABLE `tb_product_categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `category_id` INT UNSIGNED NULL COMMENT 'for hierarchical categories',
  `status_id` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),

  INDEX `idx_parent` (`category_id`),
  INDEX `idx_status_id` (`status_id`),

  CONSTRAINT `fk_categories_parent`
    FOREIGN KEY (`category_id`) REFERENCES `tb_product_categories` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;
";
query_it($sql);


$sql = "
CREATE TABLE `tb_product_category_relations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,

  PRIMARY KEY (`id`),

  UNIQUE KEY `uk_product_category` (`product_id`, `category_id`),
  INDEX `idx_product` (`product_id`),
  INDEX `idx_category` (`category_id`),

  CONSTRAINT `fk_rel_product`
    FOREIGN KEY (`product_id`) REFERENCES `tb_products` (`id`)
    ON DELETE CASCADE,

  CONSTRAINT `fk_rel_category`
    FOREIGN KEY (`category_id`) REFERENCES `tb_product_categories` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;
";
query_it($sql);


$sql = "
CREATE TABLE `tb_product_medias` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `url` VARCHAR(1000) NOT NULL,
  `alt` VARCHAR(255) NULL,
  `role` ENUM('main','gallery') NOT NULL DEFAULT 'gallery',
  `order_reg` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  INDEX `idx_product` (`product_id`),
  INDEX `idx_role_sort` (`role`, `order_reg`),

  CONSTRAINT `fk_medias_product`
    FOREIGN KEY (`product_id`) REFERENCES `tb_products` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;
";
query_it($sql);



// feature('permissions-management');
// $payload = [
//     'allowed'         => [1],
//     'name'            => 'Manage feature metrics',
//     'slug'            => 'manage-feature-metrics',
//     'permission_type' => 'only_these',
//     'type'            => 'permission',
// ];
// update_permissions($payload, false);


// feature('page-crud-management-system');

// $page = [
//     'title' => 'Feature metrics',
//     'slug' => 'feature-metrics',
//     'page_type' => 'not_essential',
//     'status_page_id' => 1,
//     'page_area' => 'admin',
//     'permission_type' => 'only_these',
//     'allowed' => [1],
//     'page_template' => PLUGINS_PATH .'/feature-metrics/custom-pages/feature-metrics-results.php',
// ];
// manage_page_system($page, 'insert');
