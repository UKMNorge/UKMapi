<?php

namespace UKMNorge\Arrangement\Aktivitet;

use Exception;
use UKMNorge\Collection;
use UKMNorge\Database\SQL\Query;

class SamlingTags extends Collection {
    private $aktivitetId = null;

    /**
     * Opprett ny samling
     *
     * @param Int $aktivitetId
     */
    public function __construct( Int $aktivitetId ) {
        $this->aktivitetId = $aktivitetId;
    }

    public function _load() {
        $retArr = [];

        $query = new Query(
            "SELECT DISTINCT tag.* FROM ". AktivitetTag::TABLE ." as tag
            JOIN `aktivitet_tag_relation` AS `relation`
            ON tag.tag_id=relation.tag_id 
            WHERE `aktivitet_id` = '#aktivitetId'",
            [
                'aktivitetId' => $this->aktivitetId            
            ]
        );

        $res = $query->run();

        while( $row = Query::fetch( $res ) ) {
            $this->add(
                new AktivitetTag($row) 
            );
        }

    }


}