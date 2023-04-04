<?php

namespace UKMNorge\Sensitivt;
use Exception;
use UKMNorge\Allergener\Allergener;
use UKMNorge\Sensitivt\Intoleranse as SensitivtIntoleranse;

/**
 * 
 * DEVELOPER: SENSITIVT-KLASSER SKAL ALDRI
 * KJØRE SQL-SPØRRINGER DIREKTE, MEN ALLTID BRUKE
 * self::query( $sql, $data )
 * 
 */

class LederSensitivt extends Intoleranse {    
    const DB_TABLE = 'ukm_sensitivt_intoleranse';
    const DB_ID = 'leder_id';

    /**
	 * Load from database
	 *
	 * @param Int $id
	 * @return void
	 */
    protected function _load( $id ) {
        $res = self::query("
            SELECT * 
            FROM `#db_table`
            WHERE `#db_id` = '#id'",
            [
                'id' => $id,
                'db_id' => static::DB_ID,
                'db_table' => static::DB_TABLE
            ]
        );

		$this->_liste = [];
		$this->intoleranser = [];

        if( !$res ) {
            $this->har = false;
            return false;
        }

        $this->_populate( self::getFirstRow( $res ) );
    }
}