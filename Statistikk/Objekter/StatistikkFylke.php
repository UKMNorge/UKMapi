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

}