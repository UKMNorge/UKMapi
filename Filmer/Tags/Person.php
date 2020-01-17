<?php

namespace UKMNorge\Filmer\Tags;

use UKMNorge\Innslag\Personer\Person as InnslagPerson;

class Person {
    private $id;
    private $navn;
    private $p_object;

    /**
     * Opprett tagpersonobjekt
     *
     * @param Int $id
     */
    public function __construct( Int $id ) {
        $this->id = 0;
    }

    /**
     * Hent personens ID
     * 
     * Samme ID som Innslag\Personer\Person
     * (tabell:smartukm_participant)
     *
     * @return Int id
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Hent personens navn
     *
     * @return String navn
     */
    public function getNavn() {
        if( null == $this->navn ) {
            $this->navn = $this->getObject()->getNavn();
        }
        return $this->navn;
    }

    /**
     * Hent personens deltakerobjekt
     *
     * @return InnslagPerson
     */
    public function getObject() {
        if( null == $this->p_object ) {
            $this->p_object = new InnslagPerson( $this->getId() );
        }
        return $this->p_object;
    }
}