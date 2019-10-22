<?php

namespace UKMNorge\Kommunikasjon;

class Mottaker {
    var $navn = null;
    var $epost = null;
    var $mobil = null;

    public function __construct( String $navn, String $epost) {
        $this->navn = $navn;
        $this->epost = $epost;
    }

    /**
     * Opprett mottaker fra e-post (og navn)
     *
     * @param String $epost
     * @param String $navn
     * @return Mottaker
     */
    public static function fraEpost( String $epost, String $navn=null) {   
        $mottaker = new Mottaker( $navn, $epost );
    
        return $mottaker;
    }

    /**
     * Hent mottakerens navn
     * 
     * @return String $navn
     */ 
    public function getNavn()
    {
        if( empty( $this->navn ) ) {
            return $this->getEpost();
        }
        return $this->navn;
    }

    /**
     * Har vi navn på mottakeren?
     *
     * @return Bool
     */
    public function harNavn(){
        return strlen( $this->getNavn() ) > 0;
    }

    /**
     * Hent mottakerens e-postadresse
     * 
     * @return String $epost
     */ 
    public function getEpost()
    {
        return $this->epost;
    }

    /**
     * Hent mottakerens mobilnummer
     * 
     * @return Int $mobilnummer
     */ 
    public function getMobil()
    {
        return (Int) $this->mobil;
    }
}