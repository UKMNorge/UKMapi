<?php

namespace UKMNorge\Innslag\Mangler;

use UKMNorge\Innslag\Innslag;

class Mangler {
    public static function loadFromJSON( String $json ) {

    }

    public static function evaluer( Innslag $innslag ) {
        $tests = [
            Mangel\Person::evaluer( $innslag->getKontaktperson(), true),
            Mangel\Personer::evaluer( $innslag ),
            Mangel\Navn::evaluer( $innslag ),
            Mangel\Sjanger::evaluer( $innslag ),
            Mangel\Beskrivelse::evaluer( $innslag ),
            Mangel\TekniskeBehov::evaluer( $innslag ),
        ];
        
        $mangler = [];
        foreach( $tests as $test ) {
            $mangler = static::test( $mangler, $test);
        }
        return $mangler;
    }

    public static function toJSON( Array $mangler ) {
        return json_encode( $mangler );
    }

    public static function test( Array $mangler, $testResult ) {
        if( is_array( $testResult ) && sizeof( $testResult ) > 0 ) {
            $real_result = [];
            foreach( $testResult as $result ) {
                if( is_array( $result ) && sizeof( $result ) == 0 ) {
                    continue;
                }
                $real_result[] = $result;
            }
            $mangler = array_merge( $mangler, $real_result );
        }
        if( is_object( $testResult ) && get_class( $testResult ) == 'UKMNorge\Innslag\Mangler\Mangel' ) {
            $mangler[] = $testResult;
        }
        return $mangler;
    }
}