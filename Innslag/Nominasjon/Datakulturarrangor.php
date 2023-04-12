<?php

namespace UKMNorge\Innslag\Nominasjon;

class Datakulturarrangor extends Nominasjon {
	
	var $lansupport = false;
   	var $streamingtekniker = false;
    var $moderator = false;
   	var $utver = false;	
	
	var $voksen_annet;
	var $voksen_efaring;
	var $voksen_samarbeid;
	
	var $sorry;
	
	public function _loadByRow( $row ) {
		parent::_loadByRow( $row );
		
		$this->setLansupport( $row['lansupport'] == 'true' );
		$this->setStreamingtekniker( $row['streamingtekniker'] == 'true' );
		$this->setModerator( $row['moderator'] == 'true' );
		$this->setUtover( $row['utver'] == 'true' );
		
		$this->setVoksenSamarbeid( $row['voksensamarbeid'] );
		$this->setVoksenErfaring( $row['voksenerfaring'] );
		$this->setVoksenAnnet( $row['voksenannet'] );
		$this->setVoksenSamarbeid( $row['voksensamarbeid'] );

		$this->sorry = $row['sorry'];
		
		$this->calcHarSkjemaStatus();
    }
    
    /**
     * Beregn om deltaker- eller voksen-skjema er utfylt
     *
     * @return self
     */
    public function calcHarSkjemaStatus() {
		if( !empty( $this->getVoksenSamarbeid() ) && !empty( $this->getVoksenErfaring() )) {
			$this->setHarVoksenskjema( true );
        }
        return $this;
    }
	
	public function getSorry() {
		return $this->sorry;
	}
	
	// Har ikke deltakerskjema derfor returneres true
	public function harDeltakerskjema() {
		return true;
	}

	public function setLansupport( $bool ) {
		$this->lansupport = $bool;
		return $this;
	}
	public function getLansupport() {
		return $this->lansupport;
	}

	public function setStreamingtekniker( $bool ) {
		$this->streamingtekniker = $bool;
		return $this;
	}
	public function getStreamingtekniker() {
		return $this->streamingtekniker;
	}

	public function setModerator( $bool ) {
		$this->moderator = $bool;
		return $this;
	}
	public function getModerator() {
		return $this->moderator;
	}

	public function setUtover( $bool ) {
		$this->utover = $bool;
		return $this;
	}
	public function getUtover() {
		return $this->utover;
	}

	public function setSamarbeid( $samarbeid ) {
		$this->samarbeid = $samarbeid;
		return $this;
	}
	public function getSamarbeid() {
		return $this->samarbeid;
	}


	public function setVoksenErfaring( $erfaring ) {
		$this->voksen_erfaring = $erfaring;
		return $this;
	}
	public function getVoksenErfaring() {
		return $this->voksen_erfaring;
	}
	
	public function setVoksenSamarbeid( $samarbeid ) {
		$this->voksen_samarbeid = $samarbeid;
		return $this;
	}
	public function getVoksenSamarbeid() {
		return $this->voksen_samarbeid;
	}
	
	public function setVoksenAnnet( $annet ) {
		$this->voksen_annet = $annet;
		return $this;
	}
	public function getVoksenAnnet() {
		return $this->voksen_annet;
	}
	
}
