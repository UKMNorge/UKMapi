ALTER TABLE `ukm_bilder`
ADD COLUMN `c_id` INT(11) AFTER `b_id`,
ADD COLUMN `synced_dropbox` enum('false','true','nogo') NOT NULL DEFAULT 'false' AFTER `timestamp`,
ADD COLUMN `synced_flickr` enum('false','true','nogo') NOT NULL DEFAULT 'false' AFTER `synced_dropbox`,
ADD COLUMN `flickr_data` text COLLATE utf8mb4_danish_ci AFTER `synced_flickr`,
ADD COLUMN `dropbox_data` text COLLATE utf8mb4_danish_ci AFTER `flickr_data`,
ADD INDEX `b_id` (`b_id`),
ADD INDEX `season` (`season`),
ADD INDEX `pl_id` (`pl_id`),
ADD INDEX `c_id` (`c_id`),
ADD INDEX `status` (`status`),
ADD INDEX `synced_dropbox` (`synced_dropbox`),
ADD INDEX `synced_flickr` (`synced_flickr`),
ADD INDEX `wp_post` (`wp_post`);