<?php

namespace UKMNorge\Innslag\Mangler;

use stdClass;

class Mangel {

    var $id = null;
    var $navn = null;
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

    public static function fraJSON( stdClass $json_object ) {
        return new Mangel(
            $json_object->id,
            $json_object->navn,
            $json_object->beskrivelse,
            $json_object->objekt,
            $json_object->objekt_id
        );
    }


    /**
     * Hent ID
     */ 
    public function getId()
    {
        return $this->id;
    }

    /**
     * Hent kategori denne mangelen faller inn under
     *
     * @return String kategori
     */
    public function getKategori() {
        return explode('.', $this->getId())[0];
    }

    /**
     * Hent navn på mangel
     * 
     * @return String
     */ 
    public function getNavn()
    {
        return $this->navn;
    }

    /**
     * Hent nøyaktig beskrivelse av mangelen
     * 
     * @return String
     */ 
    public function getBeskrivelse()
    {
        return $this->beskrivelse;
    }

    /**
     * Hent objekt-typen mangelen gjelder for
     * 
     * @return String
     */ 
    public function getObjekt()
    {
        return $this->objekt;
    }

    /**
     * Hent objekt-id mangelen gjelder for
     * 
     * @return Int
     */ 
    public function getObjektId()
    {
        return $this->objekt_id;
    }
}