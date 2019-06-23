<?php

namespace UKMNorge\RFID;

require_once('orm.collection.php');
require_once('scanner.class.php');
	
class ScannerColl extends RFIDColl {
	const TABLE_NAME = AccessMonitor::TABLE_NAME;
	public static $models = null;

	public function getForSessionId( $session_id ) {
		return parent::getByKey("session_id", $session_id);
	}
	
}
