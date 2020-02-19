# ************************************************************
# Sequel Pro SQL dump
# Version 4499
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: 127.0.0.1 (MySQL 5.6.31-0ubuntu0.14.04.2)
# Database: ukmdev_dev_ss3
# Generation Time: 2018-02-20 19:28:06 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table samtykke_approval
# ------------------------------------------------------------
CREATE TABLE `samtykke_approval` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `prosjekt` int(11) NOT NULL,
  `request` int(11) NOT NULL,
  `prosjekt-request` varchar(23) DEFAULT NULL,
  `alder` varchar(10) NOT NULL DEFAULT '',
  `ip` varchar(255) NOT NULL DEFAULT '',
  `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `foresatt_navn` varchar(200) DEFAULT NULL,
  `foresatt_mobil` int(8) DEFAULT NULL,
  `trenger_foresatt` enum('false','true') NOT NULL DEFAULT 'true',
  `hash` varchar(255) NOT NULL DEFAULT '',
  `hash-excerpt` varchar(10) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `prosjekt-request` (`prosjekt-request`),
  KEY `prosjekt` (`prosjekt`),
  KEY `request` (`request`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table samtykke_approval_foresatt
# ------------------------------------------------------------
CREATE TABLE `samtykke_approval_foresatt` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `approval` int(11) NOT NULL,
  `ip` varchar(200) NOT NULL DEFAULT '',
  `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `hash` varchar(255) NOT NULL DEFAULT '',
  `hash-excerpt` varchar(10) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `approval` (`approval`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table samtykke_prosjekt
# ------------------------------------------------------------
CREATE TABLE `samtykke_prosjekt` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tittel` varchar(150) NOT NULL DEFAULT '',
  `setning` text,
  `varighet` varchar(150) DEFAULT NULL,
  `beskrivelse` text,
  `locked` enum('false','true') NOT NULL DEFAULT 'false',
  `hash` varchar(255) DEFAULT NULL,
  `hash-excerpt` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tittel` (`tittel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table samtykke_request
# ------------------------------------------------------------
CREATE TABLE `samtykke_request` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `prosjekt` int(11) NOT NULL,
  `fornavn` varchar(150) NOT NULL DEFAULT '',
  `etternavn` varchar(150) DEFAULT NULL,
  `mobil` int(8) NOT NULL,
  `melding` text NOT NULL,
  `lenker` text NOT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `hash` varchar(255) NOT NULL DEFAULT '',
  `hash-excerpt` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;




/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
