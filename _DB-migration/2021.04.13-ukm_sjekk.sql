CREATE TABLE `ukm_sjekk` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `phone` int(11) NOT NULL,
  `hash` varchar(250) COLLATE utf8mb4_danish_ci NOT NULL,
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_danish_ci;