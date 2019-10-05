<?php

namespace UKMNorge\Innslag\Mangler\Mangel;

use UKMNorge\Innslag\Innslag;
use UKMNorge\Innslag\Mangler\Mangel;

class Sjanger {
    public static function evaluer( Innslag $innslag ) {
        if( !$innslag->getType()->harSjanger() ) {
            return true;
        }

        if( empty( $innslag->getSjanger() ) ) {
            return new Mangel(
                    'innslag.sjanger',
                    'Innslaget har ikke navn',
                    'Innslaget må ha et navn',
                    'innslag',
                    $innslag->getId()
                )
            ;
        }
        return true;
    }
}