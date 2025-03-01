<?php

namespace UKMNorge\Arrangement\Videresending\Ledere;

use stdClass;
use Exception;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Sensitivt\Leder as LederSensitivt;
use UKMNorge\Arrangement\Arrangement;



class Leder
{
    const TABLE = 'ukm_videresending_leder';
    var $id;
    var $navn;
    var $epost;
    var $mobil;
    var $type;
    var $arrangement_fra;
    var $arrangement_til;
    var $netter;
    var $beskrivelse;
    var $godkjent; // null = ikke besvart, true = godkjent, false = avvist

    private $sensitivt = null;

    /**
     * Opprett nytt lederobjekt
     *
     * @param Int $fra
     * @param Int $til
     * @param Array $data
     */
    public function __construct(Int $fra, Int $til, array $data = null)
    {
        $this->arrangement_fra = $fra;
        $this->arrangement_til = $til;

        if (!is_null($data)) {
            $this->id = intval($data['l_id']);
            $this->navn = $data['l_navn'];
            $this->epost = $data['l_epost'];
            $this->mobil = intval($data['l_mobilnummer']);
            $this->type = $data['l_type'];
            $this->beskrivelse = $data['l_beskrivelse'];
            $this->godkjent = $data['l_godkjent'];
        }
    }

    /**
     * Finnes lederen i databasen? Eller har vi fått et dummy-objekt?
     *
     * @return Bool
     */
    public function eksisterer()
    {
        return $this->id > 0;
    }

    /**
     * Last inn en gitt leder fra type
     *
     * @param Int $fra
     * @param Int $til
     * @param String $type
     * @return Leder
     */
    public static function getByType(Int $fra, Int $til, String $type)
    {
        $query = new Query(
            "SELECT *
            FROM `" . static::TABLE . "`
            WHERE `arrangement_fra` = '#from'
            AND `arrangement_til` = '#to'
            AND `l_type` = '#type' ",
            [
                'from' => $fra,
                'to' => $til,
                'type' => $type
            ]
        );

        $data = $query->getArray();
        // Hvis ikke i databasen, returner dummy-leder
        if (!$data) {
            $leder = new Leder($fra, $til);
            $leder->setType($type);
            return $leder;
        }

        // Returner reelt leder-objekt
        return new Leder($fra, $til, $data);
    }

    /**
     * Last inn leder fra ID
     *
     * @param Int $id
     * @return Leder
     */
    public static function getById(Int $id)
    {
        $query = new Query(
            "SELECT *
            FROM `" . static::TABLE . "`
            WHERE `l_id` = '#id'",
            [
                'id' => $id
            ]
        );

        $data = $query->getArray();

        // Hvis ikke i databasen
        if (!$data) {
            throw new Exception(
                'Fant ikke leder ' . $id,
                160002
            );
        }

        // Returner reelt leder-objekt
        return Leder::loadFromDatabaseRow($data);
    }

    /**
     * Hent lederobjekt fra database-data
     *
     * @param Array $data
     * @return Leder
     */
    public static function loadFromDatabaseRow(array $data)
    {
        return new Leder(
            intval($data['arrangement_fra']),
            intval($data['arrangement_til']),
            $data
        );
    }

    /**
     * Hent et standardobjekt som kan brukes av twigJS
     *
     * @return stdClass
     */
    public function getJsObject()
    {
        $data = new stdClass();
        $data->ID = $this->getId();
        $data->navn = $this->getNavn();
        $data->epost = $this->getEpost();
        $data->mobilnummer = $this->getMobil();
        $data->type = $this->getType();
        $data->typeNavn = $this->getTypeNavn();
        $data->beskrivelse = $this->getBeskrivelse();
        $data->godkjent = $this->getGodkjent();

        $data->netter = [];
        foreach ($this->getNetter()->getAll() as $natt) {
            $data->netter[$natt->getId()] = $natt->getJsObject();
        }
        return $data;
    }

    /**
     * Hent alle overnattinger for lederen
     *
     * @return Netter
     */
    public function getNetter()
    {
        if (is_null($this->netter)) {
            $this->netter = new Netter($this);
        }
        return $this->netter;
    }

