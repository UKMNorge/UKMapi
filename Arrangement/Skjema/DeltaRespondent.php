<?php
namespace UKMNorge\Arrangement\Skjema;

use UKMNorge\Database\SQL\Query;

use Exception;

class DeltaRespondent
{
    public $id;
    public $navn;
    public $etternavn;
    public $mobil;
    public $videresending_nominasjon;
    
    public function __construct($id, $navn, $etternavn, $mobil)
    {
        $this->id = $id;
        $this->navn = $navn;
        $this->etternavn = $etternavn;
        $this->mobil = $mobil;
        $this->videresending_nominasjon = false;
    }


    public static function loadByMobil($mobil) : DeltaRespondent|null{
        try {
            $query = new Query(
                "SELECT id, first_name AS navn, last_name AS etternavn, phone AS mobil FROM ukm_user WHERE phone = '#mobil'",
                ['mobil' => $mobil],
                'ukmdelta'
            );
            $res = $query->run('array');
            return new DeltaRespondent($res['id'], $res['navn'], $res['etternavn'], $res['mobil']);
        } catch(Exception $e) {
            return null;
        }
    }

    public static function loadById($id) : DeltaRespondent|null{
        try {
            $query = new Query(
                "SELECT id, first_name AS navn, last_name AS etternavn, phone AS mobil FROM ukm_user WHERE id = '#id'",
                ['id' => $id],
                'ukmdelta'
            );
            $res = $query->run('array');
            return new DeltaRespondent($res['id'], $res['navn'], $res['etternavn'], $res['mobil']);
        }
        catch(Exception $e) {
            return null;
        }
    }


    public function getId()
    {
        return $this->id;
    }

    public function getNavn()
    {
        return $this->navn;
    }

    public function getEtternavn()
    {
        return $this->etternavn;
    }

    public function getMobil()
    {
        return $this->mobil;
    }

    public function getNavnFullt()
    {
        return $this->navn . ' ' . $this->etternavn;
    }


}