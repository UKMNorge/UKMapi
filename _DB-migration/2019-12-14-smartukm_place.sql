ALTER TABLE `smartukm_place`
ADD COLUMN `pl_subtype` enum('monstring','arrangement') COLLATE utf8mb4_danish_ci NOT NULL DEFAULT 'monstring';