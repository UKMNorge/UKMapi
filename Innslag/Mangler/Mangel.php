<?php

namespace UKMNorge\Innslag\Mangler;

class Mangel {

    var $id = null;
    var $name = null;
    var $beskrivelse = null;
    var $objekt = null;
    var $objekt_id = null;

    
    public function __construct( String $id, String $navn, String $beskrivelse, String $objekt, Int $objekt_id ) {
        $this->id = $id;
        $this->navn = $navn;
        $this->beskrivelse = $beskrivelse;
        $this->objekt = $objekt;
        $this->objekt_id = $objekt_id;
    }


    /**
     * Hent ID
     */ 
    public function getId()
    {
        return $this->id;
    }

    /**
     * Hent navn på mangel
     */ 
    public function getName()
    {
        return $this->name;
    }

    /**
     * Hent nøyaktig beskrivelse av mangelen
     */ 
    public function getBeskrivelse()
    {
        return $this->beskrivelse;
    }
}