ALTER TABLE `smartukm_place`
ADD COLUMN `pl_deleted` enum('false','true') COLLATE utf8mb4_danish_ci NOT NULL DEFAULT 'false',
ADD INDEX `pl_deleted` (`pl_deleted`);

INSERT INTO `log_actions` (`log_action_id`, `log_action_verb`, `log_action_element`, `log_action_datatype`, `log_action_identifier`, `log_action_printobject`)
VALUES
	(129, 'slettet', 'arrangementet', NULL, 'smartukm_place|pl_deleted|true', 1),
	(130, 'gjenopprettet', 'arrangementet', NULL, 'smartukm_place|pl_deleted|false', 1);
