<?php

namespace UKMNorge\Arrangement\Aktivitet;

use Exception;
use UKMNorge\Collection;
use UKMNorge\Database\SQL\Query;

class SamlingDeltakere extends Collection {
    private $aktivitetTidspunktId = null;

    /**
     * Opprett ny samling
     *
     * @param Int $aktivitetId
     */
    public function __construct( $aktivitetTidspunktId) {
        $this->aktivitetTidspunktId = $aktivitetTidspunktId;
    }

    public function _load() {
        $query = new Query(
            "SELECT * 
            FROM `aktivitet_deltakelse`
            WHERE `tidspunkt_id` = '#aktivitetTidspunktId'",
            [
                'aktivitetTidspunktId' => $this->aktivitetTidspunktId            
            ]
        );
        $res = $query->run();

        while( $row = Query::fetch( $res ) ) {
            $this->add(
                new AktivitetDeltaker($row)
            );
        }
    }

}