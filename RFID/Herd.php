<?php

namespace UKMNorge\RFID;

use Exception;

require_once('UKM/Autoloader.php');
	
class Herd extends ORM {
	const TABLE_NAME = 'herd';
	
	var $name = null;
	var $foreign_id = null;
	
	public function populate( $row ) {
		$this->setName( $row['name'] );
		$this->setForeignId( $row['foreign_id'] );
	}
	
	public function setName( $name ) {
		$this->name = $name;
		return $this;
	}
	public function getName() {
		return $this->name;
	}
	
	public function setForeignId( $foreign_id ) {
		$this->foreign_id = $foreign_id;
		return $this;
	}
	public function getForeignId() {
		return $this->foreign_id;
	}
	
	
	public static function create( $name, $foreign_id ) {
		$object = self::_create( [
			'name' => $name,
			'foreign_id' => $foreign_id,
			]
		);
		return $object;
	}
	
	public function getHerdMembersInArea( $area ) {
		return PiAColl::getAreaHerd( $area, $this->getId() );
	}
	
	public function getHerdMembersInAreaCount( $area ) {
		return PiAColl::getAreaHerdCount( $area, $this->getId() );
	}
	
	public function getPersonsInArea() {
		throw new Exception('Mangler implementering');
	}

}