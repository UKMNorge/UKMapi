ALTER TABLE `ukm_nettverk_admins`
ADD COLUMN `is_contact` enum('true','false') COLLATE utf8mb4_danish_ci NOT NULL DEFAULT 'true',
ADD INDEX `is_contact` (`is_contact`);

ALTER TABLE `smartukm_contacts`
ADD COLUMN `admin_id` INT(11) AFTER `system_locked`,
ADD UNIQUE INDEX `admin_id` (`admin_id`);

INSERT INTO `log_actions` (`log_action_id`, `log_action_verb`, `log_action_element`, `log_action_datatype`, `log_action_identifier`, `log_action_printobject`)
VALUES
	(1110, 'endret', 'admin_id', 'int', 'smartukm_contact|admin_id', 1);

ALTER TABLE `smartukm_contacts`
DROP COLUMN `adress`,
DROP COLUMN `birth`,
DROP COLUMN `company`,
DROP COLUMN `postalcode`,
DROP COLUMN `postalplace`;