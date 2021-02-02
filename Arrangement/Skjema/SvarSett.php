<?php

namespace UKMNorge\Arrangement\Skjema;

use Exception;
use UKMNorge\Database\SQL\Query;

require_once('UKM/Autoloader.php');

class SvarSett
{
    private $skjema = 0;
    private $skjema_objekt;
    private $fra = 0;
    private $svar = [];
    private $loaded = false;
    private $type;


    public static function getForArrangement( Int $arrangement_id, Int $skjema ) {
        return new static($skjema, $arrangement_id);
    }

    public static function getForPerson( Int $person_id, Int $skjema ) {
        return new static($skjema, null, $person_id);
    }
    
    public function __construct(Int $skjema, Int $pl_id_fra = null, Int $person_id = null)
    {
        $this->skjema = $skjema;
        if( !is_null($pl_id_fra) && !is_null($person_id)) {
            throw new Exception(
                'Kan ikke hente svarsett for arrangement og deltaker samtidig.',
                153001
            );
        }
        if( !is_null( $pl_id_fra ) ) {
            $this->type = 'arrangement';
            $this->fra = $pl_id_fra;
        } 
        if( !is_null($person_id)) {
            $this->type = 'person';
            $this->fra = $person_id;
        }
    }

    /**
     * Hent skjema-ID
     * 
     * @return Int $skjema_id
     */
    public function getSkjemaId()
    {
        return $this->skjema;
    }

    /**
     * Hent skjema-objektet
     *
     * @return Skjema
     */
    public function getSkjema()
    {
        if (is_null($this->skjema_objekt)) {
            $this->skjema_objekt = Skjema::getFromId($this->getSkjemaId());
        }
        return $this->skjema_objekt;
    }

    /**
     * Hent eier av svar-settet
     * (Hvem har svart?)
     * 
     * @return Int $id
     */
    public function getFra()
    {
        return $this->fra;
    }

    /**
     * Hvilken entitet har svart? Person / Arrangement
     * 
     * @return String
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Er dette svarsettet for et arrangement?
     * 
     * @return bool
     */
    public function erArrangement() {
        return $this->getType() == 'arrangement';
    }

    /**
     * Er dette svarsettet for en person?
     * 
     * @return bool
     */
    public function erPerson() {
        return $this->getType() == 'person';
    }

    /**
     * Get the value of svar
     * 
     * @return Array $svar for denne respondenten
     */
    public function getAll()
    {
        if (!$this->_isLoaded()) {
            $select = new Query(
                "SELECT *
                FROM `ukm_videresending_skjema_svar`
                WHERE `skjema` = '#skjema'
                AND `#felt_fra` = '#fra'",
                [
                    'skjema' => $this->getSkjemaId(),
                    'fra' => $this->getFra(),
                    'felt_fra' => $this->erArrangement() ? 'pl_fra' : 'p_fra'
                ]
            );
            $result = $select->run();
            while ($row = Query::fetch($result)) {
                $this->svar[$row['sporsmal']] = Svar::createFromDatabase($row);
            }
            $this->loaded = true;
        }
        return $this->svar;
    }

    /**
     * Angi et nytt svar
     *
     * @param Int $sporsmal_id
     * @param String|Array $value
     * @return self
     */
    public function setSvar(Int $sporsmal_id, $value)
    {
        $svar = $this->getSvar($sporsmal_id);
        $svar->setValue($value);
        return $this;
    }

    /**
     * Hent et gitt svar
     * 
     * Vil alltid gi et svar-objekt tilbake, uansett om du har et svar eller ikke
     *
     * @param Int $sporsmal_id
     * @return Svar
     */
    public function getSvar(Int $sporsmal_id)
    {
        if (!isset($this->getAll()[$sporsmal_id])) {
            $this->svar[$sporsmal_id] = Svar::createForSvar($sporsmal_id, $this->getFra());
        }
        return $this->svar[$sporsmal_id];
    }

    /**
     * Har vi noen som helst svar fra denne avsenderen?
     * 
     * @return Bool $har_svart
     */
    public function harSvart()
    {
        return sizeof($this->getAll()) > 0;
    }

    /**
     * Get the value of loaded
     * 
     * @return Bool har lastet inn skjemadata
     */
    private function _isLoaded()
    {
        return $this->loaded;
    }
}
