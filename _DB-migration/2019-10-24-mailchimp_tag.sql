CREATE TABLE `mailchimp_tag` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `audience_id` varchar(50) COLLATE utf8mb4_danish_ci NOT NULL DEFAULT '',
  `mailchimp_id` varchar(50) COLLATE utf8mb4_danish_ci NOT NULL DEFAULT '',
  `tag` varchar(100) COLLATE utf8mb4_danish_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNQ_tag_audience` (`audience_id`,`tag`),
  KEY `audience_id` (`audience_id`),
  KEY `mailchimp_id` (`mailchimp_id`),
  KEY `tag` (`tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_danish_ci;