    /**
     * Hent inn én gitt natt for denne lederen
     *
     * @param String $dato
     * @return Natt
     */
    public function getNatt(String $dato)
    {
        return $this->getNetter()->get($dato);
    }

    /**
     * Get the value of id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the value of navn
     */
    public function getNavn()
    {
        return $this->navn;
    }

    /**
     * Get the value of epost
     */
    public function getEpost()
    {
        return $this->epost;
    }

    /**
     * Get the value of mobil
     */
    public function getMobil()
    {
        return $this->mobil;
    }

    /**
     * Get the value of type
     */
    public function getType()
    {
        return $this->type;
    }

    public function getTypeNavn()
    {
        switch ($this->getType()) {
            case 'hoved':
                return 'Hovedleder';
            case 'utstilling':
                return 'Reiseleder 2';
            case 'reise':
                return 'Reiseleder';
            case 'ledsager':
                return 'Ledsager';
            case 'turist':
                return 'Turist';
            case 'sykerom':
                return 'Sykerom - andre hotell behov';
        }
        throw new Exception(
            'Ukjent leder-type ' . $this->getType(),
            160001
        );
    }

    /**
     * Return true if the type is available outside hotel
     *
     * @return Bool
     */
    public function isAvailableOutsideHotel() {
        return $this->getType() != 'sykerom' || $this->getType() != 'turist';
    }

    /**
     * Get the value of arrangement_fra
     */
    public function getArrangementFraId()
    {
        return $this->arrangement_fra;
    }

    /**
     * Get Arrangement fra
     * @return Arrangement
     */
    public function getArrangementFra()
    {
        return new Arrangement($this->arrangement_fra);
    }

    /**
     * Get the value of arrangement_til
     */
    public function getArrangementTilId()
    {
        return $this->arrangement_til;
    }

    /**
     * Get Arrangement til
     * @return Arrangement
     */
    public function getArrangementTil()
    {
        return new Arrangement($this->arrangement_til);
    }

    /**
     * Set the value of id
     *
     * @param Int database-id
     * @return self
     */
    public function setId(Int $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set the value of navn
     *
     * @param String $navn
     * @return self
     */
    public function setNavn(String $navn)
    {
        $this->navn = $navn;

        return $this;
    }

    /**
     * Set the value of epost
     *
     * @param String $epost
     * @return self
     */
    public function setEpost(String $epost)
    {
        $this->epost = $epost;

        return $this;
    }

    /**
     * Set the value of mobil
     *
     * @param Int $mobil
     * @return self
     */
    public function setMobil(Int $mobil)
    {
        $this->mobil = $mobil;

        return $this;
    }

    /**
     * Set the value of type
     *
     * @param String $type
     * @return self
     */
    public function setType(String $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Set the value of arrangement_fra
     *
     * @param Int $arrangement_fra
     * @return self
     */
    public function setArrangement_fra(Int $arrangement_fra)
    {
        $this->arrangement_fra = $arrangement_fra;

        return $this;
    }

    /**
     * Set the value of arrangement_til
     *
     * @param Int $arrangement_til
     * @return self
     */
    public function setArrangement_til(Int $arrangement_til)
    {
        $this->arrangement_til = $arrangement_til;

        return $this;
    }

    /**
     * Set the value of beskrivelse
     *
     * @param String|null $beskrivelse
     * @return self
     */
    public function setBeskrivelse(String|null $beskrivelse) {
        $this->beskrivelse = $beskrivelse ? $beskrivelse : '';

        return $this;
    }

    /**
     * Get the value of beskrivelse
     * 
     * @return String
     */
    public function getBeskrivelse() {
        return $this->beskrivelse;
    }

    /**
     * Set the value of godkjent
     *
     * @param bool $godkjent
     * @return self
     */
    public function setGodkjent(bool $godkjent) {
        $this->godkjent = $godkjent;

        return $this;
    }

    /**
     * Get the value of godkjent
     * 
     * @return bool|null
     */
    public function getGodkjent() {
        return $this->godkjent;
    }

    /**
     * Hent samling for sensitive data for Leder
     * OBS: HEAVY LOGGING
     *
     * @return LederSensitivt
     */
    public function getSensitivt() {
        if (null == $this->sensitivt) {
            $this->sensitivt = new LederSensitivt($this->getId());
        }
        return $this->sensitivt;
    }
}
