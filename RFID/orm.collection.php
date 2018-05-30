<?php

namespace UKMNorge\RFID;	
	
abstract class RFIDColl {

	public static function getById( $id ) {
		$child = get_called_class();
		$row = POSTGRES::getRow("SELECT * FROM ". $child::TABLE_NAME ." WHERE id=$1", [$id]);
		
		$object_class = str_replace('Coll', '', $child);
		return new $object_class( $row );
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
		
		$rows = POSTGRES::getResults("SELECT * FROM ". $child::TABLE_NAME);
		
		if( is_array( $rows ) ) {
			foreach( $rows as $row ) {
				$object_class = str_replace('Coll', '', $child);
				$child::$models[] = new $object_class( $row );
			}
		}
	}
}