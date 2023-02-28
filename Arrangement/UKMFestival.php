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
     **/
    public function getOvernattingGrupper() {
        $og = new OvernattingGruppeSamling();
        return $og->getAll();
    }
    
    /**
     * Get OvernattingGruppe som liste
     *
     * @return UKMFestival
     **/
    public static function getCurrentUKMFestival() {
        $year = date('Y');
        $qry = new Query(
            self::getLoadQry() . "WHERE `place`.`pl_type` = 'land' AND `place`.`season` = '#season'",
            array('season' => $year)
        );
        $res = $qry->run('array');

        if($res) {
            return new UKMFestival($res['pl_id']);
        }
        throw new Exception('UKMFestivalen for '. $year .' er ikke definert');
    }

}