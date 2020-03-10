ALTER TABLE `smartukm_videresending_media` RENAME `ukm_videresending_media`;

DROP TABLE IF EXISTS `smartukm_videresending_lederskjema_middag`;
DROP TABLE IF EXISTS `smartukm_videresending_ledermiddag_ekstra`;
DROP TABLE IF EXISTS `smartukm_videresending_ledere_stats`;
DROP TABLE IF EXISTS `smartukm_videresending_ledere_middag`;
DROP TABLE IF EXISTS `smartukm_videresending_ledere`;
DROP TABLE IF EXISTS `smartukm_videresending_infoskjema`;
DROP TABLE IF EXISTS `smartukm_videresending_infoskjema_kunst`;
DROP TABLE IF EXISTS `smartukm_videresending_infoskjema_kunst_kolli`;
DROP TABLE IF EXISTS `smartukm_videresending_fylke_sporsmal`;
DROP TABLE IF EXISTS `smartukm_videresending_fylke_svar`;
DROP TABLE IF EXISTS `smartukm_videresending_hotell_ukm_norge`;


-- Create syntax for TABLE 'ukm_videresending_leder'
DROP TABLE IF EXISTS `ukm_videresending_leder`;
CREATE TABLE `ukm_videresending_leder` (
  `l_id` int(6) NOT NULL AUTO_INCREMENT,
  `l_navn` varchar(150) CHARACTER SET utf8 DEFAULT '',
  `l_epost` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `l_mobilnummer` int(9) DEFAULT NULL,
  `l_type` enum('hoved','utstilling','reise','turist','ledsager') CHARACTER SET utf8 NOT NULL DEFAULT 'reise',
  `arrangement_fra` int(6) NOT NULL,
  `arrangement_til` int(6) NOT NULL,
  PRIMARY KEY (`l_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

-- Create syntax for TABLE 'ukm_videresending_leder_natt'
DROP TABLE IF EXISTS `ukm_videresending_leder_natt`;
CREATE TABLE `ukm_videresending_leder_natt` (
  `n_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `l_id` int(11) DEFAULT NULL,
  `dato` varchar(5) DEFAULT NULL,
  `sted` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`n_id`),
  UNIQUE KEY `UNQ_leder_dato` (`l_id`,`dato`),
  KEY `l_id` (`l_id`),
  KEY `dato` (`dato`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;