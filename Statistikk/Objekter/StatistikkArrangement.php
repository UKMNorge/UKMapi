<?php

namespace UKMNorge\Statistikk\Objekter;

use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Database\SQL\Query;


class StatistikkArrangement {
    private $arrangement;

    public function __construct(Arrangement $arrangement) {
        $this->arrangement = $arrangement;
    }

    /**
     * Returnerer antall deltakere i arrangementet fra ukm_statistics database .
     *
     * @return int The number of participants.
     */
    public function getAntallDeltakere() : int {
        $sql = new Query(
            "SELECT count(ukm_statistics.stat_id) as antall
            FROM `ukm_statistics` 
            JOIN `smartukm_band` ON ukm_statistics.b_id = smartukm_band.b_id 
            WHERE smartukm_band.b_home_pl=#plId",
            [
                'plId' => $this->arrangement->getId()
            ]
        );

        $res = $sql->run('array');
        return intval($res['antall']);
    }

}