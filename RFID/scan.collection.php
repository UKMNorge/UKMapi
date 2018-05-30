<?php

namespace UKMNorge\RFID;

require_once('orm.collection.php');
require_once('scan.class.php');
	
class ScanColl extends RFIDColl {
	const TABLE_NAME = Scan::TABLE_NAME;
	public static $models = null;
	
	public function getAllByArea( $id ) {
		self::load('area', $id);
		return self::$models;
	}
	
	public static function load( $whereKey, $where ) {
		self::$models = [];
		
		$rows = POSTGRES::getResults("SELECT * FROM ". self::TABLE_NAME .' WHERE '. $whereKey .'=$1', [ $where ]);
		
		if( is_array( $rows ) ) {
			foreach( $rows as $row ) {
				$object_class = str_replace('Coll', '', get_called_class());
				self::$models[] = new $object_class( $row );
			}
		}
	}

}