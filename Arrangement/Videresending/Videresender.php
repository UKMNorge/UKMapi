<?php

namespace UKMNorge\Arrangement\Videresending;
use Exception, DateTime;
use UKMNorge\Arrangement\Arrangement;

abstract class Videresender {
    private $fra;
    private $til;
    private $arrangement;
    private $innslag;
    private $start;
    private $registrert;
    private $navn;
    private $eier;

    /**
     * Opprett en videresender
     *
     * @param Int $pl_fra
     * @param Int $pl_til
     */
    public function __construct( Int $pl_fra, Int $pl_til )
    {
        $this->fra = $pl_fra;
        $this->til = $pl_til;   
    }

    public function getArrangement() {
        if( null == $this->arrangement ) {
            require_once('UKM/Arrangement/Arrangement.php');
            $this->arrangement = new Arrangement( $this->getPlId() );
        }
        return $this->arrangement;
    }

    public function getVideresendte() {
        throw new Exception('Må implementeres!');
    }

    public function setProxyData( String $navn, Bool $registrert, DateTime $start, $eier) {
        $this->setNavn( $navn );
        $this->setRegistrert( $registrert );
        $this->setStart( $start );
        $this->setEier( $eier );
    }

    /**
     * Get the value of start
     */ 
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Set the value of start
     *
     * @return  self
     */ 
    public function setStart( DateTime $start)
    {
        $this->start = $start;
        return $this;
    }

    /**
     * Get the value of navn
     */ 
    public function getNavn()
    {
        if( empty( $this->navn ) ) {
            $arrangement = $this->getArrangement();

            $this->setProxyData(
                $arrangement->getNavn(),
                $arrangement->erRegistrert(),
                $arrangement->getStart(),
                $arrangement->getEier()
            );
        }
        return $this->navn;
    }

    /**
     * Set the value of navn
     *
     * @return  self
     */ 
    public function setNavn( String $navn)
    {
        $this->navn = $navn;

        return $this;
    }

    /**
     * Get the value of innslag
     */ 
    public function getInnslag()
    {
        throw new Exception('getInnslag må implementeres');
        return $this->innslag;
    }

    /**
     * Get the value of fra
     */ 
    public function getFra()
    {
        return $this->fra;
    }

    /**
     * Get the value of til
     */ 
    public function getTil()
    {
        return $this->til;
    }

    /**
     * Proxy arrangement-eier
     * 
     * @return kommune|fylke $eier
     */ 
    public function getEier()
    {
        return $this->eier;
    }

    /**
     * Sett ny proxy for arrangement-eier
     *
     * @return self
     */ 
    public function setEier($eier)
    {
        $this->eier = $eier;

        return $this;
    }

    /**
     * Get the value of registrert
     */ 
    public function erRegistrert()
    {
        return $this->registrert;
    }

    /**
     * Set the value of registrert
     *
     * @return  self
     */ 
    public function setRegistrert($registrert)
    {
        $this->registrert = $registrert;

        return $this;
    }
}