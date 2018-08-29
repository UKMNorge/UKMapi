<?php
require_once('UKM/nominasjon.class.php');

class nominasjon_konferansier extends nominasjon {
	
	private $hvorfor;
	private $beskrivelse;
	private $fil_plassering;
	private $fil_url;
	
	
	public function _loadByRow( $row ) {
		parent::_loadByRow( $row );
		$this->setHvorfor( $row['hvorfor'] );
		$this->setBeskrivelse( $row['beskrivelse'] );
		$this->setFilPlassering( $row['fil-plassering'] );
		$this->setFilUrl( $row['fil-url'] );

		if( !empty( $row['hvorfor'] ) && !empty( $row['beskrivelse'] ) ) {
			$this->setHarVoksenskjema( true );
		}
	}
	
	public function getHvorfor() {
		return $this->hvorfor;
	}
	public function setHvorfor( $hvorfor ) {
		$this->hvorfor = $hvorfor;
		return $this;
	}
	
	public function getBeskrivelse() {
		return $this->beskrivelse;
	}
	public function setBeskrivelse( $beskrivelse ) {
		$this->beskrivelse = $beskrivelse;
		return $this;
	}
	
	public function setFilPlassering( $plassering ) {
		$this->plassering = $plassering;
		return $this;
	}
	public function getFilPlassering() {
		return $this->plassering;
	}
	
	public function setFilUrl( $url ) {
		$this->fil_url = $url;
		return $this;
	}
	public function getFilUrl() {
		return $this->fil_url;
	}
}
