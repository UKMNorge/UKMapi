ALTER TABLE `smartukm_place`
ADD COLUMN `pl_subtype` enum('monstring','arrangement') COLLATE utf8mb4_danish_ci NOT NULL DEFAULT 'monstring';

INSERT INTO `log_actions` (`log_action_id`, `log_action_verb`, `log_action_element`, `log_action_datatype`, `log_action_identifier`, `log_action_printobject`)
VALUES
	(131, 'endret', 'subtype', 'text', 'smartukm_place|pl_subtype', 1);