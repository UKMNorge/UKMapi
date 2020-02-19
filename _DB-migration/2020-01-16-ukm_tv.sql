ALTER TABLE `ukm_related_video`
ADD COLUMN `pl_id` INT(11) NOT NULL DEFAULT 0,
ADD INDEX `pl_id` (`pl_id`);


CREATE TABLE `ukm_uploaded_video` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `cron_id` int(11) NOT NULL,
  `file` varchar(255) COLLATE utf8mb4_danish_ci DEFAULT NULL,
  `arrangement_id` int(11) NOT NULL,
  `innslag_id` int(11) NOT NULL COMMENT '0 hvis filmen ikke er knyttet til et innslag',
  `title_id` int(11) NOT NULL COMMENT '0 hvis filmen ikke er av en spesifikk tittel (av et innslag)',
  `season` int(4) NOT NULL,
  `converted` enum('false','true') COLLATE utf8mb4_danish_ci NOT NULL DEFAULT 'false',
  `title` varchar(255) COLLATE utf8mb4_danish_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_danish_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cron_id` (`cron_id`),
  UNIQUE KEY `file` (`file`),
  KEY `arrangement_id` (`arrangement_id`),
  KEY `innslag_id` (`innslag_id`),
  KEY `title_id` (`title_id`),
  KEY `season` (`season`),
  KEY `converted` (`converted`),
  KEY `title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_danish_ci;

ALTER TABLE `ukm_tv_tags`
DROP COLUMN `id`;

ALTER TABLE `ukm_tv_tags`
ADD `id` BIGINT(20) NOT NULL PRIMARY KEY AUTO_INCREMENT FIRST;

ALTER TABLE `ukm_tv_tags`
ADD UNIQUE KEY `UNIQ_TAG` (`tv_id`,`type`,`foreign_id`);

DROP TABLE IF EXISTS `ukm_tv_categories`;
DROP TABLE IF EXISTS `ukm_tv_category_folders`;
DROP TABLE IF EXISTS `ukm_tv_featured`;
DROP TABLE IF EXISTS `ukm_tv_img`;

ALTER TABLE `ukm_tv_files`
DROP `tv_category`,
DROP `tv_tags`,
ADD COLUMN `cron_id` INT(11) AFTER `tv_id`,
ADD COLUMN `pl_id` INT (11) AFTER `cron_id`,
ADD COLUMN `season` INT(4),
ADD INDEX (`cron_id`),
ADD INDEX (`pl_id`),
ADD INDEX (`season`);
