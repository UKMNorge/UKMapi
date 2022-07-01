<?php

namespace UKMNorge\Innslag\Venteliste;

use Exception;
use UKMNorge\Innslag\Personer\Person;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Geografi\Kommune;




class VentelistePerson
{
    /** @var Person */
    private $person;

    /** @var Arrangement */
    private $arrangement;

    // Gjelder bare hvis personen deltar i et arrangement som er fellesmønstring
    /** @var Kommune */
    private $kommune;


    /**
     * Opprett ny Venteliste-instance
     *
     * @param 
     */
    public function __construct(Person $person, Arrangement $arrangement, $kommune)
    {
        $this->person = $person;
        $this->arrangement = $arrangement;
        $this->kommune = $kommune;
    }

    /**
     * ID for en venteliste person
     * Det defineres av arrangement id og person id
     *
     * @return int
     */
    public function getId() {
        return $this->arrangement->getId() . $this->person->getId();
    }

    /**
     * Hent person
     * 
     * @return Person
     */
    public function getPerson()
    {
        return $this->person;
    }

    
    /**
     * Hent arrangement
     * 
     * @return Arrangement
     */
    public function getArrangement()
    {
        return $this->arrangement;
    }

    /**
     * Hent kommune
     * 
     * @return Kommune
     */
    public function getKommune()
    {
        return $this->kommune;
    }
    
}
