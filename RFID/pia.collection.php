<?php

namespace UKMNorge\RFID;

require_once('orm.collection.php');
require_once('pia.class.php');
	
class PiAColl extends RFIDColl {
	const TABLE_NAME = PiA::TABLE_NAME;
	public static $models = null;
		
	public function getAllByArea( $id ) {
		self::loadByKey('area_id', $id);
		return self::$models;
	}
	
	public static function getAreaCount( $area ) {
		return POSTGRES::getValue("SELECT COUNT(id) AS id FROM ". self::TABLE_NAME .' WHERE area_id=$1', [$area]);
	}
}