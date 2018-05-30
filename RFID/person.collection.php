<?php

namespace UKMNorge\RFID;

require_once(UKMRFID .'/models/orm.collection.php');
require_once(UKMRFID .'/models/person.class.php');
	
class PersonColl extends RFIDColl {
	const TABLE_NAME = Person::TABLE_NAME;
	public static $models = null;
	
	public static function getByForeignId( $id ) {
		$row = POSTGRES::getRow("SELECT * FROM ". self::TABLE_NAME ." WHERE foreign_id=$1", [$id]);
		
		$object_class = str_replace('Coll', '', get_called_class());
		return new $object_class( $row );
	}
}