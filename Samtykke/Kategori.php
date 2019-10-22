<?php

namespace UKMNorge\Samtykke;

class Kategori {
	var $id = null;
    var $navn = null;
    var $sms = null;
    
    // Brukes av liste-visningen for mellomlagring av personer i denne kategorien. Quickfix.
    public $personer; 
	
	public function __construct( $id, $navn, $krav, $sms ) {
        $this->id = $id;
        $this->navn = $navn;
        $this->krav = $krav;
        $this->sms = $sms;
    }
	
	public function getId() {
		return $this->id;
	}
	public function getNavn() {
		return $this->navn;
    }
    public function getKrav() {
        return $this->krav;
    }
    public function getSms() {
        return $this->sms;
    }
	public function __toString() {
		return $this->getNavn();
    }
}