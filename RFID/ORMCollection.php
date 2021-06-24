<?php

namespace UKMNorge\RFID;

require_once('UKM/Autoloader.php');

use UKMNorge\Database\Postgres\Postgres;

abstract class ORMCollection {

	public static function getById( $id ) {
		$child = get_called_class();
		$row = Postgres::getRow("SELECT * FROM ". $child::TABLE_NAME ." WHERE id=$1", [$id]);
		
		$object_class = str_replace('Coll', '', $child);
		return new $object_class( $row );
	}
	

	public static function getByKey( $key, $value ) {
		$child = get_called_class();
		$row = Postgres::getRow("SELECT * FROM ". $child::TABLE_NAME .' WHERE '.$key.'=$1', [$value]);
		
		$object_class = str_replace('Coll', '', $child);
		return new $object_class( $row );
	}

	public static function getMatching( $key, $value ) {
		$child = get_called_class();
		$rows = Postgres::getResults("SELECT * FROM ". $child::TABLE_NAME.
			' WHERE '. $key .'=$1', [ $value ]);
		$object_class = str_replace('Coll', '', $child);
		$returns = [];
		if( is_array( $rows ) ) {
			foreach( $rows as $row ) {
				$returns[] = new $object_class( $row );
			}
		}
		return $returns;
	}

	public static function getAllByName() {
		$child = get_called_class();
		if( $child::$models == null ) {
			self::load();
		}
		
		$sorted = [];
		foreach( $child::$models as $model ) {
			$sorted[ $model->getName().' '.$model->getId() ] = $model;
		}
		ksort( $sorted );
		return $sorted;
	}
	
	public static function getCount() {
		$child = get_called_class();

		if( $child::$models == null ) {
			self::load();
		}
		return sizeof( $child::$models );
	}
	
	public static function load() {
		$child = get_called_class();
		$child::$models = [];
		
		$rows = Postgres::getResults("SELECT * FROM ". $child::TABLE_NAME);
		
		if( is_array( $rows ) ) {
			foreach( $rows as $row ) {
				$object_class = str_replace('Coll', '', $child);
				$child::$models[] = new $object_class( $row );
			}
		}
	}
	
	public static function loadByKey( $whereKey, $where ) {
		$child = get_called_class();
		$child::$models = [];
		$rows = Postgres::getResults("SELECT * FROM ". $child::TABLE_NAME .' WHERE '. $whereKey .'=$1', [ $where ]);		
		if( is_array( $rows ) ) {
			foreach( $rows as $row ) {
				$object_class = str_replace('Coll', '', $child);
				$child::$models[] = new $object_class( $row );
			}
		}
	}
}