<?php

namespace UKMNorge\Arrangement\Skjema;

use Exception;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Innslag\Personer\Person;
use UKMNorge\Innslag\Personer\Personer;

require_once('UKM/Autoloader.php');

class SvarSett
{
    private $id;
    private $type;
    private $skjema_id;
    private $svar;


    /**
     * Hent et placeholder-svarsett
     * 
     * @param String $type
     * @param Int $id
     * @param Int $skjema_id
     * @return SvarSett
     */
    public static function getPlaceholder(String $type, Int $id, Int $skjema_id) {
        return new static(new Respondent($id, $type, $skjema_id));
    }

    public function __construct(Respondent $respondent)
    {
        $this->type = $respondent->getType();
        $this->id = $respondent->getId();
        $this->skjema_id = $respondent->getSkjemaId();
    }

    /**
     * ID til entiteten som har avgitt svaret
     * 
     * @return Int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Hvilken entitet har avgitt dette svaret?
     * 
     * @return String
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Hent skjemaID
     * 
     * @return Int
     */
    public function getSkjemaId() {
        return $this->skjema_id;
    }

    /**
     * Get the value of svar
     * 
     * @return Array $svar for denne respondenten
     */
    public function getAll()
    {
        if (is_null($this->svar)) {
            $this->load();
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
        $svar = $this->get($sporsmal_id);
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
    public function get(Int $sporsmal_id)
    {
        if (!isset($this->getAll()[$sporsmal_id])) {
            $this->svar[$sporsmal_id] = Svar::getPlaceholder($sporsmal_id, $this->getType(), $this->getId());
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

    private function load()
    {
        $this->svar = [];
        
        $select = new Query(
            "SELECT *
            FROM `ukm_videresending_skjema_svar`
            WHERE `skjema` = '#skjema'
            AND `#felt_fra` = '#fra'",
            [
                'skjema' => $this->getSkjemaId(),
                'fra' => $this->getId(),
                'felt_fra' => $this->getType() == 'arrangement' ? 'pl_fra' : 'p_fra'
            ]
        );
        $result = $select->run();
        while ($row = Query::fetch($result)) {
            $this->svar[$row['sporsmal']] = Svar::getFromDatabaseRow($row);
        }
    }
}
