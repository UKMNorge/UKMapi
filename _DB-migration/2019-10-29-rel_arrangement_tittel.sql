CREATE TABLE `ukm_rel_arrangement_tittel` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `innslag_id` int(11) NOT NULL,
  `tittel_id` int(11) NOT NULL,
  `arrangement_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNQ_tittel_innslag_arrangement` (`innslag_id`,`tittel_id`,`arrangement_id`),
  KEY `innslag_id` (`innslag_id`),
  KEY `arrangement_id` (`arrangement_id`),
  KEY `tittel_id` (`tittel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;