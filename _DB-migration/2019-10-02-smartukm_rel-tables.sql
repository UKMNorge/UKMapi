ALTER TABLE `smartukm_rel_b_p`
ADD COLUMN `instrument_object` MEDIUMTEXT AFTER `instrument`;

ALTER TABLE `smartukm_rel_pl_b`
ADD COLUMN `pl_b_id` INT(11) PRIMARY KEY AUTO_INCREMENT FIRST;