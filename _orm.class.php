<?php

use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Update;

require_once('UKM/Autoloader.php');

abstract class ORM {
	var $attr = null;
	var $id = null;
	
	abstract public function populate( $row );
	
	public function __construct( $id_or_row ) {
		$this->attr = [];
		if( !is_null( $id_or_row ) ) {
			if( is_numeric( $id_or_row ) ) {
				$id_or_row = self::getRowFromDb( $id_or_row );
			}
			
			$this->setId( $id_or_row['id'] );
			
			$this->populate( $id_or_row );
		}
	}
	
	public function setAttr( $key, $val ) {
		$this->attr[ $key ] = $val;
		return $this;
	}
	public function getAttr( $key ) {
		if( !isset( $this->attr[ $key ] ) ) {
			return false;
		}
		return $this->attr[ $key ];
	}
	
	public static function getTableName() {
		$called_class = get_called_class();
		return $called_class::TABLE_NAME;
	}
	
	public static function getRowFromDb( $id ) {
		$sql = new Query("SELECT * FROM `". self::getTableName()."` WHERE `id` = '". $id ."'");
		$res = $sql->run('array');
		return $res;
	}
	
	
	public function setId( $id ) {
		$this->id = $id;
		return $this;
	}
	public function getId() {
		return $this->id;
	}
		
	public function _update( $mapped_values ) {
		$sqlUpd = new Update( self::getTableName(), ['id' => $this->getId()] );
		foreach( $mapped_values as $key => $val) {
			$sqlUpd->add( $key, $val );
		}
		return $sqlUpd->run();
	}

}
