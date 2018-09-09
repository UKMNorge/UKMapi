<?php

require_once('UKM/sql.class.php');
	
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
		
		$sql = new SQL(
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

		$sql = new SQL(
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
		
		$sql = new SQL("
			SELECT * 
			FROM `". $child::getTableName() ."`
			WHERE `". $child::PARENT_FIELD ."` = '#parent_id'
			",
			[
				'parent_id' => $this->parent_id,
			]
		);
		$res = $sql->run();

		while( $row = SQL::fetch( $res ) ) {
			$object_class = str_replace('Coll', '', $child);
			$this->models[] = new $object_class( $row );
		}
	}
	
	public function loadByKey( $whereKey, $where ) {
		$child = get_called_class();
		$this->models = [];

		$sql = new SQL(
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

		while( $row = SQL::fetch( $res ) ) {
			$object_class = str_replace('Coll', '', $child);
			$this->models[] = new $object_class( $row );
		}
	}
	
	public function _create( $mapped_values ) {
		$child = str_replace('InstanceColl','', get_called_class());

		$sqlIns = new SQLins($child::getTableName());
		$sqlIns->add( $child::getParentField(), $this->parent_id );
		foreach( $mapped_values as $key => $val ) {
			$sqlIns->add( $key, $val );
		}
		$res = $sqlIns->run();

		return new $child( $sqlIns->insid(), $mapped_values['type'] );
	}

	public function _delete( $mapped_values ) {
		$child = str_replace('Coll','', get_called_class());		
		$mapped_values[ $child::getParentField() ] = $this->parent_id;

		$sqlDel = new SQLdel($child::getTableName(), $mapped_values);
		return $sqlDel->run();
	}


}