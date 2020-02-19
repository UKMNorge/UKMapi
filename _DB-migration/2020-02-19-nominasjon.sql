ALTER TABLE `ukm_nominasjon`
DROP COLUMN `niva`,
DROP COLUMN `fylke_id`,
DROP COLUMN `kommune_id`,
ADD COLUMN `arrangement_fra` INT (11) NOT NULL,
ADD COLUMN `arrangement_til` INT (11) NOT NULL,
ADD INDEX `arrangement_fra` (`arrangement_fra`),
ADD INDEX `arrangement_til` (`arrangement_til`)