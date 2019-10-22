<?php

namespace UKMNorge\Innslag\Mangler\Mangel;

use UKMNorge\Innslag\Innslag;
use UKMNorge\Innslag\Mangler\Mangel;

class Beskrivelse {
    public static function evaluer( Innslag $innslag ) {
        if( empty( $innslag->getBeskrivelse() ) ) {
            return new Mangel(
                    'innslag.beskrivelse',
                    'Innslaget har ikke beskrivelse',
                    'Innslaget må ha en beskrivelse som er lengre enn 20 bokstaver',
                    'innslag',
                    $innslag->getId()
                )
            ;
        }
        return true;
    }
}