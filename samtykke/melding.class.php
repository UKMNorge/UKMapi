<?php

class samtykke_melding {
	
	var $id = null;
	var $samtykke_id = null;
	var $mobil = null;
	var $type = null;
	var $timestamp = null;
	var $melding = null;
	
	public function __construct( $row ) {
		$this->_populate( $row );
	}
	
	private function _populate( $row ) {
		$this->id = $row['id'];
		$this->samtykke_id = $row['samtykke_id'];
		$this->mobil = $row['mobil'];
		$this->type = $row['type'];
		$this->timestamp = new samtykke_timestamp( $row['timestamp'] );
		$this->melding = $row['melding'];
	}
	
	public function getId() {
		return $this->id;
	}
	public function getSamtykkeId() {
		return $this->samtykke_id;
	}
	public function getMobil() {
		return $this->mobil;
	}
	public function getType() {
		return $this->type;
	}
	public function getTimestamp() {
		return $this->timestamp;
	}
	public function getMelding() {
		return $this->melding;
	}

}