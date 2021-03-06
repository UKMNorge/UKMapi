<?php

namespace UKMNorge\Samtykke;

class Foresatt {
	var $navn = null;
	var $mobil = null;
	var $status = null;

	public function __construct( $navn, $mobil, $status, $status_timestamp, $status_ip ) {
		$this->navn = $navn;
		$this->mobil = $mobil;
		$this->status = new Status( $status, $status_timestamp, $status_ip );
	}
	
	public function getNavn() {
		return $this->navn;
	}
	public function getMobil() {
		return $this->mobil;
	}
	public function getStatus() {
		return $this->status;
	}
	
	public function har() {
		return null !== $this->navn && !empty( $this->navn );
	}
}