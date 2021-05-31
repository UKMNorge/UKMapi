CREATE TABLE `ukm_band_comment` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `innslag_id` int(11) NOT NULL,
  `arrangement_id` int(11) NOT NULL,
  `kommentar` text COLLATE utf8mb4_danish_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `innslag_id_2` (`innslag_id`,`arrangement_id`),
  KEY `innslag_id` (`innslag_id`),
  KEY `arra` (`arrangement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_danish_ci;

INSERT INTO `log_actions` (`log_action_id`, `log_action_verb`, `log_action_element`, `log_action_datatype`, `log_action_identifier`, `log_action_printobject`)
VALUES
	(329, 'endret', 'innslagets kommentar', 'text', 'smartukm_band_comment|comment', 1),
	(330, 'slettet', 'innslagets kommentar', 'text', 'smartukm_band_comment|comment_delete', 1);
