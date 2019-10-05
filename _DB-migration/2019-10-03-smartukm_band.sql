ALTER TABLE `smartukm_band`
ADD COLUMN `b_home_pl` INT(11) AFTER `b_kommune`,
ADD COLUMN `b_status_object` TEXT AFTER `b_status_text`;

INSERT INTO `log_actions` (`log_action_id`, `log_action_verb`, `log_action_element`, `log_action_datatype`, `log_action_identifier`, `log_action_printobject`)
VALUES
	(328, 'endret', 'statustekst', 'text', 'smartukm_band|b_status_object', 1);
