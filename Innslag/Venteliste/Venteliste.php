<?php

namespace UKMNorge\Innslag\Venteliste;

use Exception;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Innslag\Venteliste\Write;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Innslag\Personer\Person;
use UKMNorge\Geografi\Kommune;
use UKMNorge\Innslag\Typer\Typer;
use UKMNorge\Innslag\Venteliste\VentelistePerson;
use UKMNorge\Innslag\Write as WriteInnslag;
use UKMNorge\Innslag\Personer\Write as WritePerson;
use UKMNorge\Kommunikasjon\Mottaker;
use UKMNorge\Kommunikasjon\SMS;



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
     * @return VentelistePerson[]
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
    
    /**
     * Oppdater alle deltakere hvis maks antall påmeldte er endret
     * Denne metoden kan brukes hvis maksAntall er endret på arrangement og da blir flere ledige plasser og deltakere må ikke vente i venteliste dersom det er ledige plasser
     * 
     * @return void
     */
    public function updatePersoner() {
        $arrangement = new Arrangement($this->arrangementId);
        $maksAntall = $arrangement->getMaksAntallDeltagere();
        $antPersoner = $arrangement->getAntallPersoner();
        $added = 0;

        while(($antPersoner + $added) <  $maksAntall){
            // Det er ikke flere personer i venteliste
            if($this->meldPaaNeste() == false) {
                break;
            }
            $added++;
        }
    }

    /**
     * Send sms til en VentelistePerson
     * 
     * @return bool
     */
    private function sendSMS(VentelistePerson $vePerson) {
        $arrangement = new Arrangement($this->arrangementId);
        
        $avsender = 'UKM';
        $mobilnummer = $vePerson->getPerson()->getMobil();
        $melding = 'Hei, du har fått plass på arrangement ' .$arrangement->getNavn() . ' 
        
        - Med vennlig hilsen UKM Norge';
        
        SMS::setSystemId('wordpress', get_current_user());
        SMS::setArrangementId($this->arrangementId);
        
        $sms = new SMS($avsender); // (avsender) 
        
        try {
            $result = $sms->setMelding( $melding )->setMottaker( Mottaker::fraMobil( $mobilnummer ) )->send();
        } catch(Exception $e) {
            // bare for ukm.dev
            if($e->getCode() == 148005) {
                $result = true;
            }
            else {
                $result = false;
            }
        }
        return $result;
    }

    /**
     * Meld på deltaker i venteliste som står på første posisjon
     *
     * @param Ventelisteperson $vePerson
     * @return bool
     */
    public function meldPaaNeste() {
        $vePerson = $this->hentFirstPerson();

        if($vePerson == null) { 
            return false; 
        }

        try{
            if($this->meldPaa($vePerson)) {
                $this->removePerson($vePerson);
                $this->sendSMS($vePerson);
                return true;
            }
        }catch(Exception $e) {
            return false;
        }
        return false;
    }

    /**
     * Meld på en person i venteliste
     *
     * @param Ventelisteperson $vePerson
     * @return Int
     */
    public function meldPaa(Ventelisteperson $vePerson) {
        $kontaktperson = $vePerson->getPerson();

        // Hvilken kommune er innslaget fra
        $kommune = $vePerson->getKommune();
        $arrangement = new Arrangement($this->arrangementId);
        $type = Typer::getByKey('enkeltperson');
        $navn = $kontaktperson->getNavn();

        // Opprett innslaget
        $innslag = WriteInnslag::create($kommune, $arrangement, $type, $navn, $kontaktperson );
        $innslag->setSjanger('');
        $innslag->setStatus(8);
        $innslag->getPersoner()->leggTil( $kontaktperson );
        WriteInnslag::savePersoner( $innslag );
        $kontaktperson->setRolle( $type->getNavn() );

        WriteInnslag::save( $innslag );

        return true;
    }

    /**
     * Hent alle arrangementer personen står i venteliste
     *
     * @param Int $p_id
     * @return Arrangementer[]
     */
    public static function getArrangementerByPersonId($p_id) {
        $sql = new Query(
            "SELECT `pl_id`
            FROM `". static::TABLE ."`
			WHERE `p_id` = '#personId'",
            [
                'personId' => $p_id,
            ]
        );

        
        $res = $sql->run();
        $arrangementer = [];

        while( $row = Query::fetch( $res ) ) {
            $arrangementer[] = new Arrangement($row['pl_id']);
		}
        
        return $arrangementer;
    }

}
