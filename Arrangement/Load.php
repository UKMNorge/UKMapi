<?php

namespace UKMNorge\Arrangement;

require_once('UKM/Arrangement/Arrangementer.php');

class Load {

    public static function byPostnummer( Int $season, Int $postnummer ) {
        return new Arrangementer( $season, 'postnummer', $postnummer);
    }
    public static function byKommune( Int $season, kommune $kommune ) {
        return static::byOmrade( $season, 'kommune', $kommune->getId() );
    }

    public static function byOmrade( Int $season, $omrade ) {
        return new Arrangementer( $season, $omrade->getType(), $omrade->getId() );
    }
}