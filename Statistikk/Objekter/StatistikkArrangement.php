<?php

namespace UKMNorge\Statistikk\Objekter;

use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Statistikk\Objekter\StatistikkSuper;


class StatistikkArrangement extends StatistikkSuper {
    private $arrangement;

    public function __construct(Arrangement $arrangement) {
        // Check if the user has access to the arrangement
        $this->arrangement = $arrangement;
    }

    /**
    * Returnerer antall deltakere i arrangementet fra database.
    *
    * @return int The number of participants.
    */
    public function getAntallDeltakere() : int {
        $sql = new Query(
            "SELECT COUNT(*) as antall
            FROM (
                " . $this->getQueryArrangement($this->arrangement) . "
            ) AS subquery;",
            [
                'plId' => $this->arrangement->getId()
            ]
        );

        $res = $sql->run('array');
        return (int) intval($res['antall']);
    }

}