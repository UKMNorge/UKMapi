<?php

namespace UKMNorge\RFID;

use Exception;

require_once('UKM/Autoloader.php');
	
class Area extends ORM {
	const TABLE_NAME = 'area';
	
	var $name = 'OBS: Ikke tilknyttet område';
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
		return ScannerColl::getAllByArea( $this->getId() );
	}
	
	public function getPiACount() {
		return $this->getPersonsInAreaCount();
	}
	public function getPersonsInAreaCount() {
		return PiAColl::getAreaCount( $this->getId() );
	}
	
	public function getPersonsInArea() {
		throw new Exception('Mangler implementering');
	}
}