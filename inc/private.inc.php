<?php
function UKM_private($die=false) {
$ips = array();
#	$ips[] = '80.212.20.108';		# Trudvang hotell
	$ips[] = '81.0.146.162';		# Kontor fjordgata
	$ips[] = '81.0.146.164'; 		# Kontor fjordgata
	$ips[] = '188.113.121.10';		# Marius hjemme
#	$ips[] = '84.48.63.192';		# UREDD
#	$ips[] = '77.106.178.238';		# Radisson Lillehammer Hotel
#	$ips[] = '176.11.16.231';		# Marius på togtur
#	$ips[] = '193.214.121.145';		# OSL Airport
#	$ips[] = '153.110.202.3';		# ISAK
#	$ips[] = '89.10.238.105'; 		# Frank Mandal
	$ips[] = '194.19.111.162';		# Trådløse Trondheim
	
	if(!in_array($_SERVER['REMOTE_ADDR'], $ips)) {
		if($die)
	 		die('Ingen tilgang');
	 	return false;
	}
	return true;
}
?>