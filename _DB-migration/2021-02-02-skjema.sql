ALTER TABLE `ukm_videresending_skjema_svar` ADD `p_fra` INT(11)  NULL  DEFAULT NULL  COMMENT 'Participant id hvis deltakerskjema'  AFTER `pl_fra`;
ALTER TABLE `ukm_videresending_skjema_svar` CHANGE `pl_fra` `pl_fra` INT(11)  NOT NULL  COMMENT 'Arrangement id hvis videresendingsskjema';
ALTER TABLE `ukm_videresending_skjema` ADD `type` ENUM('arrangement','person')  NOT NULL  DEFAULT 'arrangement'  AFTER `eier_id`;
ALTER TABLE `ukm_videresending_skjema` ADD UNIQUE INDEX (`pl_id`, `type`);
