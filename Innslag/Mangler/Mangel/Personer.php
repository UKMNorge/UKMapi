<?php

namespace UKMNorge\Innslag\Mangler\Mangel;

use UKMNorge\Innslag\Innslag;
use UKMNorge\Innslag\Mangler\Mangel;
use UKMNorge\Innslag\Mangler\Mangler;

class Personer {
    public static function evaluer( Innslag $innslag ) {
        if( $innslag->getPersoner()->getAntall() == 0 ) {
            return new Mangel(
                    'person.ingen',
                    'Mangler deltakere',
                    'Det er ingen deltakere i innslaget',
                    'innslag',
                    $innslag->getId()
            );
        }

        $mangler = [];
        foreach( $innslag->getPersoner()->getAll() as $person ) {
            $testResults = Person::evaluer( $person, $innslag->getType() );
            if( is_array($testResults) && sizeof($testResults) > 0 ) {
                foreach( $testResults as $testResult ) {
                    if( is_object($testResult) ) {
                        $mangler[] = $testResult;
                    }
                }
            }
        }
        return Mangler::manglerOrTrue( $mangler );
    }
}