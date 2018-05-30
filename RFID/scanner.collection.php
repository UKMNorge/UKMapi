<?php

namespace UKMNorge\RFID;

require_once('orm.collection.php');
require_once('scanner.class.php');
	
class ScannerColl extends RFIDColl {
	const TABLE_NAME = Scanner::TABLE_NAME;
	public static $models = null;

	public function getByGUID( $guid ) {
		return parent::getByKey("GUID", $guid);
	}
}
