<?php

namespace UKMNorge\Samtykke;

class Innslag {
    private $samtykker = [];
    private $harNei = false;

    private $countNei = 0;
    private $countJa = 0;

    public function __construct( $innslag ) {
        foreach( $innslag->getPersoner()->getAll() as $person ) {
            $samtykkePerson = new Person( $person, $innslag );

            if($samtykkePerson->erSamtykkeGittFult()) {
                $this->countJa++;
            } else {
                $this->countNei++;
            }

            $this->samtykker[] = $samtykkePerson;
        }
    }

    public function getAll() {
        return $this->samtykker;
    }
    
    public function harNei() {
        return $this->countNei > 0;
    }

    public function getNeiCount() {
        return $this->countNei;
    }
}