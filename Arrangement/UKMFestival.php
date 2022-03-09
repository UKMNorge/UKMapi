<?php

namespace UKMNorge\Arrangement;

use UKMNorge\UKMFestivalen\Overnatting\OvernattingGruppe;
use UKMNorge\UKMFestivalen\Overnatting\Samling as OvernattingGruppeSamling;

use UKMNorge\Database\SQL\Query;
use Exception;

/**
 * UKM Festivalen
 * Utvider Arrangement klassen
 * 
 * @namespace UKMNorge\Arrangement
 */
class UKMFestival extends Arrangement {
    
    /**
     * Get OvernattingGruppe som liste
     *
     * @return OvernattingGruppe[]
     **/
    public function getOvernattingGrupper() {
        $og = new OvernattingGruppeSamling();
        return $og->getAll();
    }

}