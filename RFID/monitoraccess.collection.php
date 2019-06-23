<?php

namespace UKMNorge\RFID;

require_once('orm.collection.php');
require_once('scanner.class.php');
	
class MonitorAccessColl extends RFIDColl {
	const TABLE_NAME = MonitorAccess::TABLE_NAME;
	public static $models = null;

	public function getForSessionId( $session_id ) {
		return parent::getByKey("session_id", $session_id);
	}
	
}
