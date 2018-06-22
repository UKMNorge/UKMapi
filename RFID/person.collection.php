<?php

namespace UKMNorge\RFID;

require_once('orm.collection.php');
require_once('person.class.php');
	
class PersonColl extends RFIDColl {
	const TABLE_NAME = Person::TABLE_NAME;
	public static $models = null;
	
	public static function getByRFID( $rfid ) {
		$row = POSTGRES::getRow("SELECT * FROM ". self::TABLE_NAME ." WHERE rfid=$1", [$rfid]);
		
		$object_class = str_replace('Coll', '', get_called_class());
		return new $object_class( $row );
	}
	
	public static function hasRFID( $rfid ) {
		try {
			$row = POSTGRES::getRow("SELECT * FROM ". self::TABLE_NAME ." WHERE rfid=$1", [$rfid]);
			return true;
		} catch( Exception $e ) {
			return false;
		}
	}
	
	public static function getByForeignId( $id ) {
		$row = POSTGRES::getRow("SELECT * FROM ". self::TABLE_NAME ." WHERE foreign_id=$1", [$id]);
		
		$object_class = str_replace('Coll', '', get_called_class());
		return new $object_class( $row );
	}
	
	public static function hasForeignId( $id ) {
		try {
			$row = POSTGRES::getRow("SELECT * FROM ". self::TABLE_NAME ." WHERE foreign_id=$1", [$id]);
			return true;
		} catch( Exception $e ) {
			return false;
		}
	}

	public static function getAll( ) {
		$row = POSTGRES::getRow("SELECT * FROM ". self::TABLE_NAME, array());

		$object_class = str_replace('Coll', '', get_called_class());
		return new $object_class( $row );
	}
}
