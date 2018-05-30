<?php

namespace UKMNorge\RFID;
	
require_once('orm.class.php');
	
class Herd extends RFIDORM {
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
}