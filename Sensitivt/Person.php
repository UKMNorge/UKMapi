<?php

namespace UKMNorge\Sensitivt;

require_once('UKM/Sensitivt/Sensitivt.php');

class Person extends Sensitivt {
    private $intoleranse = null;

    /**
     * Opprett container-objekt for sensitiv person-info
     *
     * @param Int $id
     */
    public function __construct( $id ) {
        parent::__construct( $id );
    }

    /**
     * Hent informasjon om en persons allergier
     *
     * @return Allergi
     */
    public function getIntoleranse() {
        if( null == $this->intoleranse ) {
            require_once('UKM/Sensitivt/Intoleranse.php');
            $this->intoleranse = new Intoleranse( $this->getId() );
        }

        return $this->intoleranse;
    }
}