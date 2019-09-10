<?php

namespace UKMNorge\Meta;

class ParentObject {

    private $type;
    private $id;

    /**
     * Opprett et parent-objekt
     *
     * @param String $type
     * @param Int $id
     * @return ParentObject
     */
    public function __construct( String $type, Int $id )
    {
        $this->type = $type;
        $this->id = $id; 
    }    

    /**
     * Hvilken type objekt er dette?
     */ 
    public function getType()
    {
        return $this->type;
    }

    /**
     * Hva er ID av parent-objektet?
     */ 
    public function getId()
    {
        return $this->id;
    }
}