ALTER TABLE `ukm_nettverk_admins`
ADD COLUMN `is_contact` enum('true','false') COLLATE utf8mb4_danish_ci NOT NULL DEFAULT 'true',
ADD INDEX `is_contact` (`is_contact`);

ALTER TABLE `smartukm_contacts`
ADD COLUMN `admin_id` INT(11) AFTER `system_locked`,
ADD UNIQUE INDEX `admin_id` (`admin_id`);