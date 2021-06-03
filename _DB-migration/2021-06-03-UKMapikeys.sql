-- Create syntax for TABLE 'API_Keys'
CREATE TABLE `API_Keys` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `api_key` varchar(50) COLLATE utf8mb4_danish_ci NOT NULL DEFAULT '',
  `secret` varchar(60) COLLATE utf8mb4_danish_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_danish_ci;

-- Create syntax for TABLE 'API_Permissions'
CREATE TABLE `API_Permissions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `system` varchar(50) COLLATE utf8mb4_danish_ci NOT NULL DEFAULT '',
  `permission` varchar(50) COLLATE utf8mb4_danish_ci NOT NULL DEFAULT '',
  `api_key` varchar(50) COLLATE utf8mb4_danish_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_danish_ci;