<?php

namespace UKMNorge\Kommunikasjon;

use Exception;
use UKMNorge\Database\SQL\Query;

class Mottaker
{
    var $navn = null;
    var $epost = null;
    var $mobil = null;

    const BOGUS_MOBIL = [44444444, 99999999];

    public function __construct(String $navn = null, String $epost = null, String $mobil = null)
    {
        $this->navn = $navn;
        $this->epost = $epost;

        // Hvis det er angitt et ugyldig mobilnummer: stooooopp!
        if (!is_null($mobil) && !static::erMobil($mobil)) {
            throw new Exception(
                $mobil . ' er ikke et gyldig mobilnummer.',
                149001
            );
        }
        $this->mobil = $mobil;
    }

    /**
     * Opprett mottaker fra e-post (og navn)
     *
     * @param String $epost
     * @param String $navn
     * @return Mottaker
     */
    public static function fraEpost(String $epost, String $navn = null)
    {
        return new Mottaker($navn, $epost);
    }

    /**
     * Opprett mottaker fra mobil (og navn)
     *
     * @param String $mobil
     * @param String $navn
     * @return Mottaker
     */
    public static function fraMobil(String $mobil, String $navn = null)
    {
        $mobil = (int) static::cleanMobil($mobil);

        return new Mottaker($navn, null, $mobil);
    }

    /**
     * Rensk opp (og kutt ned til evt gitt makslengde) en streng
     *
     * @param String $string
     * @param String $allow_pattern
     * @param Int $maxlength
     * @return String
     */
    public static function clean(String $string, String $allow_pattern = 'A-Za-z0-9-', Int $maxlength = false)
    {
        $string = preg_replace('/[^' . $allow_pattern . '.]/', '', strip_tags($string));
        if ($maxlength) {
            return substr($string, 0, $maxlength);
        }
        return $string;
    }

    /**
     * Rensk opp og kutt ned mobilnummer til Sveve-godkjent nummer (SMS-klasse)
     *
     * @param String $mobil
     * @return void
     */
    public static function cleanMobil(String $mobil)
    {

        // REMOVE FACEBOOK 3 SPECIAL CHARS
        if (strlen($mobil) == 11 && (int)$mobil == 0) {
            $mobil = substr($mobil, 3);
        }

        return static::clean($mobil, 'A-Za-z0-9-', SMS::AVSENDER_MAKSLENGDE);
    }

    /**
     * Hent mottakerens navn
     * 
     * @return String $navn
     */
    public function getNavn()
    {
        if (empty($this->navn)) {
            return $this->getEpost();
        }
        return $this->navn;
    }

    /**
     * Har vi navn på mottakeren?
     *
     * @return Bool
     */
    public function harNavn()
    {
        return strlen($this->getNavn()) > 0;
    }

    /**
     * Hent mottakerens e-postadresse
     * 
     * @return String $epost
     */
    public function getEpost()
    {
        return $this->epost;
    }

    /**
     * Hent mottakerens mobilnummer
     * 
     * @return Int $mobilnummer
     */
    public function getMobil()
    {
        return (int) $this->mobil;
    }

    /**
     * Er mobilnummeret et gyldig mobilnummer?
     *
     * @param Int $mobil
     * @return bool
     */
    public static function erMobil(Int $mobil)
    {
        //if( !4-serien && !9-serien) {
        if (!(90000000 < $mobil && $mobil < 99999999) && !(40000000 < $mobil && $mobil < 50000000)) {
            return false;
        }

        // Kjente dummy-nummer
        if (in_array($mobil, static::BOGUS_MOBIL)) {
            return false;
        }

        // Ikke 8 siffer
        if (empty($mobil) || $mobil == 0 || strlen((string)$mobil) != 8) {
            return false;
        }

        return true;
    }
}
