ALTER TABLE `smartukm_videresending_media`
ADD COLUMN `bilde_id` INT(11) DEFAULT NULL,
ADD INDEX `bilde_id` (`bilde_id`);