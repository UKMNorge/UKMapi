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
     * Get instanse av UKMFestivalen for nåværende år eller sesong
     *
     * @return UKMFestival
     **/
    public static function getCurrentUKMFestival() {
        try{
            $currentYearUKMFestival = self::getUKMFestivalen(date('Y'));
        } catch( Exception $e ) {
            if( $e->getCode() != 1000 ) {
                throw $e;
            }
            // If not found for current year, try last year
            return static::getBySeason(date('Y')-1);
        }
        return self::getUKMFestivalen(date('Y'));
    }

    /**
     * Get instanse av UKMFestivalen for en gitt sesong
     *
     * @param int $season
     * @return UKMFestival
     **/
    public static function getBySeason($season) {
        return self::getUKMFestivalen($season);
    }

    /**
     * Hent UKMFestivalen for en gitt sesong
     *
     * @param int $season
     * @return UKMFestival
     */
    private static function getUKMFestivalen($season) {
        $qry = new Query(
            self::getLoadQry() . "WHERE `place`.`pl_type` = 'land' AND `place`.`season` = '#season'",
            array('season' => $season)
        );
        $res = $qry->run('array');

        if($res) {
            return new UKMFestival($res['pl_id']);
        }
        throw new Exception('UKMFestivalen for '. $season .' er ikke definert', 1000);
    }
}