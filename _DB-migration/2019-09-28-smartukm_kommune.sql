# LEGG TIL NYE KOLONNER FOR Å HÅNDTERE ENDRINGER I KOMMUNESTRUKTUR
ALTER TABLE `smartukm_kommune`
ADD COLUMN `superseed` VARCHAR(80) AFTER `id`,
ADD COLUMN `alternate_name` VARCHAR(150) AFTER `name`,
ADD COLUMN `ssb_name` VARCHAR(150) AFTER `alternate_name`,
ADD COLUMN `active` ENUM('true','false') NOT NULL DEFAULT 'true' AFTER `alternate_name`;

CREATE INDEX `name` ON smartukm_kommune (name) USING BTREE; # Nyttig når vi sorterer
CREATE INDEX `superseed` ON smartukm_kommune (superseed) USING BTREE;
CREATE INDEX `active` ON smartukm_kommune (active) USING BTREE;