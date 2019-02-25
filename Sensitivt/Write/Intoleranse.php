<?php

namespace UKMNorge\Sensitivt\Write;

use UKMNorge\Sensitivt\Intoleranse as ReadIntoleranse;
require_once('UKM/Sensitivt/Intoleranse.php');

class Intoleranse extends ReadIntoleranse {
    
    public function setTekst( $tekst ) {
        return $this->update('tekst', $tekst);
    }
}
