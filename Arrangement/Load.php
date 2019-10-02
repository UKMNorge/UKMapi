<?php

namespace UKMNorge\Arrangement;

use UKMNorge\Nettverk\Omrade;
use UKMNorge\Geografi\Fylke;
use UKMNorge\Geografi\Kommune;
use Exception;

require_once('UKM/Autoloader.php');

class Load {

    public static function bySesong( $sesong, $filter=false ) {
        return new Arrangementer( $sesong, 'alle', 0);
    }

    /**
     * Alle arrangementer av en gitt eier (fylke eller kommune)
     *
     * @param Int $sesong
     * @param kommune|fylke $eier
     * @return Arrangementer $arrangementer
     */
    public static function byEier( Int $sesong, $eier ) {
        if( is_string($eier) || !in_array( get_class($eier), ['kommune','fylke','UKMNorge\Geografi\Fylke','UKMNorge\Geografi\Kommune'] ) ) {
            throw new Exception('byEier krever at parameter 2 er enten kommune- eller fylke-objekt');
        }
        return static::byOmradeInfo( $sesong, 'eier-'.strtolower(str_replace('UKMNorge\Geografi\\','', get_class( $eier ))), $eier->getId());
    }

    /**
     * Alle lokal-arrangement hvor en kommune er involvert
     * (er eier, eller med-arrangør)
     *
     * @param Int $sesong
     * @param kommune $kommune
     * @return Arrangementer
     */
    public static function forKommune( Int $sesong, Kommune $kommune, Filter $filter=null ) {
        if( $filter == null ) {
            $filter = new Filter();
        }
        return static::byOmradeInfo( $sesong, 'kommune', $kommune->getId(), $filter );
    }

    /**
     * Alle lokal-arrangement i et gitt fylke
     *
     * @param Int $sesong
     * @param fylke $fylke
     * @return Arrangementer
     */
    public static function iFylke( Int $sesong, Fylke $fylke ) {
        return static::byOmradeInfo( $sesong, 'fylke', $fylke->getId() );
    }


    /**
     * Alle fylkes-arrangement i et fylke
     *
     * @param Int $sesong
     * @param fylke $fylke
     * @return Arrangementer
     */
    public static function forFylke( Int $sesong, Fylke $fylke ) {
        return static::byEier( $sesong, $fylke);
    }

    /**
     * Hent Arrangement-collection for gitt område,
     * Bruker 2 parametre i stedet for område-objektet
     *
     * @param Int $sesong
     * @param String $omrade_type
     * @param Int $omrade_id
     * @return Arrangementer
     */
    public static function byOmradeInfo( Int $sesong, String $omrade_type, Int $omrade_id, Filter $filter=null ) {
        if( $filter == null ) {
            $filter = new Filter();
        }
        return new Arrangementer( $sesong, $omrade_type, $omrade_id, $filter );
    }

    /**
     * Hent Arrangement-collection for gitt område
     *
     * @param Int $sesong
     * @param Omrade $omrade
     * @return Arrangementer
     */
    public static function byOmrade( Int $sesong, Omrade $omrade, Filter $filter=null ) {
        if( $filter == null ) {
            $filter = new Filter();
        }
        return static::byOmradeInfo( $sesong, $omrade->getType(), $omrade->getForeignId(), $filter);
    }


    /* IMPLEMENT */
    #public static function byPostnummer( Int $sesong, Int $postnummer ) {
    #    return static::byOmradeInfo( $sesong, 'postnummer', $postnummer);
    #}

}