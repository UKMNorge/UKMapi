<?php

namespace UKMNorge\Innslag\Titler;

class Utstilling extends Tittel {
    public $real_type = 'utstilling';

    public function getParentes() {
        $tekst = '';	
        if( !empty( $this->getType() ) ) {
            $tekst .= 'Type: '. $this->getType() .' ';
        }
        if( !empty( $this->getTeknikk() ) ) {
            $tekst .= 'Teknikk: '. $this->getTeknikk() .' ';
        }

        return rtrim( $tekst );
    }
}