-- Create syntax for TABLE 'ukm_videresending_skjema'
CREATE TABLE `ukm_videresending_skjema` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pl_id` int(11) NOT NULL,
  `eier_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `eier_id` int(5) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create syntax for TABLE 'ukm_videresending_skjema_sporsmal'
CREATE TABLE `ukm_videresending_skjema_sporsmal` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `skjema` int(11) NOT NULL,
  `rekkefolge` int(11) NOT NULL DEFAULT '0',
  `type` enum('overskrift','kontakt','janei','kort_tekst','lang_tekst') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'overskrift',
  `tittel` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tekst` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create syntax for TABLE 'ukm_videresending_skjema_svar'
CREATE TABLE `ukm_videresending_skjema_svar` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `skjema` int(11) NOT NULL,
  `sporsmal` int(11) NOT NULL,
  `pl_fra` int(11) NOT NULL,
  `svar` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unq_svar_per_pl_sporsmal` (`sporsmal`,`pl_fra`),
  KEY `sporsmal` (`sporsmal`),
  KEY `pl_fra` (`pl_fra`),
  KEY `skjema` (`skjema`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;