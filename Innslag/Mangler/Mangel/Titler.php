<?php

namespace UKMNorge\Innslag\Mangler\Mangel;

use UKMNorge\Innslag\Innslag;
use UKMNorge\Innslag\Mangler\Mangel;
use UKMNorge\Innslag\Mangler\Mangler;

class Titler {
    public static function evaluer( Innslag $innslag ) {
        // Matkultur har mistet titlene sine
        if( $innslag->getType()->getKey() == 'matkultur' ) {
            return true;
        }

        if( $innslag->getTitler()->getAntall() == 0 ) {
            return new Mangel(
                    'tittel.ingen',
                    'Mangler tittel',
                    'Det er ingen titler i innslaget',
                    'innslag',
                    $innslag->getId()
            );
        }

        $mangler = [];
        
        foreach( $innslag->getTitler()->getAll() as $tittel ) {
            $testResults = Tittel::evaluer( $innslag->getType(), $tittel );
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