<?php

namespace UKMNorge\RFID;
	
class ScannerColl extends ORMCollection {
	const TABLE_NAME = Scanner::TABLE_NAME;
	public static $models = null;

	public function getByGUID( $guid ) {
		return parent::getByKey("GUID", $guid);
	}

	public static function getAllByArea( $id ) {
		self::loadByKey('area', $id);
		return self::$models;
	}
}
