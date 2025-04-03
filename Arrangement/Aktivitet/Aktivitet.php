<?php

namespace UKMNorge\Arrangement\Aktivitet;

use Exception;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Database\SQL\Query;


class Aktivitet implements AktivitetInterface {
    public const TABLE = 'aktivitet';
    
    private int $aktivitetId;
    private string $navn;
    private string $sted;
    private string $beskrivelse;
    private int $plId;

    private $tidspunkter = null; // SamlingTidspunkter
    private $tags = null; // SamlingTags

    public function __construct($id_or_row) {
        if (is_numeric($id_or_row)) {
            $this->_load_by_id($id_or_row);
        } elseif (is_array($id_or_row)) {
            $this->_load_by_row($id_or_row);
        } else {
            throw new Exception('Aktivitet: Oppretting av objekt krever numerisk id eller databaserad');
        }
    }

    public static function getAllByArrangement(int $plId) : array {
        $sql = new Query(
            self::getLoadQry()
                . " WHERE `pl_id` = '#plId'",
            ['plId' => $plId]
        );

        $res = $sql->run();

        $counter = 0;
        $aktiviteter = [];
        while ($row = Query::fetch($res)) {
            $aktiviteter[] = new Aktivitet($row);
            $counter++;
        }

        // sort by id
        usort($aktiviteter, function($a, $b) {
            return $b->getId() <=> $a->getId();
        });
        return $aktiviteter;
    }

    public function getId() {
        return $this->aktivitetId;
    }

    public function getNavn() {
        return $this->navn;
    }

    public function getSted() {
        return $this->sted;
    }

    public function getBeskrivelse() {
        return $this->beskrivelse;
    }

    public function getPlId() {
        return $this->plId;
    }

    public function getArrangement() : Arrangement {
        return new Arrangement($this->plId);
    }

    /**
     * Hent alle tidspunkter for denne aktiviteten
     *
     * @return SamlingTidspunkter
     */
    public function getTidspunkter() {
        if($this->tidspunkter == null) {
            $this->tidspunkter = new SamlingTidspunkter( $this->getId() );
        }
        return $this->tidspunkter;
    }

    public function getTags() {
        if($this->tags == null) {
            $this->tags = new SamlingTags( $this->getId() );
        }
        return $this->tags;
    }

    public static function getLoadQry()
    {
        return "SELECT * FROM `". Aktivitet::TABLE ."` AS `aktivitet`";
    }

    private function _load_by_id($id) {
        $qry = new Query(
            self::getLoadQry()
                . "WHERE `aktivitet`.`aktivitet_id` = '#id'",
            array('id' => $id)
        );
        $res = $qry->run('array');
        if ($res) {
            $this->_load_by_row($res);
        } else {
            echo $qry->debug();
            throw new Exception('Aktivitet: Fant ikke aktivitet ' . $id);
        }
    }

    private function _load_by_row($row)
    {
        if (!is_array($row)) {
            throw new Exception('Aktivitet: _load_by_row krever dataarray!');
        }
        $this->aktivitetId = $row['aktivitet_id'];
        $this->navn =  $row['navn'];
        $this->sted = $row['sted'];
        $this->beskrivelse = $row['beskrivelse'];
        $this->plId = $row['pl_id'];
    }

    public function getArrObj() {
        $tidspunkter = [];
        $tags = [];

        foreach($this->getTidspunkter()->getAll() as $tidspunkt) {
            $tidspunkter[] = $tidspunkt->getArrObj();
        }

        foreach($this->getTags()->getAll() as $tag) {
            $tags[] = $tag->getArrObj();
        }
    
        return [
            'id' => $this->getId(),
            'navn' => $this->getNavn(),
            'sted' => $this->getSted(),
            'beskrivelse' => $this->getBeskrivelse(),
            'plId' => $this->getPlId(),
            'tidspunkter' => $tidspunkter,
            'tags' => $tags,
        ];
    }

}