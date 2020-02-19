<?php

namespace UKMNorge\Innslag\Nominasjon;

class PlaceholderVoksen {
	var $id;
	var $nominasjon;
	var $navn;
	var $mobil;
	var $rolle;
	
	public function __construct( $garbage ) {
		// Do nothing
	}
    
    /**
     * Hent ID
     *
     * @return Int
     */
	public function getId() {
		return $this->id;
    }
    
    /**
     * Angi voksen-ID
     *
     * @param Int $id
     * @return self
     */
	public function setId( Int $id ) {
		$this->id = $id;
		return $this;
	}
    
    /**
     * Hent nominasjonsID
     *
     * @return Int
     */
	public function getNominasjon() {
		return $this->nominasjon;
    }
    
    /**
     * Angi hvilken nominasjon-ID denne voksne tilhører
     *
     * @param Int $nominasjon
     * @return self
     */
	public function setNominasjon( Int $nominasjon ) {
		$this->nominasjon = $nominasjon;
		return $this;
	}
    
    /**
     * Hent den voksnes navn
     *
     * @return String
     */
	public function getNavn() {
		return $this->navn;
    }
    
    /**
     * Angi den voksnes navn
     *
     * @param String $navn
     * @return self
     */
	public function setNavn( String $navn ) {
		$this->navn = $navn;
		return $this;
	}
    
    /**
     * Hent den voksnes mobilnummer
     *
     * @return Int
     */
	public function getMobil() {
		return $this->mobil;
    }
    
    /**
     * Angi den voksnes mobilnummer
     *
     * @param Int $mobil
     * @return self
     */
	public function setMobil( Int $mobil ) {
		$this->mobil = $mobil;
		return $this;
	}
    
    /**
     * Hvilken rolle har den voksne?
     *
     * @return String
     */
	public function getRolle() {
		return $this->rolle;
    }
    
    /**
     * Angi hvilken rolle personen har hatt
     *
     * @param String $rolle
     * @return self
     */
	public function setRolle( String $rolle ) {
		$this->rolle = $rolle;
		return $this;
	}
}
