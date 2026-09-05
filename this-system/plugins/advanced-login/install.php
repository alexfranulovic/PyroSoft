<?php
if (!isset($seg)) exit;

/**
 * Advanced login - schema install.
 *
 * Cria as duas tabelas do plugin:
 *  - tb_user_social_identities : vinculo universal usuario <-> provedor social (google, e futuros)
 *  - tb_user_remember_tokens   : cookies persistentes "manter conectado" (selector/validator)
 */

$sql = "
CREATE TABLE IF NOT EXISTS `tb_user_social_identities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `provider` varchar(20) NOT NULL,
  `provider_user_id` varchar(191) NOT NULL,
  `email` varchar(191) DEFAULT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(191) DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `access_token` text DEFAULT NULL,
  `refresh_token` text DEFAULT NULL,
  `token_expires_at` datetime DEFAULT NULL,
  `scopes` text DEFAULT NULL,
  `raw_profile` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_provider_identity` (`provider`, `provider_user_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
query_it($sql);

$sql = "
CREATE TABLE IF NOT EXISTS `tb_user_remember_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `selector` varchar(24) NOT NULL,
  `validator_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `last_used_at` datetime DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_selector` (`selector`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
query_it($sql);
