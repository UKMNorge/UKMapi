CREATE TABLE `ukm_nettverk_admins` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `wp_user_id` int(11) NOT NULL,
  `geo_type` enum('land','fylke','kommune','monstring') NOT NULL DEFAULT 'monstring',
  `geo_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`wp_user_id`),
  KEY `geo_type` (`geo_type`),
  KEY `geo_id` (`geo_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;