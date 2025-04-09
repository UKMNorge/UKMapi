<?php

namespace UKMNorge\Arrangement\Aktivitet;

use UKMNorge\Database\SQL\Query;

use Exception;
use DateTime;


class AktivitetKlokkeslett {
    public const TABLE = 'aktivitet_klokkeslett';

    private int $id;
    private string $navn;
    private DateTime $start;
    private DateTime $stop;
    private int $plId;

    public function __construct($row) {
        $this->_load_by_row($row);
    }

    public static function getByTidspunkt(AktivitetTidspunkt $tidspunkt) {
        $query = new Query(
            "SELECT DISTINCT kslett.* FROM ". AktivitetKlokkeslett::TABLE ." AS kslett 
            JOIN aktivitet_tidspunkt_klokkeslett_relation AS rel ON rel.klokkeslett_id=kslett.id
            WHERE rel.tidspunkt_id='#tidspunktId'",
            [
                'tidspunktId' => $tidspunkt->getId()            
            ]
        );

        $res = $query->run();
        if( Query::numRows($res) == 0 ) {
            return null;
        }

        return new AktivitetKlokkeslett(Query::fetch($res));
    }

    public static function getById(int $id) : AktivitetKlokkeslett|null {
        $query = new Query(
            "SELECT DISTINCT * from ". AktivitetKlokkeslett::TABLE ."
            WHERE `id` = '#id'",
            [
                'id' => $id            
            ]
        );

        $res = $query->run();
        if( Query::numRows($res) == 0 ) {
            return null;
        }

        return new AktivitetKlokkeslett(Query::fetch($res));
    }

    public static function getAllByArrangement(int $plId) : array {
        $query = new Query(
            "SELECT DISTINCT * from ". AktivitetKlokkeslett::TABLE ."
            WHERE `pl_id` = '#plId'",
            [
                'plId' => $plId            
            ]
        );

        $res = $query->run();

        $tags = [];
        while ($row = Query::fetch($res)) {
            $tags[] = new AktivitetKlokkeslett($row);
        }

        // sort by id
        usort($tags, function($a, $b) {
            return $b->getId() <=> $a->getId();
        });

        return $tags;
    }

    public function getId() {
        return $this->id;
    }

    public function getStart() {
        return $this->start;
    }

    public function getNavn() {
        return $this->navn;
    }

    public function getStop() {
        return $this->stop;
    }

    public function getPlId() {
        return $this->plId;
    }

    public function _load_by_row($row) {
        $this->id = (int)$row['id'];
        $this->navn = $row['navn'];
        $this->start = new DateTime($row['start']);
        $this->stop = new DateTime($row['stop']);
        $this->plId = (int)$row['pl_id'];
    }

    public function getArrObj() {
        return [
            'id' => $this->getId(),
            'navn' => $this->getNavn(),
            'start' => $this->getStart()->format('Y-m-d H:i:s'),
            'stop' => $this->getStop()->format('Y-m-d H:i:s'),
            'plId' => $this->getPlId(),
        ];
    }

}