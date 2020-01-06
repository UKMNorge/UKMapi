CREATE TABLE `ukm_delta_wp_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `delta_id` int(11) NOT NULL,
  `wp_id` bigint(20) NOT NULL,
  `participant_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `delta_id` (`delta_id`),
  KEY `wp_id` (`wp_id`),
  KEY `participant_id` (`participant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_danish_ci;

CREATE TABLE `ukm_delta_wp_login_token` (
  `token_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `delta_id` int(11) NOT NULL,
  `wp_id` bigint(20) NOT NULL,
  `secret` varchar(255) NOT NULL,
  `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `used` enum('false','true') NOT NULL DEFAULT 'false',
  PRIMARY KEY (`token_id`),
  KEY `delta_id` (`delta_id`),
  KEY `wp_id` (`wp_id`),
  KEY `secret` (`secret`),
  KEY `used` (`used`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;