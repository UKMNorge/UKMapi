<?php

/**
 * MELDINGSTEKSTER OG TEMPLATE DEFINITIONS
 */

namespace UKMNorge\Samtykke\Meldinger;

require_once('melding.class.php');
require_once('deltaker.class.php');
require_once('foresatt.class.php');
require_once('purring.class.php');

class Meldinger {
    public static function getById( $melding_id ) {
        switch( $melding_id ) {
            case 'deltaker':
                return new Deltaker();
            case 'deltaker_u15':
                return new DeltakerU15();
            case 'foresatt':
                return new Foresatt();
            case 'foresatt_deltakergodkjent':
                return new ForesattDeltakerHarGodkjent();
            case 'purring_deltaker':
                return new PurringDeltaker();
            case 'purring_foresatt':
                return new PurringForesatt();
        }
    }
}