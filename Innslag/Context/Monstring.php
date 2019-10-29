<?php

namespace UKMNorge\Innslag\Context;

require_once('UKM/Autoloader.php');

class Monstring
{
    var $id;
    var $type;
    var $sesong;
    var $kommuner;
    var $fylke;

    /**
     * Opprett mønstring-context
     *
     * @param Int $id
     * @param String $type
     * @param Int $sesong
     * @param Int $fylke (null hvis landsnivå)
     * @param String $kommuner (null hvis fylke- eller landsnivå)
     */
    public function __construct(Int $id, String $type, Int $sesong, Int $fylke=null, Array $kommuner=null)
    {
        $this->id = $id;
        $this->type = $type;
        $this->sesong = $sesong;
        $this->kommuner = $kommuner == null ? [] : $kommuner;
        $this->fylke = $fylke;
    }

    /**
     * Sett arrangementets ID
     *
     * @param Int $id
     * @return self
     */
    public function setId(Int $id)
    {
        $this->id = $id;
        return $this;
    }
    /**
     * Hent arrangementets ID
     *
     * @return Int $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sett arrangementets type
     *
     * @param String $type
     * @return self
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }
    /**
     * Hent arrangementets type
     *
     * @return String $type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sett arrangementets sesong
     *
     * @param Int $sesong
     * @return self
     */
    public function setSesong(Int $sesong)
    {
        $this->sesong = $sesong;
        return $this;
    }

    /**
     * Hent arrangementets sesong
     *
     * @return Int $seong
     */
    public function getSesong()
    {
        return $this->sesong;
    }

    /**
     * Angi kommuner i arragementet
     * (hvis lokal-arrangement)
     *
     * @param Array<Int> $kommuner
     * @return self
     */
    public function setKommuner(Array $kommuner)
    {
        $this->kommuner = $kommuner;
        return $this;
    }
    /**
     * Hent kommuner i arrangementet
     *
     * @return Array<Int>
     */
    public function getKommuner()
    {
        return $this->kommuner;
    }

    /**
     * Sett fylke ID
     *
     * @param Int $fylke
     * @return self
     */
    public function setFylke(Int $fylke_id)
    {
        if (!is_numeric($fylke_id)) {
            throw new Exception('CONTEXT_MONSTRING: setFylke krever numerisk fylke-id');
        }
        $this->fylke = $fylke_id;
        return $this;
    }
    /**
     * Hent arrangementets fylke-ID
     *
     * @return Int $fylke
     */
    public function getFylke()
    {
        return $this->fylke;
    }

    public function getVideresendTil()
    {
        throw new Exception('DEVELOPER ALERT: getVideresendTil() må implementeres for 2019. Varsle support@ukm.no');

        if (null == $this->videresend_til) {
            switch ($this->getType()) {
                case 'kommune':
                    $videresendTil = [];
                    foreach ($this->getKommuner() as $kommune_id) {
                        $kommune = new Kommune($kommune_id);
                        if (!isset($videresendTil[$kommune->getFylke()->getId()])) {
                            $fylke = monstringer_v2::fylke($kommune->getFylke(), $this->getSesong());
                            $videresendTil[$kommune->getFylke()->getId()] = $fylke->getId();
                        }
                    }
                    $this->videresend_til = $videresendTil;
                    break;
                case 'fylke':
                    $this->videresend_til = monstringer_v2::land($this->getSesong());
                    break;
                default:
                    throw new Exception(
                        'CONTEXT_MONSTRING: Kan ikke videresende fra landsnivå'
                    );
            }
        }
        return $this->videresend_til;
    }

    /**
     * Sjekk at gitt objekt er gyldig Context\Mønstring-objekt
     *
     * @param Any $object
     * @return Bool
     */
    public static function validateClass( $object ) {
        return is_object($object) && get_class($object) == 'UKMNorge\Innslag\Context\Monstring';
    }
}
