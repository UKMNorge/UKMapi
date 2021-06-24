<?php

namespace UKMNorge\RFID;

require_once('UKM/Autoloader.php');

class MonitorAccessColl extends ORMCollection {
	const TABLE_NAME = MonitorAccess::TABLE_NAME;
	public static $models = null;

	public function getForSessionId( $session_id ) {
		return parent::getMatching("session_id", $session_id);
	}
	
}
