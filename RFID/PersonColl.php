<?php

namespace UKMNorge\RFID;

require_once('UKM/Autoloader.php');

use Exception;
	
class PersonColl extends ORMCollection {
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
		$result = POSTGRES::getResults("SELECT * FROM ". self::TABLE_NAME, array());

		$object_class = str_replace('Coll', '', get_called_class());

		$persons = array();
		foreach($result as $row) {
			$persons[] = new $object_class( $row );
		}
		return $persons;
	}

	public static function countMatching($firstname = "", $lastname = "", $phone = "") {
		$row_count = POSTGRES::getResults( 
			"SELECT COUNT('rfid') FROM ". self::TABLE_NAME ." WHERE 
			CAST(phone AS TEXT) LIKE $1 || '%' AND 
			last_name LIKE $2 || '%' AND 
			first_name LIKE $3 || '%'", [$phone, $lastname, $firstname]);
		return $row_count;
	}
}
