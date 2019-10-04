<?php

namespace UKMNorge\Innslag\Mangler\Mangel;

use UKMNorge\Innslag\Innslag;
use UKMNorge\Innslag\Mangler\Mangel;

class Personer {
    public static function evaluer( Innslag $innslag ) {
        if( $innslag->getPersoner()->getAntall() == 0 ) {
            return new Mangel(
                    'personer.ingen',
                    'Mangler deltakere',
                    'Det er ingen deltakere i innslaget',
                    'innslag',
                    $innslag->getId()
            );
        }

        $mangler = [];
        foreach( $innslag->getPersoner()->getAll() as $person ) {
            $mangler[] = Person::evaluer( $person );
        }
        return $mangler;
    }
}