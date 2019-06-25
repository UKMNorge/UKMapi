<?php

namespace UKMNorge\RFID;
use Exception;

require_once('orm.class.php');
	
class MonitorAccess extends RFIDORM {
	const TABLE_NAME = 'monitor_access';
	
	var $ID = null;
	var $session_id = null;
	var $scanner_id = null;

	
	public function populate( $row ) {
		$this->setID( $row['id'] );
		$this->setSessionId( $row['session_id'] );
		$this->setScannerId( $row['scanner_id'] );
	}
	
	
	public function setID( $ID ) {
		$this->ID = $ID;
		return $this;
	}
	public function getID() {
		return $this->ID;
	}
	
	public function getSessionId() {
		return $this->session_id;
	}
	
	public function setSessionId( $session_id ) {
		$this->session_id = $session_id;
		return $this;
	}

	public function getScannerId() {
		return $this->scanner_id;
	}

	public function setScannerId( $scanner_id) {
		$this->scanner_id = $scanner_id;
		return $this;
	}
	
	public static function create( $session_id, $scanner_id ) {
		$object = self::_create( [
			'session_id' => $guid,
			'scanner_id' => $ip,
			]
		);
		return $object;
	}
	
	public function save() {
		self::_update(
			[
				'session_id' => $this->getSessionId(),
				'scanner_id' => $this->getScannerId(),
			]
		);
	}
}