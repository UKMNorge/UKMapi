<?php

class tv_v2 {
	public static function hasActiveCache() {
		return !empty( tv_v2::getCacheAddr() );
	}
	
	public static function getCacheAddr() {
		$sql = new SQL("SELECT `ip`
			FROM `ukm_tv_caches_caches`
			WHERE `last_heartbeat` >= NOW() - INTERVAL 3 MINUTE
				AND `status` = 'ok' AND `deactivated` = 0
			ORDER BY RAND()
			LIMIT 1");
		return $sql->run('field', 'ip');
	}
}
require_once('UKM/v1_tv.class.php');
