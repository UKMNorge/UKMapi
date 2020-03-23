<?php

use UKMNorge\Database\SQL\Delete;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Query;

require_once('UKM/Autoloader.php');
	
abstract class Coll {

	public static function getById( $id ) {
		$child = get_called_class();
		
		$sql = new Query(
			"SELECT * FROM `". $child::TABLE_NAME ."` WHERE `id` = '#id' ",
			['id' => $id]
		);
		$row = $sql->run('array');
		
		$object_class = str_replace('Coll', '', $child);
		return new $object_class( $row );
	}
	

	public static function getByKey( $key, $value ) {
		$child = get_called_class();

		$sql = new Query(
			"SELECT * FROM `". $child::TABLE_NAME ."` WHERE `". $key ."` = '#id' ",
			['id' => $value]
		);
		$row = $sql->run('array');
		
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
		
		$sql = new Query("SELECT * FROM `". $child::TABLE_NAME ."`");
		$res = $sql->run();

		while( $row = Query::fetch( $res ) ) {
			$object_class = str_replace('Coll', '', $child);
			$child::$models[] = new $object_class( $row );
		}
	}
	
	public static function loadByKey( $whereKey, $where ) {
		$child = get_called_class();
		$child::$models = [];

		$sql = new Query(
			"SELECT * FROM `". $child::TABLE_NAME ."` WHERE `". $whereKey ."` = '#value'",
			['value' => $where]
		);
		$res = $sql->run();

		while( $row = Query::fetch( $res ) ) {
			$object_class = str_replace('Coll', '', $child);
			$child::$models[] = new $object_class( $row );
		}
	}
	
	public static function _create( $mapped_values ) {
		$child = str_replace('Coll','', get_called_class());

		$sqlIns = new Insert($child::getTableName());
		foreach( $mapped_values as $key => $val ) {
			$sqlIns->add( $key, $val );
		}
		$insert_id = $sqlIns->run();

		return new $child( $insert_id );
	}

	public static function _delete( $mapped_values ) {
		$child = str_replace('Coll','', get_called_class());

		$delete = new Delete($child::getTableName(), $mapped_values);
		return $delete->run();
	}


}