ALTER TABLE `ukm_rapport_template` 
ADD `omrade_id` INT(11)  NULL  DEFAULT NULL AFTER `config`
ADD `omrade_type` VARCHAR(20)  NULL  DEFAULT NULL  AFTER `omrade_id`
ADD INDEX (`omrade_id`);
ADD INDEX (`omrade_type`);