<?php

namespace UKMNorge\Kommunikasjon;

use Exception;
use UKMNorge\Database\SQL\Query;

class Reservasjoner {

    static $blokkerteMobilnummer = null;

    /**
     * Hent inn alle blokkerte mobilnummer,
     * slik at vi kan sjekke reservasjoner for mange i slengen.
     * 
     * Må skrives om når vi begynner å blokkere latterlig mange.
     *
     * @return void
     */
    private static function _loadBlockListMobil() {
        if( is_null(static::$blokkerteMobilnummer) ) {
            $sql = new Query("SELECT `number` FROM `sms_block`");
            $res = $sql->run();
            
            while( $row = Query::fetch( $res ) ) {
                static::$blokkerteMobilnummer[] = $row['number'];
            }
        }
    }

    /**
     * Sjekk om dette mobilnummeret er blokkert
     *
     * @throws Exception
     * @return bool true
     */
    public static function erBlokkertMotSms( String $mobilnummer ) {
        if( is_null($mobilnummer) || $mobilnummer == 0) {
            throw new Exception(
                'Kan ikke sjekke om tomt mobilnummer er blokkert'.
                'da mottakeren ikke har oppgitt mobilnummer',
                134001
            );
        }

        // Sikre at listen med blokkerte mobilnummer er lastet inn
        static::_loadBlockListMobil();
        
        // Sikre at vi jobber med standardisert mobilnummer
        $mobilnummer = Mottaker::cleanMobil($mobilnummer);

        if( in_array($mobilnummer, static::$blokkerteMobilnummer) ) {
            return true;
        }

        return false;
    }
}