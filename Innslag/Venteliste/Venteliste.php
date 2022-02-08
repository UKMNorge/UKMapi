<?php

namespace UKMNorge\Innslag\Venteliste;

use Exception;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Innslag\Venteliste\Write;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Innslag\Personer\Person;
use UKMNorge\Geografi\Kommune;
use UKMNorge\Innslag\Venteliste\VentelistePerson;


class Venteliste
{
    /** @var VentelistePerson[] */
    private $personer = [];

    private $arrangementId = null;

    const TABLE = 'venteliste';

    public static function getLoadQuery()
    {
        return "SELECT *
            FROM `". static::TABLE ."`";
    }

    /**
     * Opprett ny Venteliste-instance
     *
     * @param 
     */
    public function __construct($arrangementId)
    {
        $this->arrangementId = $arrangementId;
        $this->_load();
    }

    /**
     * Last inn alle ventelistepersoner
     *
     * @return void
     */
    public function _load() {
        $qry = new Query(
			"SELECT * 
			FROM `venteliste` 
			WHERE `pl_id` = '#pl_id'
            ORDER BY deltager_id ASC",
			[
				'pl_id' => $this->arrangementId,
			]
		);

		$res = $qry->run();

        // Empty personer
		$this->personer = array();

		while ($row = Query::fetch($res)) {
            $person = Person::loadFromId( $row['p_id'] );
            $arrangement = Arrangement::getById($row['pl_id']);
            $arrangement = Arrangement::getById($row['pl_id']);
            $kommune = new Kommune($row['k_id']);

            $this->personer[] = new VentelistePerson($person, $arrangement, $kommune);
		}
    }

    /**
     * Opprett en instanse av Venteliste
     *
     * @return Venteliste
     */
    public static function getByArrangement($pl_id) : Venteliste {
        return new Venteliste($pl_id);
    }

    /**
     * Hent alle personer som array
     *
     * @return array
     */
    public function getAllPersoner() {
        return $this->personer;
    }

    /**
     * Sjekk om brukeren staar i venteliste for ett gitt arrangement
     *
     * @param Int $personId
     * @param Int $arrangementId
     * @throws Exception
     * @return VentelistePerson
     */
    public static function staarIVenteliste(Int $personId, Int $arrangementId) {
        echo $personId;
        $sql = new Query(
            Venteliste::getLoadQuery() . "
						WHERE `p_id` = '#personId' 
                        AND `pl_id` = '#arrangementId'",
            [
                'personId' => $personId,
                'arrangementId' => $arrangementId,
            ]
        );

        $res = $sql->getArray();
        if ($res) {
            $person = Person::loadFromId( $res['p_id'] );
            $arrangement = Arrangement::getById($res['pl_id']);
            $kommune = new Kommune($res['k_id']);

            return new VentelistePerson($person, $arrangement, $kommune);
        }

        return null;
    }
    
    /**
     * hent antall personer som er i venteliste
     *
     * @return int
     */
    public function getAntall() {
        return count($this->personer);
    }

    /**
     * Legg til ny person i Venteliste
     *
     * @return Venteliste
     */
    public function addPerson(Person $person, $kommune) {
        $arrangement = Arrangement::getById($this->arrangementId);
        $vePerson = new VentelistePerson($person, $arrangement, $kommune);

        Write::opprett($vePerson, $kommune);
        $this->_load();

        return $this;
    }
    
    /**
     * Fjern person fra Venteliste
     *
     *  @param VentelistePerson $personId
     * @return Venteliste
     */
    public function removePerson(VentelistePerson $vePerson) {
        Write::fjern($vePerson);
        $this->_load();
        
        return $this;
    }

    /**
     * Er person i venteliste for ett arrangement
     *
     * @return bool
     */
    public function erPersonIVenteliste(Person $person) {
        return $this->erPersonIdIVenteliste($person->getId());
    }

    /**
     * Er person i venteliste for ett arrangement etter person id
     *
     * @param Int $id
     * @return bool
     */
    public function erPersonIdIVenteliste($personId) {
        if($personId == null) {
            return false;
        }
        
        foreach($this->personer as $p) {
            if($p->getPerson()->getId() == $personId) {
                return true;
            }
        }
        return false;
    }

    /**
     * Flytt siste personen fra venteliste til arrangement
     *
     * @param Int $personId
     * @return VentelistePerson
     */
    public function hentFirstPerson() {
        if(count($this->personer) < 1) {
            return null;
        }
        // Remove from the array but not from the database
        // Call to removePerson() must be called afterwards to remove the person from the waiting list
        return array_shift($this->personer);
    }
    
    /**
     * Hent posisjon i venteliste for en person etter person id
     *
     * @param Int $id
     * @return Int
     */
    public function hentPersonPosisjon(Int $personId) {
        $count = 1;
        foreach($this->personer as $p) {
            if($p->getPerson()->getId() == $personId) {
                return $count;
            }
            $count++;
        }
        return null;
    }

}
