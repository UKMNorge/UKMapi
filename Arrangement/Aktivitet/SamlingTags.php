<?php

namespace UKMNorge\Arrangement\Aktivitet;

use Exception;
use UKMNorge\Collection;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Arrangement\Aktivitet\AktivitetTidspunkt;

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
            "SELECT DISTINCT aktivitet_tag.* from ". AktivitetTag::TABLE ."
            JOIN `aktivitet_tag_relation` AS `relation` 
            WHERE `aktivitet_id` = '#aktivitetId'",
            [
                'aktivitetId' => $this->aktivitetId            
            ]
        );

        $res = $query->run();

        while( $row = Query::fetch( $res ) ) {
            $this->add(
                new AktivitetTidspunkt($row) 
            );
        }

    }


}