CREATE TABLE `ukm_rel_arrangement_innslag_type` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pl_id` int(11) NOT NULL,
  `type_id` varchar(20) COLLATE utf8mb4_danish_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNQ_pl_type_id` (`pl_id`,`type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_danish_ci;