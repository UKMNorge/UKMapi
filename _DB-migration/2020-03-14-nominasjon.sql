-- Create syntax for TABLE 'ukm_nominasjon'
DROP TABLE IF EXISTS `ukm_nominasjon`;
CREATE TABLE `ukm_nominasjon` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `b_id` int(11) DEFAULT NULL,
  `season` int(4) NOT NULL,
  `type` enum('arrangor','media','konferansier') COLLATE utf8mb4_danish_ci DEFAULT 'arrangor',
  `nominert` enum('true','false') COLLATE utf8mb4_danish_ci NOT NULL DEFAULT 'false',
  `arrangement_fra` int(11) NOT NULL,
  `arrangement_til` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `participant_id` (`b_id`),
  KEY `type` (`type`),
  KEY `season` (`season`),
  KEY `arrangement_fra` (`arrangement_fra`),
  KEY `arrangement_til` (`arrangement_til`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_danish_ci;

-- Create syntax for TABLE 'ukm_nominasjon_arrangor'
DROP TABLE IF EXISTS `ukm_nominasjon_arrangor`;
CREATE TABLE `ukm_nominasjon_arrangor` (
  `nominasjon_arrangor_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `nominasjon` int(11) NOT NULL,
  `type_lydtekniker` enum('false','true') COLLATE utf8mb4_danish_ci NOT NULL DEFAULT 'false',
  `type_lystekniker` enum('false','true') COLLATE utf8mb4_danish_ci NOT NULL DEFAULT 'false',
  `type_vertskap` enum('false','true') COLLATE utf8mb4_danish_ci NOT NULL DEFAULT 'false',
  `type_produsent` enum('false','true') COLLATE utf8mb4_danish_ci NOT NULL DEFAULT 'false',
  `samarbeid` text COLLATE utf8mb4_danish_ci,
  `erfaring` text COLLATE utf8mb4_danish_ci,
  `suksesskriterie` text COLLATE utf8mb4_danish_ci,
  `annet` text COLLATE utf8mb4_danish_ci,
  `lyd-erfaring-1` text COLLATE utf8mb4_danish_ci,
  `lyd-erfaring-2` text COLLATE utf8mb4_danish_ci,
  `lyd-erfaring-3` text COLLATE utf8mb4_danish_ci,
  `lyd-erfaring-4` text COLLATE utf8mb4_danish_ci,
  `lyd-erfaring-5` text COLLATE utf8mb4_danish_ci,
  `lyd-erfaring-6` text COLLATE utf8mb4_danish_ci,
  `lys-erfaring-1` text COLLATE utf8mb4_danish_ci,
  `lys-erfaring-2` text COLLATE utf8mb4_danish_ci,
  `lys-erfaring-3` text COLLATE utf8mb4_danish_ci,
  `lys-erfaring-4` text COLLATE utf8mb4_danish_ci,
  `lys-erfaring-5` text COLLATE utf8mb4_danish_ci,
  `lys-erfaring-6` text COLLATE utf8mb4_danish_ci,
  `voksen-samarbeid` text COLLATE utf8mb4_danish_ci,
  `voksen-erfaring` text COLLATE utf8mb4_danish_ci,
  `voksen-annet` text COLLATE utf8mb4_danish_ci,
  `sorry` varchar(20) COLLATE utf8mb4_danish_ci DEFAULT NULL,
  PRIMARY KEY (`nominasjon_arrangor_id`),
  UNIQUE KEY `nominasjon` (`nominasjon`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_danish_ci;

-- Create syntax for TABLE 'ukm_nominasjon_konferansier'
DROP TABLE IF EXISTS `ukm_nominasjon_konferansier`;
CREATE TABLE `ukm_nominasjon_konferansier` (
  `nominasjon_konferansier_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `nominasjon` int(11) NOT NULL,
  `hvorfor` text COLLATE utf8mb4_danish_ci,
  `beskrivelse` text COLLATE utf8mb4_danish_ci,
  `fil-plassering` enum('playback','url') COLLATE utf8mb4_danish_ci DEFAULT NULL,
  `fil-url` varchar(255) COLLATE utf8mb4_danish_ci DEFAULT NULL,
  PRIMARY KEY (`nominasjon_konferansier_id`),
  UNIQUE KEY `nominasjon` (`nominasjon`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_danish_ci;

-- Create syntax for TABLE 'ukm_nominasjon_media'
DROP TABLE IF EXISTS `ukm_nominasjon_media`;
CREATE TABLE `ukm_nominasjon_media` (
  `nominasjon_media_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `nominasjon` int(11) NOT NULL,
  `pri_1` varchar(20) COLLATE utf8mb4_danish_ci DEFAULT '',
  `pri_2` varchar(20) COLLATE utf8mb4_danish_ci DEFAULT '',
  `pri_3` varchar(20) COLLATE utf8mb4_danish_ci DEFAULT '',
  `annet` text COLLATE utf8mb4_danish_ci,
  `beskrivelse` text COLLATE utf8mb4_danish_ci,
  `samarbeid` text COLLATE utf8mb4_danish_ci,
  `erfaring` text COLLATE utf8mb4_danish_ci,
  PRIMARY KEY (`nominasjon_media_id`),
  UNIQUE KEY `nominasjon` (`nominasjon`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_danish_ci;

-- Create syntax for TABLE 'ukm_nominasjon_voksen'
DROP TABLE IF EXISTS `ukm_nominasjon_voksen`;
CREATE TABLE `ukm_nominasjon_voksen` (
  `nominasjon_voksen_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `nominasjon` int(6) NOT NULL,
  `navn` varchar(80) COLLATE utf8mb4_danish_ci NOT NULL DEFAULT '',
  `mobil` varchar(8) COLLATE utf8mb4_danish_ci NOT NULL DEFAULT '',
  `rolle` varchar(100) COLLATE utf8mb4_danish_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`nominasjon_voksen_id`),
  UNIQUE KEY `nominasjon` (`nominasjon`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_danish_ci;