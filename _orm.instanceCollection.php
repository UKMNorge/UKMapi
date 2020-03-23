<?php

use UKMNorge\Database\SQL\Delete;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Query;

require_once('UKM/Autoloader.php');
	
abstract class InstanceColl {
	var $parent_id = null;

	public function __construct( $parent_id ) {
		$this->parent_id = $parent_id;
	}
	
	public function getParentId() {
		return $this->parent_id;
	}
	
	public function getById( $id ) {
		$child = get_called_class();
		
		$sql = new Query(
			"SELECT * 
			FROM `". $child::getTableName() ."` 
			WHERE `id` = '#id'
			AND `". $child::PARENT_FIELD ."` = '#parent_id' ",
			[
				'id' => $id,
				'parent_id' => $this->parent_id,
			]
		);
		$row = $sql->run('array');
		
		$object_class = str_replace('Coll', '', $child);
		return new $object_class( $row );
	}
	

	public function getByKey( $key, $value ) {
		$child = get_called_class();

		$sql = new Query(
			"SELECT * 
			FROM `". $child::getTableName() ."` 
			WHERE `". $key ."` = '#id'
			AND `". $child::PARENT_FIELD ."` = '#parent_id' ",
			[
				'id' => $value,
				'parent_id' => $this->parent_id,
			]
		);
		$row = $sql->run('array');
		
		$object_class = str_replace('Coll', '', $child);
		return new $object_class( $row );
	}

	public function getAllByName() {
		$child = get_called_class();
		if( $this->models == null ) {
			$this->load();
		}
		
		$sorted = [];
		foreach( $this->models as $model ) {
			$sorted[ $model->getName().' '.$model->getId() ] = $model;
		}
		ksort( $sorted );
		return $sorted;
	}
	
	public function getCount() {
		$child = get_called_class();

		if( $this->models == null ) {
			$this->load();
		}
		return sizeof( $this->models );
	}
	
	public function load() {
		$child = get_called_class();
		$this->models = [];
		
		$sql = new Query("
			SELECT * 
			FROM `". $child::getTableName() ."`
			WHERE `". $child::PARENT_FIELD ."` = '#parent_id'
			",
			[
				'parent_id' => $this->parent_id,
			]
		);
		$res = $sql->run();

		while( $row = Query::fetch( $res ) ) {
			$object_class = str_replace('Coll', '', $child);
			$this->models[] = new $object_class( $row );
		}
	}
	
	public function loadByKey( $whereKey, $where ) {
		$child = get_called_class();
		$this->models = [];

		$sql = new Query(
			"SELECT * 
			FROM `". $child::getTableName() ."` 
			WHERE `". $whereKey ."` = '#value'
			AND `". $child::getParentField() ."` = '#parent_id'
			",
			[
				'value' => $where,
				'parent_id' => $this->parent_id,
			]
		);
		$res = $sql->run();

		while( $row = Query::fetch( $res ) ) {
			$object_class = str_replace('Coll', '', $child);
			$this->models[] = new $object_class( $row );
		}
	}
	
	public function _create( $mapped_values ) {
		$child = str_replace('InstanceColl','', get_called_class());

		$sqlIns = new Insert($child::getTableName());
		$sqlIns->add( $child::getParentField(), $this->parent_id );
		foreach( $mapped_values as $key => $val ) {
			$sqlIns->add( $key, $val );
		}
		$insert_id = $sqlIns->run();

		return new $child( $insert_id, $mapped_values['type'] );
	}

	public function _delete( $mapped_values ) {
		$child = str_replace('Coll','', get_called_class());		
		$mapped_values[ $child::getParentField() ] = $this->parent_id;

		$delete = new Delete($child::getTableName(), $mapped_values);
		return $delete->run();
	}


}