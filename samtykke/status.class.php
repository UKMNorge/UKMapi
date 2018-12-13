<?php
class samtykke_person_status {
	var $id = null;
	var $timestamp = null;
	var $ip = null;
	
	public function __construct( $id, $timestamp, $ip ) {
		$this->id = $id;
		$this->timestamp = new samtykke_timestamp( $timestamp );
		$this->ip = $ip;
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function getTimestamp() {
		return $this->timestamp;
	}
	
	public function getIp() {
		return $this->ip;
	}
	
	public function getNavn() {
		switch( $this->getId() ) {
			case 'godkjent':
				return 'Godtatt';
			case 'ikke_godkjent':
				return 'Ikke godtatt!';
			case 'ikke_svart':
				return 'Har ikke svart';
			case 'ikke_sett':
				return 'Har ikke sett informasjonen';
			case 'ikke_sendt':
				return 'IKKE SENDT!';
		}
		return 'UKJENT FEIL';
	}
	
	public function getLevel() {
		switch( $this->getId() ) {
			case 'godkjent':
				return 'success';

			case 'ikke_sendt':
			case 'ikke_godkjent':
				return 'danger';

			case 'ikke_svart':
				return 'info';

			case 'ikke_sett':
				return 'warning';
		}
	}
	
	public function __toString() {
		return $this->getNavn();
	}
}