ALTER TABLE `smartukm_titles_scene`
ADD COLUMN `t_instrumental` ENUM('0','1') DEFAULT '0' NOT NULL AFTER `t_time`,
ADD COLUMN `t_selfmade` ENUM('0','1') DEFAULT '0' NOT NULL AFTER `t_instrumental`,
ADD COLUMN `t_litterature_read` ENUM('0','1') DEFAULT '0' NOT NULL AFTER `t_selfmade`;
