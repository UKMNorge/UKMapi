<?php

namespace UKMNorge\Samtykke;

class Innslag {
    private $samtykker = [];
    private $harNei = false;

    private $countNei = 0;
    private $countJa = 0;

    public function __construct( $innslag ) {
        foreach( $innslag->getPersoner()->getAll() as $person ) {
            $samtykke = new Person( $person, $innslag );

            if( $samtykke->getStatus()->getId() == 'ikke_godkjent' ) {
                $this->countNei++;
            }
            if( $samtykke->harForesatt() && $samtykke->getForesatt()->getStatus()->getId() == 'ikke_godkjent' ) {
                $this->countNei++;
            }

            $this->samtykker[] = $samtykke;
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