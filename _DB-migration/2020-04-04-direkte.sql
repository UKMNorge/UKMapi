CREATE TABLE `ukm_direkte` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `hendelse_id` int(11) NOT NULL,
  `start_offset` int(3) NOT NULL DEFAULT '0',
  `varighet` int(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `hendelse_id` (`hendelse_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

DROP VIEW IF EXISTS `ukm_direkte_view`;
CREATE VIEW `ukm_direkte_view`
AS 
SELECT `ukm_direkte`.*,
`hendelse`.`c_name` AS `navn`,
`hendelse`.`c_place` AS `sted`,
FROM_UNIXTIME( `hendelse`.`c_start` + `start_offset` * 60 ) AS `start`,
FROM_UNIXTIME( `hendelse`.`c_start` + (`start_offset` + `varighet`) * 60) AS `stopp`,
`arrangement`.`pl_id` AS `arrangement_id`,
`arrangement`.`pl_name` AS `arrangement_navn`,
`arrangement`.`pl_link` AS `arrangement_lenke`,
`arrangement`.`pl_type` AS `eier_type`,
`arrangement`.`pl_owner_kommune` AS `eier_kommune`,
`arrangement`.`pl_owner_fylke` AS `eier_fylke`,
`meta_link`.`value` AS `link`,
`meta_embed`.`value` AS `embed`
FROM `ukm_direkte`
JOIN `smartukm_concert` AS `hendelse`
	ON (`hendelse`.`c_id` = `ukm_direkte`.`hendelse_id`)
JOIN `smartukm_place` AS `arrangement`
	ON (`arrangement`.`pl_id` = `hendelse`.`pl_id`)
LEFT JOIN `ukm_meta` AS `meta_link`
	ON (`meta_link`.`parent_type` = 'arrangement' AND `meta_link`.`parent_id` = `arrangement`.`pl_id` AND `meta_link`.`name` = 'live_link')
LEFT JOIN `ukm_meta` AS `meta_embed`
	ON (`meta_link`.`parent_type` = 'arrangement' AND `meta_link`.`parent_id` = `arrangement`.`pl_id` AND `meta_link`.`name` = 'live_embed')
