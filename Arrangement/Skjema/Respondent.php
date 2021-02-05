<?php

namespace UKMNorge\Arrangement\Skjema;

use Exception;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Innslag\Personer\Person;

class Respondent
{

    private $id;
    private $type;
    private $skjema_id;
    private $arrangement;
    private $person;
    private $svar;

    /**
     * Oppretter en ny respondent
     * 
     * @param Int $id
     * @param String <arrangement|person>
     */
    public function __construct(Int $id, String $type, Int $skjema_id)
    {
        $this->id = $id;
        $this->type = $type;
        $this->skjema_id = $skjema_id;
    }

    /**
     * Hent type respondent
     * 
     * @return String <arrangement|person>
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Hent respondent ID (foreign ID)
     * 
     * @return Int $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Hent responendentens svar
     * 
     * @return SvarSett
     */
    public function getSvar()
    {
        if (is_null($this->svar)) {
            $this->svar = new SvarSett($this);
        }
        return $this->svar;
    }

    /**
     * Har respondenten svart på skjemaet?
     * 
     * @return bool
     */
    public function harSvart()
    {
        return $this->getSvar()->harSvart();
    }

    /**
     * Hent respondentens objekt
     * 
     * @return Arrangement|Person
     */
    public function getObject()
    {
        switch ($this->getType()) {
            case 'arrangement':
                return $this->getArrangement();
            case 'person':
                return $this->getPerson();
        }
        throw new Exception(
            'Kan ikke hente respondent-objekt for type ' . $this->getType(),
            163001
        );
    }

    /**
     * Hent respondentens navn
     * 
     * @return String
     */
    public function getNavn()
    {
        switch ($this->getType()) {
            case 'arrangement':
                return $this->getArrangement()->getNavn();
            case 'person':
                return $this->getPerson()->getNavn();
        }
        throw new Exception(
            'Kan ikke hente navn for ukjent type ' . $this->getType(),
            163002
        );
    }

    /** 
     * Hent arrangement-objektet 
     * 
     * Pass på - her kan du gå på rekursiv-smell
     *
     * @return Arrangement
     */
    public function getArrangement()
    {
        if (is_null($this->arrangement)) {
            $this->arrangement = new Arrangement($this->getId());
        }
        return $this->arrangement;
    }

    /**
     * Hent person-objektet
     * 
     * @return Person
     */
    public function getPerson()
    {
        if (is_null($this->person)) {
            $this->person = new Person($this->getId());
        }
        return $this->person;
    }

    /**
     * Hent skjemaets ID
     * 
     * @return Int
     */
    public function getSkjemaId()
    {
        return $this->skjema_id;
    }
}
