<?php

namespace UKMNorge\RFID;

require_once(UKMRFID .'/models/orm.collection.php');
require_once(UKMRFID .'/models/pia.class.php');
	
class PiAColl extends RFIDColl {
	const TABLE_NAME = PiA::TABLE_NAME;
	public static $models = null;
		
	public function getAllByArea( $id ) {
		self::load('area_id', $id);
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