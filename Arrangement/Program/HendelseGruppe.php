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
    private $start = null;
    private $tag = null;

    public function __construct(Int $id, String $navn, String $beskrivelse = '', int $arrangementId, String $tag = null) {
        $this->id = $id;
        $this->navn = $navn;
        $this->beskrivelse = $beskrivelse;
        $this->arrangementId = $arrangementId;
        $this->tag = $tag;
    }

    public static function getById(Int $id) : HendelseGruppe {
        $query = new Query(
            "SELECT * FROM `hendelse_gruppe`
            WHERE `id` = '#id'",
            [
                'id' => $id,
            ]
        );

        $res = $query->run();
        if ($row = Query::fetch($res)) {
            return new HendelseGruppe(
                (int) $row['id'],
                $row['navn'],
                $row['beskrivelse'],
                (int) $row['arrangement_id'],
                $row['tag'] ?? null
            );
        }

        throw new Exception('Hendelsegruppe med ID ' . $id . ' finnes ikke');
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
                $row['arrangement_id'],
                $row['tag'] ?? null
            );
        }

        return $retHendelseGrupper;
    }

    private function fetchAlleHendelser() : array /* Hendelse[] */ {
        if (empty($this->hendelser)) {
            $query = new Query(
                "SELECT hendelse_id FROM `hendelse_gruppe_relation`
                WHERE `gruppe_id` = '#hendelse_gruppe_id'",
                [
                    'hendelse_gruppe_id' => $this->id,
                ]
            );

            $res = $query->run();
            while ($row = Query::fetch($res)) {
                try{
                    $this->addHendelse(new Hendelse((int) $row['hendelse_id']));
                } catch(Exception $e) {
                    // Hendelsen finnes ikke, eller er ikke aktiv
                    // Vi ignorerer dette, da det kan skje at en hendelse er slettet
                }
            }
        }

        return $this->hendelser;
    }

    public function getId() {
        return $this->id;
    }

    public function setNavn(String $navn) {
        $this->navn = $navn;
    }

    public function getNavn() {
        return $this->navn;
    }

    public function setTag(String $tag) {
        $this->tag = $tag;
    }

    public function getTag() {
        return $this->tag;
    }

    public function getStart() {
        if (is_null($this->start)) {
            $this->fetchAlleHendelser();
        }
        return $this->start;
    }

    public function setBeskrivelse(String $beskrivelse) {
        $this->beskrivelse = $beskrivelse;
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

        // Set eller oppdater start-tidspunktet for gruppen
        foreach($this->hendelser as $h) {
            if (is_null($this->start)) {
                $this->start = $h->getStart();
            } elseif ($h->getStart()->getTimestamp() < $this->start->getTimestamp()) {
                $this->start = $h->getStart();
            }
        }
    }

    public function getHendelser() {
        if (empty($this->hendelser)) {
            return $this->fetchAlleHendelser();
        }
        return $this->hendelser;
    }
}