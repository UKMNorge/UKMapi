CREATE TABLE `ukm_rel_arrangement_innslag` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `innslag_id` int(11) NOT NULL,
  `arrangement_id` int(11) NOT NULL,
  `fra_arrangement_id` int(11) NOT NULL,
  `fra_arrangement_navn` varchar(255) COLLATE utf8mb4_danish_ci NOT NULL DEFAULT '' COMMENT 'Kopi av fra-arrangementets navn. Sparer en join når vi sier hvor innslaget er fra',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNQ_innslag_arrangement` (`innslag_id`,`arrangement_id`),
  KEY `innslag_id` (`innslag_id`),
  KEY `arrangement_id` (`arrangement_id`),
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8mb4_danish_ci;


CREATE TABLE `ukm_rel_arrangement_person` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `innslag_id` int(11) NOT NULL,
  `person_id` int(11) NOT NULL,
  `arrangement_id` int(11) NOT NULL,
  `fra_arrangement_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNQ_person_innslag_arrangement` (`innslag_id`,`person_id`,`arrangement_id`),
  KEY `innslag_id` (`innslag_id`),
  KEY `arrangement_id` (`arrangement_id`),
  KEY `person_id` (`person_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8mb4_danish_ci;