<?php

namespace UKMNorge\Statistikk\Objekter;

use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Statistikk\Objekter\StatistikkSuper;
use UKMNorge\Statistikk\StatistikkManager;
use Exception;

class StatistikkArrangement extends StatistikkSuper {
    private Arrangement $arrangement;
    private StatistikkManager $sm;


    public function __construct(Arrangement $arrangement) {
        $this->sm = new StatistikkManager();
        // Check if the user has access to the arrangement
        if($this->sm::hasAccessToArrangement($arrangement) == false) {
            throw new Exception('Ingen tilgang til arrangement ' . $arrangement->getId(), 401);
        }
        $this->arrangement = $arrangement;
    }

    /**
    * Returnerer antall unike deltakere i arrangementet
    *
    * @return int antall unike deltakere.
    */
    public function getAntallUnikeDeltakere() : int {
        return $this->runAntall(true);
    }

    /**
    * Returnerer antall IKKE UNIKE deltakere i arrangementet
    *
    * @return int antall deltakere.
    */
    public function getAntallDeltakere() : int {
        return $this->runAntall();
    }

    private function runAntall($unique = false) : int {
        $select = $unique ? "COUNT(DISTINCT p_id)" : "COUNT(p_id)";
        $sql = new Query(
            "SELECT " . $select . " as antall
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