<?php

namespace UKMNorge\Arrangement\Videresending\Request;

class RequestVideresending
{
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
    public function __construct(Int $id, Int $arrangement_fra, Int $arrangement_til, String $dato, Bool $completed) {
        $this->id = $id;
        $this->arrangement_fra = $arrangement_fra;
        $this->arrangement_til = $arrangement_til;
        $this->dato = $dato;
        $this->completed = $completed;
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
     * Set id
     *
     * @return void
     */
    public function setId(Int $id) {
        $this->id = $id;
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
     * Hent dato
     * 
     * @return Date
     */
    public function getDato() {
        return $this->dato;
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
