<?php

namespace UKMNorge\Statistikk\Objekter;

use UKMNorge\Database\SQL\Query;
use UKMNorge\Statistikk\Objekter\StatistikkSuper;
use UKMNorge\Statistikk\StatistikkManager;
use UKMNorge\Geografi\Fylke;

use Exception;

class StatistikkFylke extends StatistikkSuper {
    private Fylke $fylke;
    private StatistikkManager $sm;
    private int $season;

    public function __construct(Fylke $fylke, $season) {
        $this->sm = new StatistikkManager();
        $this->fylke = $fylke;
        $this->season = $season;
    }

    /**
    * Returnerer antall unike deltakere i fylke
    *
    * @return int antall unike deltakere.
    */
    public function getAntallUnikeDeltakere() : int {
        return $this->runAntall(true);
    }

    /**
    * Returnerer antall IKKE UNIKE deltakere i fylke
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
                " . $this->getQueryFylke($this->season) . "
            ) AS subquery;",
            [
                'fylke_id' => $this->fylke->getId(),
                'season' => $this->season
            ]
        );   

        $res = $sql->run('array');
        return (int) intval($res['antall']);
    }






   
}