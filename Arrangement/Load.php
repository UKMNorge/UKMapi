<?php

namespace UKMNorge\Arrangement;

use kommune;
use fylke;
use UKMNorge\Nettverk\Omrade;

require_once('UKM/Arrangement/Arrangementer.php');

class Load {

    /**
     * Alle arrangementer av en gitt eier (fylke eller kommune)
     *
     * @param Int $season
     * @param kommune|fylke $eier
     * @return Arrangementer $arrangementer
     */
    public static function byEier( Int $season, $eier ) {
        if( !in_array( get_class($eier), ['kommune','fylke'] ) ) {
            throw new Exception('byEier krever at parameter 2 er enten kommune- eller fylke-objekt');
        }
        return static::byOmradeInfo( $season, 'eier-'.get_class( $eier ), $eier->getId());
    }

    /**
     * Alle lokal-arrangement hvor en kommune er involvert
     * (er eier, eller med-arrangør)
     *
     * @param Int $season
     * @param kommune $kommune
     * @return Arrangementer
     */
    public static function forKommune( Int $season, kommune $kommune ) {
        return static::byOmradeInfo( $season, 'kommune', $kommune->getId() );
    }

    /**
     * Alle lokal-arrangement i et gitt fylke
     *
     * @param Int $season
     * @param fylke $fylke
     * @return Arrangementer
     */
    public static function iFylke( Int $season, fylke $fylke ) {
        return static::byOmradeInfo( $season, 'fylke', $fylke->getId() );
    }


    /**
     * Alle fylkes-arrangement i et fylke
     *
     * @param Int $season
     * @param fylke $fylke
     * @return Arrangementer
     */
    public static function forFylke( Int $season, fylke $fylke ) {
        return static::byEier( $season, $fylke);
    }

    /**
     * Hent Arrangement-collection for gitt område,
     * Bruker 2 parametre i stedet for område-objektet
     *
     * @param Int $season
     * @param String $omrade_type
     * @param Int $omrade_id
     * @return Arrangementer
     */
    public static function byOmradeInfo( Int $season, String $omrade_type, Int $omrade_id ) {
        return new Arrangementer( $season, $omrade_type, $omrade_id );
    }

    /**
     * Hent Arrangement-collection for gitt område
     *
     * @param Int $season
     * @param Omrade $omrade
     * @return Arrangementer
     */
    public static function byOmrade( Int $season, Omrade $omrade ) {
        return static::byOmradeInfo( $season, $omrade->getType(), $omrade->getForeignId() );
    }


    /* IMPLEMENT */
    #public static function byPostnummer( Int $season, Int $postnummer ) {
    #    return static::byOmradeInfo( $season, 'postnummer', $postnummer);
    #}

}