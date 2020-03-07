<?php

namespace UKMNorge\Innslag\Nominasjon;

class Media extends Nominasjon {
	
	private $pri1;
	private $pri2;
	private $pri3;
	private $annet;
	private $beskrivelse;
	private $samarbeid;
	private $erfaring;
	
	public function _loadByRow( $row ) {
		parent::_loadByRow( $row );
		$this->setPri1( $row['pri_1'] );
		$this->setPri2( $row['pri_2'] );
		$this->setPri3( $row['pri_3'] );
		$this->setAnnet( $row['annet'] );
		$this->setBeskrivelse( $row['beskrivelse'] );
		$this->setSamarbeid( $row['samarbeid'] );
		$this->setErfaring( $row['erfaring'] );
        
        $this->calcHarSkjemaStatus();
    }
        
    /**
     * Beregn om deltaker- eller voksen-skjema er utfylt
     *
     * @return self
     */
    public function calcHarSkjemaStatus() {

        if( !empty( $this->getBeskrivelse() ) ) {
			$this->setHarDeltakerskjema( true );
		}
		if( !empty( $this->getSamarbeid() ) && !empty( $this->getErfaring() ) ) {
			$this->setHarVoksenskjema( true );
        }
        
        return $this;
    }
	
	public function getPri1(){
		return $this->pri1;
	}

	public function setPri1($pri1){
		$this->pri1 = $pri1;
		return $this;
	}

	public function getPri2(){
		return $this->pri2;
	}

	public function setPri2($pri2){
		$this->pri2 = $pri2;
		return $this;
	}

	public function getPri3(){
		return $this->pri3;
	}

	public function setPri3($pri3){
		$this->pri3 = $pri3;
		return $this;
	}

	public function getAnnet(){
		return $this->annet;
	}

	public function setAnnet($annet){
		$this->annet = $annet;
		return $this;
	}

	public function getBeskrivelse(){
		return $this->beskrivelse;
	}

	public function setBeskrivelse($beskrivelse){
		$this->beskrivelse = $beskrivelse;
		return $this;
	}
	
	public function getSamarbeid() {
		return $this->samarbeid;
	}
	public function setSamarbeid( $samarbeid ) {
		$this->samarbeid = $samarbeid;
		return $this;
	}
	
	public function getErfaring() {
		return $this->erfaring;
	}
	public function setErfaring( $erfaring ) {
		$this->erfaring = $erfaring;
		return $this;
	}
}