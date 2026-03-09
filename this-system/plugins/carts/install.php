<?php
if (!isset($seg)) exit;


/**
 * Create table
 */
$sql = "
CREATE TABLE `tb_carts` (
  `id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(50) DEFAULT NULL,
  `created` datetime NOT NULL,
  `modified` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `tb_carts`
  ADD PRIMARY KEY (`id`);

  ALTER TABLE `tb_carts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
";
query_it($sql);
