<?php
require_once('UKM/sql.class.php');
	
class samtykke_prosjekt {
	
	var $id;
	var $tittel;
	var $setning;
	var $varighet;
	var $beskrivelse;
	var $hash;
	var $locked;
	
	public function __construct( $id ) {
		if( is_numeric( $id ) ) {
			$this->_load_by_id( $id );
		} elseif( is_array( $id ) ) {
			$this->_load_by_row( $id );
		} else {
			throw new Exception('Kan kun laste inn samtykke-prosjekter med numerisk ID');
		}
	}
	
	
	private function _load_by_id( $id ) {
		$sql = new SQL("
			SELECT * 
			FROM `samtykke_prosjekt`
			WHERE `id` = '#id'",
			[
				'id' => $id
			]
		);
		$res = $sql->run('array');
		
		if( $res ) {
			$this->_load_by_row( $res );
		}
	}
	
	private function _load_by_row( $row ) {
		$this->id = $row['id'];
		$this->tittel = $row['tittel'];
		$this->setning = $row['setning'];
		$this->varighet = $row['varighet'];
		$this->beskrivelse = $row['beskrivelse'];
		$this->hash = $row['hash'];
		$this->locked = $row['locked'] == 'true';
	}
	
	public function getId() {
		return $this->id;
	}
	public function setId( $id ) {
		$this->id = $id;
		return $this;
	}
	
	public function getTittel() {
		return $this->tittel;
	}
	public function setTittel( $tittel ) {
		$this->tittel = $tittel;
		return $this;
	}
	
	public function getSetning() {
		return $this->setning;
	}
	public function setSetning( $setning ) {
		$this->setning = $setning;
		return $this;
	}
	
	public function getVarighet() {
		return $this->varighet;
	}
	public function setVarighet( $varighet ) {
		$this->varighet = $varighet;
		return $this;
	}
	
	public function getBeskrivelse() {
		return $this->beskrivelse;
	}
	public function setBeskrivelse( $beskrivelse ) {
		$this->beskrivelse = $beskrivelse;
		return $this;
	}
	
	public function isLocked() {
		return $this->getLocked();
	}
	public function getLocked() {
		return $this->locked;
	}
	
	public function getHash() {
		if( null == $this->hash ) {
			$this->hash = sha1( 
				$this->getId() .'-'. 
				$this->getTittel() .'-'.
				$this->getSetning() .'-'.
				$this->getVarighet() .'-'.
				$this->getBeskrivelse()
			);
		}
		return $this->hash;
	}
	
	public function getLenkeHash() {
		return substr( $this->getHash(), 6, 10 );
	}
}