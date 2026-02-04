<?php

namespace UKMNorge\Innslag\Context;

use Exception;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Innslag\Personer\Person;

require_once('UKM/Autoloader.php');


class Context
{
    var $type = null;

    var $sesong = null;
    var $monstring = null;
    var $innslag = null;
    var $forestilling = null;
    var $videresend_til = false;
    var $kontaktperson = null;
    var $delta_user_id = null;


    /**
     * Opprett mønstring-context
     *
     * @param Int $id
     * @param String $type
     * @param Int $sesong
     * @param Int $fylke_id (null hvis landsnivå)
     * @param String $kommuner (null hvis fylkes- eller landsnivå)
     * @return Context
     */
    public static function createMonstring(Int $id, String $type, Int $sesong, Int $fylke_id=null, array $kommuner=null)
    {
        $context = new Context('monstring');
        $context->monstring = new Monstring(
            $id,
            $type,
            $sesong,
            $fylke_id,
            $kommuner
        );
        return $context;
    }

    public static function createVideresending( Arrangement $fra, Arrangement $til ) {
        $context = new Context('videresending');
        $context->sesong = $fra->getSesong();
        $context->fra = static::_createMonstringFromArrangement( $fra );
        $context->til = static::_createMonstringFromArrangement( $til );
        return $context;
    }

    /**
     * Opprett mønstring-context fra arrangement
     * 
     * Brukes kun internt, da målsettingen er å kunne opprette
     * disse kontekstene uten å opprette arrangement-objektet først
     * (opprinnelig i alle fall)
     *
     * @param Arrangement $arrangement
     * @return Monstring
     */
    private static function _createMonstringFromArrangement( Arrangement $arrangement ) {

        if( $arrangement->getEierType() != 'land' ) {
            $fylke = $arrangement->getFylke()->getId();
        } else {
            $fylke = null;
        }

        if( $arrangement->getEierType() == 'kommune' ) {
            $kommuner = $arrangement->getKommuner()->getIdArray();
        } else {
            $kommuner = null;
        }

        return new Monstring(
            $arrangement->getId(),
            $arrangement->getType(),
            $arrangement->getSesong(),
            $fylke,
            $kommuner
        );
    }

    /**
     * Opprett innslag-context med Mønstring-context-objekt
     *
     * @param Int $id
     * @param String $type
     * @param Monstring $monstring
     * @return Context
     */
    public static function createInnslagWithMonstringContext( Int $id, String $type, Monstring $monstring ) {
        $context = new Context('innslag');
        $context->monstring = $monstring;
        $context->setInnslag(
            $id,
            $type
        );
        return $context;
    }

    /**
     * Opprett innslag-context
     *
     * @param Int $id
     * @param String $type
     * @param Int $monstring_id
     * @param String $monstring_type
     * @param Int $monstring_sesong
     * @param Int $fylke_id
     * @param Array $kommuner
     * @return Context
     */
    public static function createInnslag(Int $id, String $type, Int $monstring_id, String $monstring_type, Int $monstring_sesong, Int $fylke_id, array $kommuner)
    {
        $monstring_context = new Monstring(
            $monstring_id,
            $monstring_type,
            $monstring_sesong,
            $fylke_id,
            $kommuner
        );
        return static::createInnslagWithMonstringContext($id, $type, $monstring_context);
    }

    /**
     * Opprett hendelse (forestilling)-context
     *
     * @param Int $id
     * @param Monstring $context
     * @return Context
     */
    public static function createForestilling(Int $id, Monstring $context = null)
    {
        $forestilling_context = new Context('forestilling');
        $forestilling_context->forestilling = new Forestilling($id);
        if ($context !== null) {
            $forestilling_context->monstring = $context;
        }
        return $forestilling_context;
    }
    /**
     * Opprett hendelse-context
     *
     * @param Int $id
     * @param Monstring $monstring
     * @return Context
     */
    public static function createHendelse(Int $id, Monstring $context = null)
    {
        return static::createForestilling($id, $context);
    }

    /**
     * Opprett kontaktperson-context
     *
     * @param Int $id
     * @return Context
     */
    public static function createKontaktperson(Int $id)
    {
        $context = new Context('kontaktperson');
        $context->kontaktperson = new Kontaktperson($id);
        return $context;
    }

    /**
     * Opprett delta-bruker-context
     *
     * @param Int $user_id
     * @param Int $sesong
     * @return Context
     */
    public static function createDeltaUser(Int $user_id)
    {
        $context = new Context('deltauser');
        $context->delta_user_id = $user_id;
        return $context;
    }

    /**
     * Opprett sesong-context
     *
     * @param Int $sesong
     * @return Context
     */
    public static function createSesong(Int $sesong)
    {
        $context = new Context('sesong');
        $context->sesong = $sesong;
        return $context;
    }

    /**
     * Opprett ny context-instance
     *
     * @param String $type
     */
    public function __construct($type)
    {
        $this->type = $type;
    }

    /**
     * Hent type context
     *
     * @return String
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Hent møntring-context
     *
     * @return Monstring
     */
    public function getMonstring()
    {
        if( $this->getType() == 'videresending') {
            return $this->getTil();
        }
        
        return $this->monstring;
    }

    /**
     * Videresending: hvilken videresending er avsender?
     *
     * @return Monstring
     */
    public function getFra() {
        return $this->fra;
    }

    /**
     * Videresending: hvilken videresending er mottaker?
     *
     * @return Monstring
     */
    public function getTil() {
        return $this->til;
    }

    /**
     * Sett innslag-context
     *
     * @param Int $id
     * @param String $type
     * @return Context self
     */
    public function setInnslag( Int $id, String $type ) {
        $this->innslag = new Innslag( $id, $type );
        return $this;
    }

    /**
     * Sett mønstring-context
     *
     * @param Monstring $context
     * @return void
     */
    public function setMonstring( Monstring $context ) {
        $this->monstring = $context;
        return $this;
    }

    /**
     * Hent innslag-info
     *
     * @return Innslag
     */
    public function getInnslag()
    {
        return $this->innslag;
    }
    /**
     * Hent hendelse
     *
     * @return Hendelse
     */
    public function getForestilling()
    {
        return $this->forestilling;
    }
    /**
     * Hent kontaktperson
     *
     * @return Kontaktperson
     */
    public function getKontaktperson()
    {
        return $this->kontaktperson;
    }
    /**
     * Hent Delt brukerID
     *
     * @return Int $delta_user_id
     */
    public function getDeltaUserId()
    {
        return $this->delta_user_id;
    }

    /**
     * Hvilken sesong jobber vi med?
     *
     * @return Int $sesong
     * @throws Exception har ikke info
     */
    public function getSesong()
    {
        switch ($this->getType()) {
            case 'deltauser':
            case 'kontaktperson':
            case 'sesong':
            case 'videresending':
                return $this->sesong;
            case 'forestilling':
            case 'monstring':
                return $this->getMonstring()->getSesong();
            case 'innslag':
                if ($this->getMonstring() !== null) {
                    return $this->getMonstring()->getSesong();
                }
            default:
                throw new Exception(
                    'CONTEXT: Denne typen context (' . $this->getType() . ') støtter ikke getSesong()',
                    112001
                );
        }
    }

    /**
     * Sjekk at gitt objekt er gyldig Context\Context-objekt
     *
     * @param Any $object
     * @return Bool
     */
    public static function validateClass($object)
    {
        return is_object($object) && get_class($object) == 'UKMNorge\Innslag\Context\Context';
    }
}
