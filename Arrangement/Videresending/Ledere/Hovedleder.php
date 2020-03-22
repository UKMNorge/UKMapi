<?php

namespace UKMNorge\Arrangement\Videresending\Ledere;

class Hovedleder
{
    const TABLE = 'ukm_videresending_leder_hoved';

    var $leder_id;
    var $leder;
    var $dato;
    var $arrangement_fra;
    var $arrangement_til;

    /**
     * Opprett Hovedleder-objekt
     *
     * @param Int $arrangement_fra
     * @param Int $arrangement_til
     * @param Leder $leder
     * @param String $dato
     * @return Hovedleder
     */
    public function __construct(Int $arrangement_fra, Int $arrangement_til, Leder $leder, String $dato)
    {
        $this->leder = $leder;
        $this->leder_id = $leder->getId();
        $this->dato = $dato;
        $this->arrangement_fra = $arrangement_fra;
        $this->arrangement_til = $arrangement_til;
    }

    /**
     * Opprett Hovedleder-objekt fra databaseinfo
     *
     * @param Int $arrangement_fra
     * @param Int $arrangement_til
     * @param Int $leder_id
     * @param String $dato
     * @return Hovedleder
     */
    public static function getByData(Int $arrangement_fra, Int $arrangement_til, Int $leder_id, String $dato)
    {
        if ($leder_id == 0) {
            $leder = new Leder($arrangement_fra, $arrangement_til);
        } else {
            $leder = Leder::getById($leder_id);
        }
        return new Hovedleder(
            $arrangement_fra,
            $arrangement_til,
            $leder,
            $dato
        );
    }

    /**
     * Opprett tomt Hovedleder-objekt
     *
     * @param Int $arrangement_fra
     * @param Int $arrangement_til
     * @param String $dato
     * @return Hovedleder
     */
    public static function createEmpty(Int $arrangement_fra, Int $arrangement_til, String $dato)
    {
        return static::getByData(
            $arrangement_fra,
            $arrangement_til,
            0,
            $dato
        );
    }


    /**
     * Hovedlederens ID er vanligvis datoen (OBS!)
     * 
     * Det vil si at getId() ikke returnerer en unik ID, 
     * men en dato-identifikator for denne lederen.
     * Løsningen er sånn på grunn av arv, og javascript-implementasjonen.
     *
     * @return void
     */
    public function getId()
    {
        return $this->getDato();
    }

    /**
     * Hent leder-objektet
     *
     * @return Int
     */
    public function getLederId()
    {
        return $this->leder_id;
    }

    /**
     * Hent leder-objektet
     *
     * @return Leder
     */
    public function getLeder()
    {
        if (is_null($this->leder)) {
            $this->leder = Leder::getById($this->getLederId());
        }
        return $this->leder;
    }

    /**
     * Sett ny leder for gitt natt
     *
     * @param Leder $leder
     * @return self
     */
    public function setLeder(Leder $leder)
    {
        $this->leder = $leder;
        $this->leder_id = $leder->getId();
        return $this;
    }

    /**
     * Hent lederens navn
     * 
     * @see Leder::getNavn()
     *
     * @return String
     */
    public function getNavn()
    {
        return $this->getLeder()->getNavn();
    }

    /**
     * Hent hvilken dato lederen er hovedleder
     *
     * @return String $dato
     */
    public function getDato()
    {
        return $this->dato;
    }

    /**
     * Get the value of arrangement_fra
     */
    public function getArrangementFraId()
    {
        return $this->arrangement_fra;
    }

    /**
     * Get the value of arrangement_til
     */
    public function getArrangementTilId()
    {
        return $this->arrangement_til;
    }
}
