# FJERN UBRUKTE KOLONNER
ALTER TABLE `smartukm_place`,
DROP COLUMN `pl_contact`,
DROP COLUMN `contactp_konferansier`,
DROP COLUMN `contactp_nettred`,
DROP COLUMN `contactp_sceneteknikk`
;

# RENAME UTDATERTE KOLONNER
ALTER TABLE `smartukm_place`
CHANGE COLUMN `pl_start` `old_pl_start` INT(20) AFTER `pl_link`,
CHANGE COLUMN `pl_stop` `old_pl_stop` INT(20) AFTER `old_pl_start`,
CHANGE COLUMN `pl_deadline` `old_pl_deadline` INT(20) AFTER `old_pl_stop`,
CHANGE COLUMN `pl_deadline2` `old_pl_deadline2` INT(20) AFTER `old_pl_deadline`,
CHANGE COLUMN `pl_kommune` `old_pl_kommune` INT(10) AFTER `old_pl_deadline2`,
CHANGE COLUMN `pl_fylke` `old_pl_fylke` INT(10) AFTER `old_pl_kommune`;

# LEGG TIL NYE KOLONNER MED BEDRE DATATYPER
ALTER TABLE `smartukm_place`
ADD COLUMN `pl_registered` ENUM('false','true') NOT NULL DEFAULT 'false' AFTER `pl_place`,
ADD COLUMN `pl_start` DATETIME AFTER `pl_registered`,
ADD COLUMN `pl_stop` DATETIME AFTER `pl_start`,
ADD COLUMN `pl_deadline` DATETIME AFTER `pl_stop`,
ADD COLUMN `pl_deadline2` DATETIME AFTER `pl_deadline`,
ADD COLUMN `pl_pamelding` ENUM('apen','betinget','ingen') NOT NULL DEFAULT 'apen' AFTER `pl_deadline2`,
ADD COLUMN `pl_videresending` ENUM('true','false') NOT NULL DEFAULT 'true' AFTER `pl_pamelding`,
ADD COLUMN `pl_has_form` ENUM('false','true') DEFAULT 'false' AFTER `pl_videresending`,
ADD COLUMN `pl_owner_fylke` INT(3) AFTER `pl_pamelding`,
ADD COLUMN `pl_owner_kommune` INT(4) AFTER `pl_fylke`,
ADD COLUMN `pl_type` ENUM('kommune','fylke','land','ukjent') NOT NULL DEFAULT 'ukjent' AFTER `pl_name`,
ADD COLUMN `pl_location` JSON AFTER `pl_place`,
ADD COLUMN `pl_visible` ENUM('true','false') NOT NULL DEFAULT 'true' AFTER `pl_type`;
#ADD COLUMN `pl_type` ENUM('monstring_liten','monstring_stor','monstring','workshop') NOT NULL DEFAULT 'monstring' AFTER `pl_name`;

# OVERFØR TIMESTAMPS
UPDATE `smartukm_place`
    SET `pl_start` = FROM_UNIXTIME(`old_pl_start`),
    `pl_stop` = FROM_UNIXTIME(`old_pl_stop`),
    `pl_deadline` = FROM_UNIXTIME(`old_pl_deadline`),
    `pl_deadline2` = FROM_UNIXTIME(`old_pl_deadline2`);

# SJEKK OM MØNSTRINGEN ER REGISTERT
UPDATE `smartukm_place`
    SET `pl_registered` = 'true'
    WHERE `old_pl_start` > 150000
    AND `season` < 2019;

# SETT EIER_TYPE: LAND
UPDATE `smartukm_place`
	SET `pl_type` = 'land'
	WHERE `old_pl_kommune` > 1000
 	AND `old_pl_fylke` > 1000
    AND `season` < 2019;

# SETT EIER_TYPE: FYLKE
UPDATE `smartukm_place`
	SET `pl_type` = 'fylke' 
	WHERE `old_pl_fylke` > 0 
	AND `old_pl_fylke` < 100
    AND `season` < 2019;

# SETT EIER_TYPE: KOMMUNE
UPDATE `smartukm_place`
	SET `pl_type` = 'kommune' 
	WHERE `old_pl_fylke` = 0
    AND `old_pl_kommune` > 1000
    AND `season` < 2019;

# SETT FYLKE FOR FYLKESMØNSTRINGER
UPDATE `smartukm_place`
	SET `pl_fylke` = `pl_fylke` 
	WHERE `old_pl_fylke` < 100
    AND `season` < 2019;

