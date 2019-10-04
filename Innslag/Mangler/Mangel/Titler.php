<?php

namespace UKMNorge\Innslag\Mangler\Mangel;

use UKMNorge\Innslag\Innslag;
use UKMNorge\Innslag\Mangler\Mangel;

class Titler {
    public static function evaluer( Innslag $innslag ) {
        if( $innslag->getTitler()->getAntall() == 0 ) {
            return new Mangel(
                    'titler.ingen',
                    'Mangler tittel',
                    'Det er ingen titler i innslaget',
                    'innslag',
                    $innslag->getId()
            );
        }

        $mangler = [];
        foreach( $innslag->getTitler()->getAll() as $tittel ) {
            $mangler[] = Tittel::evaluer( $tittel );
        }
        return $mangler;
    }
}