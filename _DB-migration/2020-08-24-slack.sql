-- Create syntax for TABLE 'slack_access_token'
CREATE TABLE `slack_access_token` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `team_id` varchar(100) COLLATE utf8mb4_danish_ci NOT NULL DEFAULT '',
  `team_name` varchar(100) COLLATE utf8mb4_danish_ci DEFAULT NULL,
  `access_token` varchar(255) COLLATE utf8mb4_danish_ci NOT NULL DEFAULT '',
  `data` text COLLATE utf8mb4_danish_ci,
  `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `bot_id` varchar(100) COLLATE utf8mb4_danish_ci DEFAULT NULL,
  `bot_access_token` varchar(255) COLLATE utf8mb4_danish_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_danish_ci;

-- Create syntax for TABLE 'slack_channel'
CREATE TABLE `slack_channel` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `team_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `slack_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` json DEFAULT NULL,
  `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `team_id_2` (`team_id`,`slack_id`),
  KEY `team_id` (`team_id`),
  KEY `slack_id` (`slack_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create syntax for TABLE 'slack_template'
CREATE TABLE `slack_template` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_danish_ci NOT NULL DEFAULT '',
  `data` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_danish_ci;

-- Create syntax for TABLE 'slack_user'
CREATE TABLE `slack_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `team_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `slack_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `real_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `data` json DEFAULT NULL,
  `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `team_id_2` (`team_id`,`slack_id`),
  KEY `team_id` (`team_id`),
  KEY `user_id` (`slack_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create syntax for TABLE 'slack_view_tempdata'
CREATE TABLE `slack_view_tempdata` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `view_id` varchar(150) COLLATE utf8mb4_danish_ci NOT NULL DEFAULT '',
  `key` varchar(150) COLLATE utf8mb4_danish_ci NOT NULL DEFAULT '',
  `value` text COLLATE utf8mb4_danish_ci,
  `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `view_id` (`view_id`,`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_danish_ci;