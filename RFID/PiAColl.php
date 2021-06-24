<?php

namespace UKMNorge\RFID;

require_once('UKM/Autoloader.php');

use Exception;

class PiAColl extends ORMCollection {
	const TABLE_NAME = PiA::TABLE_NAME;
	public static $models = null;
		
	public static function getAllByArea( $id ) {
		self::loadByKey('area_id', $id);
		return self::$models;
	}
	
	public static function getAreaCount( $area ) {
		return POSTGRES::getValue("SELECT COUNT(id) AS id FROM ". self::TABLE_NAME .' WHERE area_id=$1', [$area]);
	}
	
	public static function getAreaHerd( $area, $herd ) {
		try {
			$result = POSTGRES::getResults(
				'SELECT '. Person::TABLE_NAME .'.*, '. self::TABLE_NAME .'.entryTime '. self::_getAreaHerdQuery(), 
				[
					$area,
					$herd
				]
			);
		} catch( Exception $e ) {
			if( $e->getCode() == 3 ) {
				return [];
			}
			throw $e;
		}
		
		$persons = [];
		if( is_array( $result ) ) {
			foreach( $result as $personRow ) {
				$person = new Person( $personRow );
				$person->setAttr('entryTime', $personRow['entrytime'] );
				$persons[] = $person;
			}
		}
		return $persons;
	}
		
	public static function getAreaHerdCount( $area, $herd ) {
		try {
			return POSTGRES::getValue(
				'SELECT COUNT('. self::TABLE_NAME .'.id) AS count '. self::_getAreaHerdQuery(), 
				[
					$area,
					$herd
				]
			);
		} catch( Exception $e ) {
			if( $e->getCode() == 3 ) {
				return 0;
			}
			throw $e;
		}
	}
	
	private static function _getAreaHerdQuery() {
		return 'FROM '. self::TABLE_NAME .'
				JOIN '. Person::TABLE_NAME .' ON '. Person::TABLE_NAME .'.id = '. self::TABLE_NAME .'.person_id
				JOIN '. Herd::TABLE_NAME .' ON '. Herd::TABLE_NAME .'.id = '. Person::TABLE_NAME .'.herd
				WHERE '. self::TABLE_NAME .'.area_id = $1 
				AND '. Herd::TABLE_NAME .'.id = $2';
	}
}