<?php

namespace UKMNorge\Sensitivt;

/**
 * 
 * DEVELOPER: SENSITIVT-KLASSER SKAL ALDRI
 * KJØRE SQL-SPØRRINGER DIREKTE, MEN ALLTID BRUKE
 * self::query( $sql, $data )
 * 
 */

class Intoleranse extends Sensitivt {    
    const DB_TABLE = 'ukm_sensitivt_intoleranse';
    const DB_ID = 'p_id';

    private $har = null;
    private $tekst = null;
    private $liste = null;

    public function __construct( $id ) {
        parent::__construct( $id );
        $this->_load( $id );
    }


    private function _load( $id ) {
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

        if( !$res ) {
            $this->har = false;
            return false;
        }

        $this->_populate( self::getFirstRow( $res ) );
    }

    private function _populate( $data ) {
        if( null == $data ) {
            $this->har = false;
            return false;
        }

        $this->har = true;
        $this->tekst = $data['tekst'];
    }

    public function har() {
        return $this->har;
    }

    public function getTekst() {
        return $this->tekst;
    }
}