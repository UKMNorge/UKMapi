<?php

namespace UKMNorge\Arrangement;

use kommune;
use fylke;

require_once('UKM/Arrangement/Arrangementer.php');

class Load {

    public static function byPostnummer( Int $season, Int $postnummer ) {
        return static::byOmradeInfo( $season, 'postnummer', $postnummer);
    }
    public static function byKommune( Int $season, kommune $kommune ) {
        return static::byOmradeInfo( $season, 'kommune', $kommune->getId() );
    }

    public static function byFylke( Int $season, fylke $fylke ) {
        return static::byOmradeInfo( $season, 'fylke', $fylke->getId() );
    }

    public static function byOmradeInfo( Int $season, $omrade_type, $omrade_id ) {
        return new Arrangementer( $season, $omrade_type, $omrade_id );
    }

    public static function byOmrade( Int $season, $omrade ) {
        return static::byOmradeInfo( $season, $omrade->getType(), $omrade->getId() );
    }
}