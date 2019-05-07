<?php

namespace UKMNorge\RFID;
	
require_once('orm.class.php');
	
class Scan extends RFIDORM {
	const TABLE_NAME = 'scan';
	
	var $rfid = null;
	var $area = null;
	var $direction = null;
	var $timestamp = null;
	
	public function populate( $row ) {
		$this->setRFID( $row['rfid'] );
		$this->setAreaId( $row['area'] );
		$this->setDirection( $row['direction'] );
		$this->timestamp = $row['timestamp'];
	}
	
	public function setRFID( $rfid ) {
		$this->rfid = $rfid;
		return $this;
	}
	public function getRFID() {
		return $this->rfid;
	}
	
	public function setAreaId( $area ) {
		if( is_object( $area ) ) {
			$area = $area->getId();
		}
		$this->area_id = $area;
		return $this;
	}
	public function getAreaId() {
		return $this->area_id;
	}

	public function getArea() {
		require_once('area.collection.php');

		if( $this->area == null ) {
			$this->area = AreaColl::getById( $this->getAreaId() );
		}
		return $this->area;
	}
	
	public function setDirection( $direction ) {
		$this->direction = $direction;
		return $this;
	}
	public function getDirection() {
		return $this->direction;
	}
	
	public function getTimestamp() {
		return $this->timestamp;
	}	
	
	public static function create( $rfid, $direction, $scanner ) {
		$object = self::_create( [
			'rfid' => $rfid,
			'direction' => $direction,
			'area' => $scanner->getAreaId(),
			'scanner' => $scanner->getId(),
			]
		);
		return $object;
	}
}