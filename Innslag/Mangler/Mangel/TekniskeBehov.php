<?php

namespace UKMNorge\Innslag\Mangler\Mangel;

use UKMNorge\Innslag\Innslag;
use UKMNorge\Innslag\Mangler\Mangel;

class TekniskeBehov {
    public static function evaluer( Innslag $innslag ) {
        if( !$innslag->getType()->harTekniskeBehov() ) {
            return true;
        }

        if( empty( $innslag->getTekniskeBehov() ) ) {
            return new Mangel(
                    'innslag.tekniske',
                    'Innslaget mangler tekniske behov',
                    'Innslaget må en beskrivelse av de tekniske behovene',
                    'innslag',
                    $innslag->getId()
                )
            ;
        }
        return true;
    }
}