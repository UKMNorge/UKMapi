<?php

/**
 * MELDINGSTEKSTER OG TEMPLATE DEFINITIONS
 */

namespace UKMNorge\Samtykke\Meldinger;

require_once('UKM/Autoloader.php');

class Meldinger {
    public static function getById( $melding_id ) {
        switch( $melding_id ) {
            case 'ombestemt':
                return new Ombestemt();
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