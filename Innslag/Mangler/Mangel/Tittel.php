<?php

namespace UKMNorge\Innslag\Mangler\Mangel;

use UKMNorge\Innslag\Innslag;
use UKMNorge\Innslag\Mangler\Mangel;
use UKMNorge\Innslag\Type as InnslagType;
use UKMNorge\Innslag\Titler\Tittel as InnslagTittel;

class Tittel
{
    public static function evaluer( InnslagType $type, InnslagTittel $tittel )
    {   
        switch( $type->getKey() ) {
            case 'dans':
                return evaluerDans( $tittel );
        }

        return [];
    }



    public static function evaluerDans( InnslagTittel $tittel ) {
        
    }
}
