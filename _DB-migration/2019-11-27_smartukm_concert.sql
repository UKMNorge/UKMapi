ALTER TABLE `smartukm_concert`
ADD COLUMN `c_teknisk_prove` enum('false','true') COLLATE utf8mb4_danish_ci NOT NULL DEFAULT 'false',
ADD COLUMN `c_visible_oppmote` enum('false','true') COLLATE utf8mb4_danish_ci NOT NULL DEFAULT 'false',
ADD COLUMN `c_intern` enum('false','true') COLLATE utf8mb4_danish_ci NOT NULL DEFAULT 'false',
ADD COLUMN `c_type` enum('default','post','category') COLLATE utf8mb4_danish_ci NOT NULL DEFAULT 'default',
ADD COLUMN `c_type_post_id` int(11) DEFAULT NULL,
ADD COLUMN `c_type_category_id` int(11) DEFAULT NULL,
ADD COLUMN `c_beskrivelse` text COLLATE utf8mb4_danish_ci,
ADD COLUMN `c_color` varchar(7) COLLATE utf8mb4_danish_ci DEFAULT '' COMMENT '#hexcode',
ADD COLUMN `c_fremhevet` enum('false','true') COLLATE utf8mb4_danish_ci NOT NULL DEFAULT 'false',
ADD INDEX `c_name` (`c_name`),
ADD INDEX `c_start` (`c_start`),
ADD INDEX `c_visible_program` (`c_visible_program`),
ADD INDEX `c_intern` (`c_intern`);