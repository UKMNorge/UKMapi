<?php

namespace UKMNorge\RFID;

require_once('orm.class.php');
	
class Area extends RFIDORM {
	const TABLE_NAME = 'area';
	
	var $name = null;
	var $capacity = null;
	
	public function populate( $row ) {
		$this->setName( $row['name'] );
		$this->setCapacity( $row['capacity'] );
	}
	
	public function setName( $name ) {
		$this->name = $name;
		return $this;
	}
	public function getName() {
		return $this->name;
	}
	
	public function setCapacity( $capacity ) {
		$this->capacity = $capacity;
		return $this;
	}
	public function getCapacity() {
		return $this->capacity;
	}
	
	public function __toString() {
		return $this->getName();
	}
	
	
	public static function create( $name, $capacity ) {
		$object = self::_create( [
			'name' => $name,
			'capacity' => $capacity,
			]
		);
		return $object;
	}
	
	public function getScanners() {
		require_once('scanner.collection.php');
		return ScannerColl::getAllByArea( $this->getId() );
	}
	
	public function getPersonsInAreaCount() {
		require_once('pia.collection.php');
		return PiAColl::getAreaCount( $this->getId() );
	}
	
	public function getPersonsInArea() {
		throw new Exception('Mangler implementering');
	}
}