/* LEGG TIL FELT */
ALTER TABLE `smartukm_place` ADD `pl_forward_start` DATETIME  NOT NULL  DEFAULT CURRENT_TIMESTAMP  AFTER `pl_deadline2`;
ALTER TABLE `smartukm_place` ADD `pl_forward_stop` DATETIME  NOT NULL  DEFAULT CURRENT_TIMESTAMP  AFTER `pl_forward_start`;
/* OVERFØR VERDIER */
UPDATE `smartukm_place` SET `pl_forward_stop` = `pl_deadline2`;
UPDATE `smartukm_place` SET `pl_forward_start` = `pl_deadline`;
/* OPPRETT LOG ACTIONS */
INSERT INTO `log_actions` (`log_action_id`, `log_action_verb`, `log_action_element`, `log_action_datatype`, `log_action_identifier`, `log_action_printobject`)
VALUES
	(132, 'endret', 'dato videresending åpner', 'datetime', 'smartukm_place|pl_forward_start', 1),
	(133, 'endret', 'dato videresending stenger', 'datetime', 'smartukm_place|pl_forward_stop', 1);
