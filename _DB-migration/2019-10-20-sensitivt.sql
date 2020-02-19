-- Create syntax for TABLE 'log_sensitivt'
CREATE TABLE `log_sensitivt` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `direction` enum('read','write') COLLATE utf8mb4_danish_ci NOT NULL DEFAULT 'read',
  `object_id` int(11) NOT NULL,
  `object_type` varchar(100) COLLATE utf8mb4_danish_ci NOT NULL DEFAULT '',
  `user_id` int(11) NOT NULL,
  `user_system` varchar(50) COLLATE utf8mb4_danish_ci NOT NULL,
  `user_ip` varchar(255) COLLATE utf8mb4_danish_ci NOT NULL DEFAULT '',
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_danish_ci;

-- Create syntax for TABLE 'ukm_sensitivt_intoleranse'
CREATE TABLE `ukm_sensitivt_intoleranse` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `p_id` int(11) NOT NULL,
  `tekst` text COLLATE utf8mb4_danish_ci,
  `liste` text COLLATE utf8mb4_danish_ci,
  `liste_human` text COLLATE utf8mb4_danish_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_danish_ci;