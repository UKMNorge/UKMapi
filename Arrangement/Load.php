<?php

namespace UKMNorge\Arrangement;

use UKMNorge\Nettverk\Omrade;
use UKMNorge\Geografi\Fylke;
use UKMNorge\Geografi\Kommune;
use Exception;

require_once('UKM/Autoloader.php');


/**
 * Laster inn arrangementer
 * @see UKMNorge\Arrangement\Kommende for å laste kun kommende arrangement
 * @see UKMNorge\Arrangement\Tidligere for å laste kun tidligere arrangement
 */
class Load
{

    /**
     * Hent arrangementer per sesong
     *
     * @param Int $sesong
     * @param boolean $filter
     * @return Arrangementer
     */
    public static function bySesong(Int $sesong, Filter $filter = null)
    {
        $filter = static::initFilter($filter);
        $filter->sesong($sesong);

        return new Arrangementer('alle', 0, $filter);
    }

    /**
     * Alle arrangementer av en gitt eier (fylke eller kommune)
     *
     * @param Int $sesong
     * @param kommune|fylke $eier
     * @return Arrangementer $arrangementer
     */
    public static function byEier($eier, Filter $filter = null)
    {
        $filter = static::initFilter();
        if (is_string($eier) || !in_array(get_class($eier), ['kommune', 'fylke', 'UKMNorge\Geografi\Fylke', 'UKMNorge\Geografi\Kommune'])) {
            throw new Exception('byEier krever at parameter 2 er enten kommune- eller fylke-objekt');
        }
        return static::byOmradeInfo('eier-' . strtolower(str_replace('UKMNorge\Geografi\\', '', get_class($eier))), $eier->getId(), $filter);
    }

    /**
     * Alle lokal-arrangement hvor en kommune er involvert
     * (er eier, eller med-arrangør)
     *
     * @param Kommune $kommune
     * @param Filter $filter
     * @return Arrangementer
     */
    public static function forKommune(Kommune $kommune, Filter $filter = null)
    {
        $filter = static::initFilter($filter);
        return static::byOmradeInfo('kommune', $kommune->getId(), $filter);
    }

    /**
     * Alle lokal-arrangement i et gitt fylke
     *
     * @param fylke $fylke
     * @param Filter $filter
     * @return Arrangementer
     */
    public static function iFylke(Fylke $fylke, Filter $filter = null)
    {
        $filter = static::initFilter($filter);
        return static::byOmradeInfo('fylke', $fylke->getId(), $filter);
    }


    /**
     * Alle fylkes-arrangement i et fylke
     *
     * @param Fylke $fylke
     * @param Filter $filter
     * @return Arrangementer
     */
    public static function forFylke(Fylke $fylke, Filter $filter = null)
    {
        $filter = static::initFilter($filter);
        return static::byEier($fylke, $filter);
    }

    /**
     * Hent Arrangement-collection for gitt område,
     * Bruker 2 parametre i stedet for område-objektet
     *
     * @param String $omrade_type
     * @param Int $omrade_id
     * @return Arrangementer
     */
    public static function byOmradeInfo(String $omrade_type, Int $omrade_id, Filter $filter = null)
    {
        $filter = static::initFilter($filter);
        return new Arrangementer($omrade_type, $omrade_id, $filter);
    }

    /**
     * Hent Arrangement-collection for gitt område
     *
     * @param Omrade $omrade
     * @return Arrangementer
     */
    public static function byOmrade(Omrade $omrade, Filter $filter = null)
    {
        $filter = static::initFilter($filter);
        return static::byOmradeInfo($omrade->getType(), $omrade->getForeignId(), $filter);
    }

    /**
     * Opprett eller oppdater filter
     *
     * @param Filter $filter
     * @return Filter
     */
    private static function initFilter(Filter $filter = null)
    {
        if (is_null($filter) || !$filter) {
            $filter = new Filter();
        }
        switch (get_called_class()) {
            case 'UKMNorge\Arrangement\Kommende':
                $filter->erKommende();
                break;
            case 'UKMNorge\Arrangement\Kommende':
                $filter->erTidligere();
                break;
        }
        return $filter;
    }


    /* IMPLEMENT */
    #public static function byPostnummer( Int $sesong, Int $postnummer ) {
    #    return static::byOmradeInfo( $sesong, 'postnummer', $postnummer);
    #}

}
