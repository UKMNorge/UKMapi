<?php

namespace UKMNorge\Arrangement\Skjema;

use UKMNorge\Database\SQL\Query;

require_once('UKM/Autoloader.php');

class SvarSett
{
    private $skjema = 0;
    private $skjema_objekt;
    private $fra = 0;
    private $svar = [];
    private $loaded = false;

    
    public function __construct(Int $skjema, Int $pl_id_fra)
    {
        $this->skjema = $skjema;
        $this->fra = $pl_id_fra;
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
     * @return Int $pl_id_fra
     */
    public function getFra()
    {
        return $this->fra;
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
                AND `pl_fra` = '#fra'",
                [
                    'skjema' => $this->getSkjemaId(),
                    'fra' => $this->getFra()
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
