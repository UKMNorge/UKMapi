<?php

namespace UKMNorge\Innslag\Personer;

use Exception;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Collection;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Innslag\Context\Context;
use UKMNorge\Innslag\Typer\Type;

require_once('UKM/Autoloader.php');

class Personer extends Collection
{
    var $context = null;

    var $personer = null;
    var $personer_videresendt = null;
    var $personer_ikke_videresendt = null;
    var $debug = false;

    /**
     * Opprett en ny collection
     *
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * Hent person med gitt ID
     *
     * @param Int $id
     * @return Person
     * @throws Exception not found
     **/
    public function get($id)
    {
        if (!is_numeric($id) && !Person::validateClass($id)) {
            throw new Exception(
                'Personer::get() krever Person-objekt eller numerisk ID som objekt',
                172003
            );
        }
        // Hvis person-objekt, let etter id-feltet
        if (Person::validateClass($id)) {
            $id = $id->getId();
        }

        foreach ($this->getAllInkludertIkkePameldte() as $person) {
            if ($person->getId() == $id) {
                return $person;
            }
        }

        throw new Exception(
            'PERSONER: Kunne ikke finne person ' . $id . ' i innslag ' . $this->getContext()->getInnslag()->getId(),
            106003
        );
    }

    /**
     * Hent alle personer (påmeldt aktivt arrangement)
     * 
     * Aktivt arrangement settes via context.
     * Når innslaget lastes inn via Arrangement->getInnslag().... 
     * er dette automatisk riktig satt på personer-collection
     *
     * @return Array<Person>
     */
    public function getAll()
    {
        return static::filterPameldte(
            $this->getContext()->getMonstring()->getId(),
            parent::getAll()
        );
    }

    /**
     * Hent alle personer som ikke er påmeldt aktivt arrangement
     * 
     * Aktivt arrangement settes via context.
     * Når innslaget lastes inn via Arrangement->getInnslag().... 
     * er dette automatisk riktig satt på titler-collection
     *
     * @return Array<Person>
     */
    public function getAllIkkePameldte()
    {
        return static::filterIkkePameldte(
            $this->getContext()->getMonstring()->getId(),
            parent::getAll()
        );
    }

    /**
     * Hent absolutt alle personer
     * 
     * Uavhengig om de er påmeldt aktivt arrangement eller ikke
     * Aktivt arrangement settes via context.
     * Når innslaget lastes inn via Arrangement->getInnslag().... 
     * er dette automatisk riktig satt på personer-collection
     *
     * @return Array<Person>
     */
    public function getAllInkludertIkkePameldte()
    {
        return parent::getAll();
    }

    /**
     * Filtrer og returner personer påmeldt gitt arrangement
     *
     * @param Int $arrangement_id
     * @param Array<Person> $personer
     * @return Array<Person>
     */
    public static function filterPameldte(Int $arrangement_id, array $personer)
    {
        $filtered = [];
        foreach ($personer as $person) {
            if ($person->erPameldt($arrangement_id)) {
                $filtered[] = $person;
            }
        }
        return $filtered;
    }

    /**
     * Filtrer og returner personer som ikke er påmeldt gitt arrangement
     *
     * @param Int $arrangement_id
     * @param Array<Person> $personer
     * @return Array<Person>
     */
    public static function filterIkkePameldte(Int $arrangement_id, array $personer)
    {
        $filtered = [];
        foreach ($personer as $person) {
            if (!$person->erPameldt($arrangement_id)) {
                $filtered[] = $person;
            }
        }
        return $filtered;
    }

    /**
     * Hent ID-liste for alle personer i getAll()
     *
     * @return Array<Int> 
     */
    public function getAllIds()
    {
        return static::getIdList($this->getAll());
    }

    /**
     * Hent ID-liste for gitte personer
     *
     * @param Array<Person> $personer
     * @return Arra
     */
    public static function getIdList(array $personer)
    {
        return array_keys($personer);
    }


    /**
     * Finn ut hvor mange som er innenfor gitt aldersspenn
     *
     * @param Int $start
     * @param Int $stop
     * @return Int antall innenfor
     */
    public function getAntallInnenforAlder( Int $start=10, Int $stop=20) {
        $innenfor = 0;
        foreach( $this->getAll() as $person ) {
            if( $person->getAlderTall() >= $start && $person->getAlderTall() <= $stop ) {
                $innenfor++;
            }
        }
        return $innenfor;
    }

    /**
     * Finn ut hvor mange som er innenfor gitt aldersspenn (i prosent av antall detltakere)
     *
     * @param Int $start
     * @param Int $stop
     * @return Float $prosetn
     */
    public function getProsentInnenforAlder( Int $start=10, Int $stop=20) {
        return round(
            (100 / $this->getAntall()) * $this->getAntallInnenforAlder($start,$stop),
            2
        );
    }

