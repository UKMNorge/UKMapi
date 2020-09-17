ALTER TABLE `slack_user`
ADD COLUMN `active` enum('true','false') COLLATE utf8mb4_danish_ci NOT NULL DEFAULT 'true'