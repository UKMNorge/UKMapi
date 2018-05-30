<?php

namespace UKMNorge\RFID;

require_once(UKMRFID .'/models/orm.class.php');
	
class Person extends RFIDORM {
	const TABLE_NAME = 'person';
	
	var $herd_id = null;
	var $herd = null;
	var $first_name = null;
	var $last_name = null;
	var $phone = null;
	var $rfid = null;
	var $foreign_id = null;
	
	public function populate( $row ) {
		$this->setFirstName( $row['first_name'] );
		$this->setLastName( $row['last_name'] );
		$this->setPhone( $row['phone'] );
		$this->setRFID( $row['rfid'] );
		$this->setForeignId( $row['foreign_id'] );
		$this->setHerd( $row['herd'] );
	}
	
	public function setFirstName( $first_name ) {
		$this->first_name = $first_name;
		return $this;
	}
	public function getFirstName() {
		return $this->first_name;
	}
	
	public function setLastName( $last_name ) {
		$this->last_name = $last_name;
		return $this;
	}
	public function getLastName() {
		return $this->last_name;
	}
	
	public function setPhone( $phone ) {
		$this->phone = $phone;
		return $this;
	}
	public function getPhone() {
		return $this->phone;
	}
	
	public function setRFID( $rfid ) {
		$this->rfid = $rfid;
		return $this;
	}
	public function getRFID() {
		return $this->rfid;
	}
	
	public function setForeignId( $foreign_id ) {
		$this->foreign_id = $foreign_id;
		return $this;
	}
	public function getForeignId() {
		return $this->foreign_id;
	}
	
	public function setHerd( $herd_id ) {
		$this->herd_id = $herd_id;
		return $this;
	}
	public function getHerdId() {
		return $this->herd_id;
	}
	
	public function getHerd() {
		if( $this->herd == null ) {
			require_once(UKMRFID .'/models/herd.collection.php');
			$this->herd = HerdColl::getById( $this->getHerdId() );
		}
		return $this->herd;
	}
	
	
	public function __toString() {
		return $this->getFirstName() .' '. $this->getLastName();
	}
	
	
	public static function create( $first_name, $last_name, $phone, $rfid, $herd, $foreign_id=null ) {
		$object = self::_create( [
			'first_name' => $first_name,
			'last_name' => $last_name,
			'phone' => $phone,
			'rfid' => $rfid,
			'herd' => $herd,
			'foreign_id' => $foreign_id,
			]
		);
		return $object;
	}
	
	public function save() {
		self::_update([
			'first_name' => $this->getFirstName(),
			'last_name' => $this->getLastName(),
			'phone' => $this->getPhone(),
			'rfid' => $this->getRFID(),
			'herd' => $this->getHerdId(),
		]);
	}
}