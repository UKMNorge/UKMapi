<?php
	
namespace UKMNorge\RFID;

require_once('UKM/postgres.class.php');

abstract class RFIDORM {
	var $attr = null;
	var $id = null;
	
	abstract public function populate( $row );
	
	public function __construct( $id_or_row ) {
		$this->attr = [];
		if( !is_null( $id_or_row ) && !$dummy ) {
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
		return POSTGRES::getRow("SELECT * FROM ". self::getTableName()." WHERE id=". $id);
	}
	
	
	public function setId( $id ) {
		$this->id = $id;
		return $this;
	}
	public function getId() {
		return $this->id;
	}
	
	public static function _create( $mapped_values ) {
		$query = 'INSERT INTO '. self::getTableName() .' (';

		// KEYS
		$count = 0;
		foreach( $mapped_values as $key => $val ) {
			$count++;
			$query .= $key;
			if( $count < sizeof( $mapped_values ) ) {
				$query .= ', ';
			}
		}
		$query .= ')';
		
		// VALUES
		$query .= ' VALUES (';
		$values = [];
		$count = 0;
		foreach( $mapped_values as $key => $val ) {
			$count++;
			$query .= '$'. $count;
			if( $count < sizeof( $mapped_values ) ) {
				$query .= ', ';
			}
			$values[] = $val;
		}
		$query .= ')';
		
		$object_id = POSTGRES::insert( $query, $values );
		
		$called_class = get_called_class();
		return new $called_class( $object_id );
	}
	
	public function _update( $mapped_values ) {
		$query = 'UPDATE '. self::getTableName() .' SET (';

		// KEYS
		$count = 1;
		foreach( $mapped_values as $key => $val ) {
			$count++;
			$query .= $key;
			if( $count < sizeof( $mapped_values )+1 ) {
				$query .= ', ';
			}
		}
		$query .= ')';
		
		// VALUES
		$query .= ' = (';
		$values = [ $this->getId() ];
		$count = 1;
		foreach( $mapped_values as $key => $val ) {
			$count++;
			$query .= '$'. $count;
			if( $count < sizeof( $mapped_values )+1 ) {
				$query .= ', ';
			}
			$values[] = $val;
		}
		$query .= ')
		WHERE id = $1';
		
		$result = POSTGRES::update( $query, $values );
	}

}
