<?php

namespace UKMNorge\Innslag\Nominasjon;

class Konferansier extends Nominasjon {
	
	private $hvorfor;
	private $beskrivelse;
	private $plassering;
	private $fil_url;
	
	
	public function _loadByRow( $row ) {
		parent::_loadByRow( $row );
		$this->setHvorfor( $row['hvorfor'] );
		$this->setBeskrivelse( $row['beskrivelse'] );
		$this->setFilPlassering( $row['fil-plassering'] );
		$this->setFilUrl( $row['fil-url'] );

        $this->calcHarSkjemaStatus();
    }
    
    /**
     * Beregn om deltaker- eller voksen-skjema er utfylt
     *
     * @return self
     */
    public function calcHarSkjemaStatus() {
        if( !empty( $this->getHvorfor() ) && !empty( $this->getBeskrivelse() ) ) {
			$this->setHarVoksenskjema( true );
        }
        return $this;
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