    /**
     * getSingle
     * Hent én enkelt person fra innslaget. 
     * Er beregnet for tittelløse innslag, som aldri har mer enn én person
     *
     * @return Person $person
     * @throws Exception hvis innslaget har mer enn én person
     **/
    public function getSingle()
    {
        if (1 < $this->getAntall()) {
            throw new Exception('PERSON_V2: getSingle() er kun ment for bruk med tittelløse innslag som har ett personobjekt. '
                . 'Dette innslaget har ' . $this->getAntall() . ' personer');
        }
        $all = $this->getAll();
        return end($all); // and only...
    }

    /**
     * Legg til person i collection
     *
     * @param Person $person
     * @return self
     */
    public function leggTil($person)
    {
        try {
            Person::validateClass($person);
        } catch (Exception $e) {
            throw new Exception(
                'Kunne ikke legge til person. ' . $e->getMessage(),
                106001
            );
        }

        // Hvis personen allerede er lagt til kan vi skippe resten
        if ($this->har($person)) {
            return true;
        }

        // Gi personen riktig context (hent fra collection, samme som new person herfra)
        $person->setContext($this->getContext());

        // Legg til at personen skal være påmeldt arrangementet
        $person->addPameldt($person->getContext()->getMonstring()->getId());

        // Legg til personen i collection
        parent::add($person);

        return true;
    }

    /**
     * Fjern person fra collection
     *
     * @param Person $person
     * @return self
     */
    public function fjern($person)
    {
        try {
            Write::validerPerson($person);
        } catch (Exception $e) {
            throw new Exception(
                'Kunne ikke fjerne person. ' . $e->getMessage(),
                106002
            );
        }

        if (!$this->har($person)) {
            return true;
        }

        parent::fjern($person);

        return true;
    }

    /**
     * Last inn alle personer tilhørende innslaget
     * 
     * @return void
     **/
    public function _load()
    {
        // 2020 regionreform gir ny beregning av personer. Strengt tatt samme løsning
        // som smartukm_fylkestep, men nå rendyrket i egen tabell for å sikre at ikke APIv1
        // tuller til relasjoner i ny sesong. Nå brukes relasjonstabellen for ALLE arrangementer,
        // uavhengig om innslaget er videresendt eller ikke.
        if ($this->getContext()->getSesong() > 2019) {
            $SQL = new Query(
                "SELECT 
                    `participant`.*, 
                    `relation`.`instrument`,
                    `relation`.`instrument_object`,
                    GROUP_CONCAT(`arrangement`.`arrangement_id`) AS `arrangementer`,
                    `band`.`bt_id`
                FROM `smartukm_participant` AS `participant` 
                JOIN `smartukm_rel_b_p` AS `relation` 
                    ON (`relation`.`p_id` = `participant`.`p_id`)
                JOIN `smartukm_band` AS `band`
                    ON(`band`.`b_id` = `relation`.`b_id`)
                LEFT JOIN `ukm_rel_arrangement_person` AS `arrangement`
                    ON(`arrangement`.`innslag_id` = '#innslag' AND `arrangement`.`person_id` = `participant`.`p_id`)
                WHERE `relation`.`b_id` = '#innslag'
                GROUP BY `participant`.`p_id`
                ORDER BY 
                    `participant`.`p_firstname` ASC, 
                    `participant`.`p_lastname` ASC",
                [
                    'innslag' => $this->getContext()->getInnslag()->getId()
                ]
            );
        } else {
            $SQL = new Query(
                "SELECT 
                    `participant`.*, 
                    `relation`.`instrument`,
                    `relation`.`instrument_object`,
                    GROUP_CONCAT(`smartukm_fylkestep_p`.`pl_id`) AS `pl_ids`,
                    `band`.`bt_id`
                FROM `smartukm_participant` AS `participant` 
                JOIN `smartukm_rel_b_p` AS `relation` 
                    ON (`relation`.`p_id` = `participant`.`p_id`) 
                LEFT JOIN `smartukm_fylkestep_p`
                    ON(`smartukm_fylkestep_p`.`b_id` = '#innslag' AND `smartukm_fylkestep_p`.`p_id` = `participant`.`p_id`)
                JOIN `smartukm_band` AS `band`
                    ON(`band`.`b_id` = `relation`.`b_id`)
                WHERE `relation`.`b_id` = '#innslag'
                GROUP BY `participant`.`p_id`
                ORDER BY 
                    `participant`.`p_firstname` ASC, 
                    `participant`.`p_lastname` ASC",
                [
                    'innslag' => $this->getContext()->getInnslag()->getId()
                ]
            );
        }
        $res = $SQL->run();
        if (isset($_GET['debug']) || $this->debug) {
            echo $SQL->debug();
        }
        if ($res === false) {
            throw new Exception("PERSONER_COLLECTION: Klarte ikke hente personer og roller - kan databaseskjema være utdatert?" . $SQL->debug());
        }
        while ($r = Query::fetch($res)) {
            $person = new Person($r);
            $person->setContext($this->getContext());
            $this->add($person);
        }
    }

    /**
     * Hent innslagets / personers context
     *
     * @return Context
     */
    public function getContext()
    {
        return $this->context;
    }
}