# SETT KOMMUNE HVIS VI HAR NOEN (SKAL VEL IKKE SKJE, EGENTLIG?)
UPDATE `smartukm_place`
	SET `pl_owner_kommune` = `pl_kommune` 
	WHERE `old_pl_kommune` > 1000
    AND `season` < 2019
;


# LEGG TIL NYE INDEXER
DROP INDEX `pl_start` ON `smartukm_place`;
DROP INDEX `pl_fylke` ON `smartukm_place`;
DROP INDEX `pl_kommune` ON `smartukm_place`;

CREATE INDEX `pl_start` ON smartukm_place (pl_start) USING BTREE;
CREATE INDEX `pl_stop` ON smartukm_place (pl_stop) USING BTREE;
CREATE INDEX `pl_deadline` ON smartukm_place (pl_deadline) USING BTREE;
CREATE INDEX `pl_deadline2` ON smartukm_place (pl_deadline2) USING BTREE;
CREATE INDEX `pl_pamelding` ON smartukm_place (pl_pamelding) USING BTREE;
CREATE INDEX `pl_fylke` ON smartukm_place (pl_fylke) USING BTREE;
CREATE INDEX `pl_kommune` ON smartukm_place (pl_kommune) USING BTREE;
CREATE INDEX `pl_type` ON smartukm_place (pl_type) USING BTREE;

CREATE INDEX `old_pl_start` ON smartukm_place (old_pl_start) USING BTREE;
CREATE INDEX `old_pl_fylke` ON smartukm_place (old_pl_fylke) USING BTREE;
CREATE INDEX `old_pl_kommune` ON smartukm_place (old_pl_kommune) USING BTREE;

## LEGG TIL TRIGGERE FOR Å OPPRETTHOLDE GAMLE KOLONNER
DELIMITER //
CREATE
	TRIGGER `smartukm_place_migration_20190823` BEFORE UPDATE
	ON `smartukm_place` 
	FOR EACH ROW
    BEGIN
        IF NEW.pl_start > DATE('2009-01-01') THEN
            SET NEW.pl_registered = 'true';
        ELSE
        	SET NEW.pl_registered = 'false';
        END IF;
        
        SET NEW.old_pl_fylke = NEW.pl_owner_fylke;
        SET NEW.old_pl_start = UNIX_TIMESTAMP( NEW.pl_start );
        SET NEW.old_pl_stop = UNIX_TIMESTAMP( NEW.pl_stop );
        SET NEW.old_pl_deadline = UNIX_TIMESTAMP( NEW.pl_deadline );
        SET NEW.old_pl_deadline2 = UNIX_TIMESTAMP( NEW.pl_deadline2 );
    END;//
DELIMITER ;

## LEGG TIL LOGG-FELTER
INSERT INTO `log_actions` (`log_action_id`, `log_action_verb`, `log_action_element`, `log_action_datatype`, `log_action_identifier`, `log_action_printobject`)
VALUES
	(119, 'endret', 'mønstringen har påmelding', 'bool', 'smartukm_place|pl_pamelding', 1),
	(120, 'endret', 'eier, fylke', 'int', 'smartukm_place|pl_owner_fylke', 1),
	(121, 'endret', 'eier, kommune', 'int', 'smartukm_place|pl_owner_kommune', 1),
    (122, 'endret', 'lokasjon (kart)', 'text', 'smartukm_place|pl_location', 1),
    (123, 'endret', 'om arrangementet tar i mot videresendinger', 'text', 'smartukm_place|pl_videresending', 1),
    (124, 'endret', 'om arrangementet tar i mot påmeldinger', 'text', 'smartukm_place|pl_pamelding', 1),
    (125, 'la til', 'arrangement som får videresende', 'text', 'ukm_rel_pl_videresending', 1),
    (126, 'fjernet', 'arrangement som får videresende', 'text', 'ukm_rel_pl_videresending', 1),
    (127, 'endret', 'om arrangementet har videresendingsskjema', 'text', 'smartukm_place|pl_has_form', 1),
    (128, 'endret', 'om arrangementet er synlig', 'text', 'smartukm_place|pl_visible', 1);


## VIDERESENDINGS-RELASJON
CREATE TABLE `ukm_rel_pl_videresending` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pl_id_receiver` int(11) NOT NULL COMMENT 'Mønstringen det kan videresendes til',
  `pl_id_sender` int(11) NOT NULL COMMENT 'Mønstringen som kan videresende',
  PRIMARY KEY (`id`),
  UNIQUE KEY `en-rad-per-relasjon` (`pl_id_receiver`,`pl_id_sender`),
  KEY `pl_id` (`pl_id_receiver`),
  KEY `allow_pl_id` (`pl_id_sender`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;