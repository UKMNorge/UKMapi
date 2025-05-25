<?php

// Create simple class to represent a group of events
namespace UKMNorge\Arrangement\Program;

use UKMNorge\Database\SQL\Query;
use Exception;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Arrangement\Program\Hendelse;


class HendelseGruppe
{
    private $id = null;
    private $navn = null;
    private $beskrivelse = null;
    private $hendelser = [];
    private $arrangementId = null;

    public function __construct(Int $id, String $navn, String $beskrivelse = '', int $arrangementId) {
        $this->id = $id;
        $this->navn = $navn;
        $this->beskrivelse = $beskrivelse;
        $this->arrangementId = $arrangementId;
    }

    public static function getAlleByArrangement(Arrangement $arrangement) : array /* HendelseGruppe[] */ {
        $retHendelseGrupper = [];

        $query = new Query(
            "SELECT * FROM `hendelse_gruppe`
            WHERE `arrangement_id` = '#arrangement_id'",
            [
                'arrangement_id' => $arrangement->getId(),
            ]
        );


        $res = $query->run();
        while ($row = Query::fetch($res)) {
            $retHendelseGrupper[] = new HendelseGruppe(
                (int) $row['id'],
                $row['navn'],
                $row['beskrivelse'],
                $row['arrangement_id']
            );
        }

        return $retHendelseGrupper;
    }

    private function fetchAlleHendelser() : array /* Hendelse[] */ {
        if (empty($this->hendelser)) {
            $query = new Query(
                "SELECT c_id FROM `smartukm_concert`
                WHERE `gruppe_id` = '#hendelse_gruppe_id'",
                [
                    'hendelse_gruppe_id' => $this->id,
                ]
            );

            $res = $query->run();
            while ($row = Query::fetch($res)) {
                $this->addHendelse(new Hendelse((int) $row['c_id']));
            }
        }

        return $this->hendelser;
    }

    public function getId() {
        return $this->id;
    }

    public function getNavn() {
        return $this->navn;
    }

    public function getBeskrivelse() {
        return $this->beskrivelse;
    }

    public function getArrangement() : Arrangement {
        return new Arrangement($this->arrangementId);
    }

    public function getArrangementId() {
        return $this->arrangementId;
    }

    private function addHendelse(Hendelse $hendelse) {
        if (!isset($this->hendelser[$hendelse->getId()])) {
            $this->hendelser[$hendelse->getId()] = $hendelse;
        }
    }

    public function getHendelser() {
        if (empty($this->hendelser)) {
            return $this->fetchAlleHendelser();
        }
        return $this->hendelser;
    }
}