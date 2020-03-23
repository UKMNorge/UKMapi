<?php

namespace UKMNorge\RFID;

require_once('UKM/Autoloader.php');

class ScanColl extends ORMColl {
	const TABLE_NAME = Scan::TABLE_NAME;
	public static $models = null;
	
	public function getAllByArea( $id ) {
		self::loadByKey('area', $id);
		return self::$models;
	}
}