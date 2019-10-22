CREATE TABLE `ukm_meta` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parent_type` varchar(50) COLLATE utf8mb4_danish_ci NOT NULL DEFAULT '',
  `parent_id` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_danish_ci NOT NULL DEFAULT '',
  `value` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique-value-for-parent` (`parent_type`,`parent_id`,`name`),
  KEY `parent_type` (`parent_type`),
  KEY `parent_id` (`parent_id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_danish_ci;