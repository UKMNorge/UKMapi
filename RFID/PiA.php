<?php

namespace UKMNorge\RFID;

require_once('UKM/Autoloader.php');
	
class PiA extends ORM {
	const TABLE_NAME = 'person_in_area';
	
	var $area_id = null;
	var $person_id = null;
	var $entryTime = null;
	var $area = null;
	var $person = null;
	
	public function populate( $row ) {
		$this->area_id = $row['area_id'];
		$this->person_id = $row['person_id'];
		$this->entryTime = $row['entryTime'];
	}
		
	public function getPersonId() {
		return $this->person_id;
	}
	
	public function getPerson() {
		if( null == $this->person ) {
			$this->person = PersonColl::getById( $this->getPersonId() );
		}
		return $this->person;
	}
	
	public function getAreaId() {
		return $this->area_id;
	}

	public function getArea() {
		if( $this->area == null ) {
			$this->area = AreaColl::getById( $this->getAreaId() );
		}
		return $this->area;
	}


}