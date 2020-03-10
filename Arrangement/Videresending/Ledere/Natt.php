<?php

namespace UKMNorge\Arrangement\Videresending\Ledere;

use stdClass;
use UKMNorge\Database\SQL\Query;

class Natt
{
    const TABLE = 'ukm_videresending_leder_natt';

    var $leder_id;
    var $natt_id;
    var $dato;
    var $sted;
    var $dag;
    var $maned;

    /**
     * Informasjon om hvor en leder overnatter gitt dato
     *
     * @param Leder $leder
     * @param String dato i dag_maned-format date('d_m')
     * @param String $sted
     */
    public function __construct(Int $leder_id, Int $natt_id, String $dato, String $sted)
    {
        $this->leder_id = $leder_id;
        $this->natt_id = $natt_id;
        $this->dato = $dato;
        $this->sted = $sted;

        $dato_info = explode('_', $dato);
		$this->dag = $dato_info[0];
		$this->maned = $dato_info[1];
    }

    /**
     * Hent ut overnattingen til en leder for gitt dag
     *
     * @param Leder $leder
     * @param String $dato
     * @return Natt
     */
    public static function getByLeder(Leder $leder, String $dato)
    {
        $query = new Query(
            "SELECT `n_id`, `sted` 
            FROM `" . static::TABLE . "`
            WHERE `leder` = '#leder' 
            AND `dato` = '#dato'",
            [
                'leder' => $leder->getId(),
                'dato' => $dato
            ]
        );

        $res = $query->getField();
        if (!$res) {
            $sted = 'ukjent';
            $id = 0;
        } else {
            $sted = $res['sted'];
            $id = intval($res['id']);
        }

        return new Natt($leder->getId(), $id, $dato, $sted);
    }

    /**
     * Opprett et natt-objekt fra databaserad
     *
     * @param Array $data
     * @return Natt
     */
    public function getByDatabaseRow( Array $data ) {
        return new Natt(
            intval($data['l_id']),
            intval($data['n_id']),
            $data['dato'],
            $data['sted']
        );
    }

    /**
     * Opprett et tomt natt-objekt 
     *
     * @param Int $leder_id
     * @param String $dato
     * @return Natt
     */
    public function getEmptyByDato( Int $leder_id, String $dato ) {
        return new Natt(
            $leder_id,
            0,
            $dato,
            'ukjent'
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
        $data->leder = $this->getLederId();
        $data->dag = $this->getDag();
        $data->mnd = $this->getManed();
        $data->dato = $this->getDato();
        $data->sted = $this->getSted();
        return $data;
    }

    /**
     * Nattens ID er vanligvis datoen (OBS!)
     * 
     * Det vil si at getId() ikke returnerer en unik ID, 
     * men en dato-identifikator for denne lederen.
     * Løsningen er sånn på grunn av arv, og javascript-implementasjonen.
     *
     * @return void
     */
    public function getId() {
        return $this->getDato();
    }

    /**
     * Finnes denne natten i databasen, eller er det et midlertidig objekt?
     *
     * @return Bool
     */
    public function eksisterer() {
        return $this->getDatabaseId() > 0;
    }

    /**
     * Hent leder ID
     * 
     * @return Int leder_id
     */
    public function getLederId()
    {
        return $this->leder_id;
    }

    /**
     * Sett leder ID
     *
     * @param Int $leder_id
     * @return self
     */
    public function setLederId(Int $leder_id)
    {
        $this->leder_id = $leder_id;

        return $this;
    }

    /**
     * Hent natt ID (intern db-referanse)
     */
    public function getDatabaseId()
    {
        return $this->natt_id;
    }

    /**
     * Sett natt ID (intern db-referanse)
     *
     * @param Int $natt_id
     * @return self
     */
    public function setNattId(Int $natt_id)
    {
        $this->natt_id = $natt_id;

        return $this;
    }

    /**
     * Hent dato for natten
     * 
     * @return String d_m (dd_mm)-format
     */
    public function getDato()
    {
        return $this->dato;
    }

    /**
     * Sett nattens dato
     *
     * @param String $dato
     * @return self
     */
    public function setDato(String $dato)
    {
        $this->dato = $dato;

        return $this;
    }

    /**
     * Hent hvilket sted lederen overnatter (key, ikke nicename)
     * 
     * @return String stedskey
     */
    public function getSted()
    {
        return $this->sted;
    }

    /**
     * Sett hvilket sted lederen overnatter
     *
     * @param String $sted
     * @return self
     */
    public function setSted(String $sted)
    {
        $this->sted = $sted;

        return $this;
    }

    /**
     * Hent hvilken dag dette er date(d)
     */ 
    public function getDag()
    {
        return $this->dag;
    }

    /**
     * Hent hvilken måned dette er date(m)
     */ 
    public function getManed()
    {
        return $this->maned;
    }
}
