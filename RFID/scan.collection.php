<?php

namespace UKMNorge\RFID;

require_once('orm.collection.php');
require_once('scan.class.php');
	
class ScanColl extends RFIDColl {
	const TABLE_NAME = Scan::TABLE_NAME;
	public static $models = null;
	
	public function getAllByArea( $id ) {
		self::loadByKey('area', $id);
		return self::$models;
	}
}