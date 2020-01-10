CREATE TABLE `ukm_foresatte_info` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `p_id` int(11) NOT NULL,
  `foresatte_navn` varchar(255) COLLATE utf8mb4_danish_ci NOT NULL,
  `foresatte_mobil` int(8) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_danish_ci;
