<?php
class samtykke_person_kategori {
	var $id = null;
	var $navn = null;
	
	public function __construct( $person ) {
		// Under 13 år (tom 12)
		if( $person->getAlderTall() < 13 ) {
			$this->id = 'u13';
			$this->navn = 'Under 13 år';
		}
		// Under 15 år (fom 13 tom 14)
		elseif( $person->getAlderTall() < 15 ) {
			$this->id = 'u15';
			$this->navn = 'Under 15 år';
		}
		// 15 og opp (fom 15)
		else {
			$this->id = '15o';
			$this->navn = 'Over 15 år';
		}	
	}
	
	public function getId() {
		return $this->id;
	}
	public function getNavn() {
		return $this->navn;
	}
	public function __toString() {
		return $this->getNavn();
	}
}