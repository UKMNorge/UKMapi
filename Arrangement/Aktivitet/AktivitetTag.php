<?php

namespace UKMNorge\Arrangement\Aktivitet;

use UKMNorge\Database\SQL\Query;

use Exception;

class AktivitetTag {
    public const TABLE = 'aktivitet_tag';

    private int $tagId;
    private string $navn;
    private string $beskrivelse;
    private int $plId;

    // Pga avhengighet til tidspunkt kan kun hentes via aktivitet_deltakelse tabell og ikke ved bruk av ID.
    public function __construct($row) {
        $this->_load_by_row($row);
    }

    
    public static function getById(int $tagId) : AktivitetTag|null {
        $retArr = [];

        $query = new Query(
            "SELECT DISTINCT * from ". AktivitetTag::TABLE ."
            WHERE `tag_id` = '#tagId'",
            [
                'tagId' => $tagId            
            ]
        );

        $res = $query->run();
        if( Query::numRows($res) == 0 ) {
            return null;
        }
        // foreach
        return new AktivitetTag(Query::fetch($res));
    }

    public static function getAllByArrangement(int $plId) : array {
        $retArr = [];

        $query = new Query(
            "SELECT DISTINCT * from ". AktivitetTag::TABLE ."
            WHERE `pl_id` = '#plId'",
            [
                'plId' => $plId            
            ]
        );

        $res = $query->run();

        $tags = [];
        while ($row = Query::fetch($res)) {
            $tags[] = new AktivitetTag($row);
        }

        // sort by id
        usort($tags, function($a, $b) {
            return $b->getId() <=> $a->getId();
        });

        return $tags;
    }

    public function getId() {
        return $this->tagId;
    }

    public function getNavn() {
        return $this->navn;
    }

    public function getBeskrivelse() {
        return $this->beskrivelse;
    }

    public function getPlId() {
        return $this->plId;
    }

    public function _load_by_row($row) {
        $this->tagId = (int)$row['tag_id'];
        $this->navn = $row['navn'] ?? '';
        $this->beskrivelse = $row['beskrivelse'] ?? '';
        $this->plId = (int)$row['pl_id'];
    }

    public function getArrObj() {
        return [
            'id' => $this->getId(),
            'navn' => $this->getNavn(),
            'beskrivelse' => $this->getBeskrivelse(),
            'plId' => $this->getPlId(),
        ];
    }

}