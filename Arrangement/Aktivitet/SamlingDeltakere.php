<?php

namespace UKMNorge\Arrangement\Aktivitet;

use Exception;
use UKMNorge\Collection;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Arrangement\Aktivitet\AktivitetTidspunkt;

class SamlingTidspunkter extends Collection {
    private int $aktivitetTidspunktId = null;

    /**
     * Opprett ny samling
     *
     * @param Int $aktivitetId
     */
    public function __construct( Int $aktivitetTidspunktId ) {
        $this->aktivitetTidspunktId = $aktivitetTidspunktId;
                
        parent::__construct();
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