<?php

namespace UKMNorge\Sensitivt;

require_once('UKM/Sensitivt/LederSensitivt.php');

class Leder extends LederSensitivt {
    private $intoleranse = null;

    /**
     * Opprett container-objekt for sensitiv leder-info
     *
     * @param Int $id
     */
    public function __construct( $id ) {
        parent::__construct( $id );
    }

    /**
     * Hent informasjon om lederens allergier
     *
     * @return AllerLederSensitivtgi
     */
    public function getIntoleranse() {
        if( null == $this->intoleranse ) {
            $this->intoleranse = new LederSensitivt( $this->getId() );
        }

        return $this->intoleranse;
    }
}