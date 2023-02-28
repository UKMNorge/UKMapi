<?php

namespace UKMNorge\Arrangement\Videresending\Request;
use UKMNorge\Arrangement\Arrangement;


class RequestVideresending {
    const TABLE = 'ukm_request_videresending';

    var $id;
    var $arrangement_fra;
    var $arrangement_til;
    var $dato;
    var $completed;
    
    /**
     * Opprett Hovedleder-objekt
     *
     * @param Int $id
     * @param Int $arrangement_fra
     * @param Int $arrangement_til
     * @param String $dato
     * @return void
     */
    public function __construct(Int $id, Int $arrangement_fra, Int $arrangement_til, $dato=null, Bool $completed=false) {
        $this->id = $id;
        $this->arrangement_fra = $arrangement_fra;
        $this->arrangement_til = $arrangement_til;
        $this->dato = $dato;
        $this->completed = $completed;

        // Hvis kombinasjonen fra til eksisterer, da får RequestVideresending riktig id
        Write::eksisterer($this);
    }


    /**
     * Sjekker om kobinasjonen arrangement fra - arrangement til finnes. Returnerer boolean
     *
     * @return Bool
     */
    public static function finnesKombinasjonen(Int $arrangement_fra, Int $arrangement_til) {
        $reqVideresending = new RequestVideresending(-1, $arrangement_fra, $arrangement_til);
        Write::eksisterer($reqVideresending);
        
        return $reqVideresending->getId() != -1 ? true : false;
    }

    /**
     * Hent id
     *
     * @return Int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set id (bare hvis id er udefinert -1)
     *
     * @return void
     */
    public function setId(Int $id) {
        if($this->id == -1) {
            $this->id = $id;
        }
    }

    /**
     * Hent id av arrangement_fra
     * 
     * @return Int
     */
    public function getArrangementFraId() {
        return $this->arrangement_fra;
    }

    /**
     * Hent id av arrangement_til
     * 
     * @return Int
     */
    public function getArrangementTilId() {
        return $this->arrangement_til;
    }

    /**
     * Hent Arrangement fra
     * 
     * @return Arrangement
     */
    public function getArrangementFra() {
        return new Arrangement($this->arrangement_fra);
    }

    /**
     * Hent Arrangement til
     * 
     * @return Arrangement
     */
    public function getArrangementTil() {
        return new Arrangement($this->arrangement_til);
    }

    /**
     * Hent dato
     * 
     * @return Date
     */
    public function getDato() {
        return $this->dato ? $this->dato : null;
    }


    /**
     * Er gjennomført
     * 
     * @return Bool
     */
    public function isCompleted() {
        return $this->completed;
    }
  
    /**
     * Set gjennomført
     * 
     * @return void
     */
    public function setCompleted(Bool $completed) {
        $this->completed = $completed;
    }
    
}
