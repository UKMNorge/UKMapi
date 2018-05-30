<?php

namespace UKMNorge\RFID;
	
require_once('orm.class.php');
	
class Scanner extends RFIDORM {
	const TABLE_NAME = 'scanner';
	
	var $guid = null;
	var $registerTime = null;
	var $ip = null;
	var $verified = false;

	var $name = null;
	var $direction = null;
	var $area = null;
	var $area_id = null;
	
	
	public function populate( $row ) {
		$this->setGUID( $row['guid'] );
		$this->setIp( $row['ip'] );
		$this->setVerified( $row['verified'] );
		$this->setName( $row['name'] );
		$this->setDirection( $row['direction'] );
		$this->setAreaId( $row['area'] );
		$this->registerTime = $row['registerTime'];
	}
	
	
	public function setGUID( $guid ) {
		$this->guid = $guid;
		return $this;
	}
	public function getGUID() {
		return $this->guid;
	}
	
	public function getRegisterTime() {
		return $this->registerTime;
	}
	
	public function setIp( $ip ) {
		$this->ip = $ip;
		return $this;
	}
	public function getIp() {
		return $this->ip;
	}
	
	public function isVerified() {
		return $this->getVerified();
	}
	
	public function setVerified( $verified ) {
		$this->verified = $verified;
		return $this;
	}
	public function getVerified() {
		return $this->verified;
	}
	
	public function setName( $name ) {
		$this->name = $name;
		return $this;
	}
	public function getName() {
		return $this->name;
	}
	
	public function setDirection( $direction ) {
		$this->direction = $direction;
		return $this;
	}
	public function getDirection() {
		return $this->direction;
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
	
	
	public static function create( $guid, $ip ) {
		$object = self::_create( [
			'guid' => $guid,
			'ip' => $ip,
			]
		);
		return $object;
	}
	
	public function save() {
		self::_update(
			[
				'area' => $this->getAreaId(),
				'direction' => $this->getDirection(),
				'name' => $this->getName(),
				'verified' => $this->getVerified(),
			]
		);
	}
}