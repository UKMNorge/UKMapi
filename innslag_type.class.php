<?php
	
class innslag_type {
	var $id = null;
	var $key = null;
	var $name = null;
	var $icon = null;
	var $har_filmer = false; # Kan det finnes noe i UKM-TV?
	var $har_titler = false;
	var $tabell = false;
	var $har_tekniske_behov = false;
	
	public function __construct($id, $key, $name, $icon, $har_filmer, $har_titler, $tabell, $har_tekniske_behov) {
		$this->setId( $id );
		$this->setKey( $key );
		$this->setNavn( $name );
		$this->setIcon( $icon );
		$this->setHarFilmer( $har_filmer );
		$this->setHarTitler( $har_titler );
		$this->setHarTekniskeBehov( $har_tekniske_behov );
		$this->setTabell( $tabell );
	}
	
	public function setId( $id ) {
		$this->id = $id;
		return $this;
	}
	public function getId() {
		return $this->id;
	}
	
	public function setKey( $key ) {
		$this->key = $key;
		return $this;
	}
	public function getKey() {
		return $this->key;
	}
	
	public function setNavn( $name ) {
		$this->name = $name;
		return $this;
	}
	public function getNavn() {
		return $this->name;
	}
	
	public function setIcon( $icon ) {
		$this->icon = $icon;
		return $this;
	}
	public function getIcon() {
		return $this->icon;
	}
	
	public function setTabell($tabell) {
		$this->tabell = $tabell;
		return $this;
	}
	public function getTabell() {
		return $this->tabell;
	}
	
	public function harTid() {
		return $this->getTabell() != false;
	}
	
	public function setHarFilmer( $har_filmer ) {
		$this->har_filmer = $har_filmer;
		return $this;
	}
	public function harFilmer() {
		return $this->har_filmer;
	}

	
	public function setHarTitler( $har_titler ) {
		$this->har_titler = $har_titler;
		return $this;
	}
	public function harTitler() {
		return $this->har_titler;
	}
	
	public function setHarTekniskeBehov( $har_tekniske_behov ) {
		$this->har_tekniske_behov = $har_tekniske_behov;
		return $this;
	}
	public function harTekniskeBehov() {
		return $this->har_tekniske_behov;
	}
	
	public function getFrist() {
		return $this->harTitler() ? 1 : 2;
	}
	
	public function __toString() {
		return $this->getNavn();
	}

	public function getFunksjoner() {
        if( 5 == $this->getId() ) {
            return array('tekst'=> 'Journalist',
                         'foto' => 'Fotograf',
                         'videoreportasjer' => 'Videoreportasjer',
                         'flerkamera_regi' => 'Flerkamera, regi',
                         'flerkamera_kamera' => 'Flerkamera, kamera',
                         'pr' => 'PR og pressekontakt'
                        );
        } elseif( 8 == $this->getId() ) {
            return array('lyd' => 'Lyd',
                         'lys' => 'Lys',
                         'scenearbeider' => 'Scenearbeider',
                         'artistvert' => 'Artistvert',
                         'info' => 'Info / sekretariat',
                         'koordinator' => 'Koordinator / produsent'
                        );
        }
        return [];
    }
}