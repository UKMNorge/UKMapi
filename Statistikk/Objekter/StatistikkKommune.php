<?php

namespace UKMNorge\Statistikk\Objekter;

use DateTime;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Statistikk\Objekter\StatistikkSuper;
use UKMNorge\Statistikk\StatistikkManager;
use UKMNorge\Geografi\Kommune;
use UKMNorge\API\SSB\Klass;
use UKMNorge\Geografi\Fylke;

use Exception;

class StatistikkKommune extends StatistikkSuper {
    private Kommune $kommune;
    private int $season;
    private StatistikkManager $sm;

    public function __construct(Kommune $kommune, $season) {
        $this->sm = new StatistikkManager();
        $this->kommune = $kommune;
        $this->season = $season;
    }

    /**
    * Returnerer antall unike deltakere i kommune
    *
    * @return int antall unike deltakere.
    */
    public function getAntallUnikeDeltakere() : int {
        return $this->runAntall(true);
    }  

    /**
    * Returnerer antall IKKE UNIKE deltakere i kommune
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
                " . $this->getQueryKommune($this->season) . "
            ) AS subquery;",
            [
                'k_id' => $this->kommune->getId(),
                'season' => $this->season
            ]
        );   

        $res = $sql->run('array');
        return (int) intval($res['antall']);
    }

    /**
     * Returnerer alle kommuner hentet fra SSB
     * 
     * @param int $season
     * @return Kommune[]
     */
    public static function getAlleKommunerFraSSB(int $season, Fylke $kommunerIFylke = null) : Array {
        // Hent alle kommuner fra SSB
        $dataset = new Klass();
        // 131 er "Standard for kommuneinndeling"
        $dataset->setClassificationId("131");
        $startDato = new DateTime($season."-01-01");
        $sluttDato = new DateTime($season."-12-31");

        $dataset->setRange($startDato, $sluttDato);
        $dataset->includeFutureChanges(true);
        $kommuner = $dataset->getCodes();

        $retKommuner = [];
        foreach($kommuner->codes as $kommune) {
            try{
                $kommune = new Kommune($kommune->code);
            }catch(Exception $e) {
                continue;
            }
            
            if($kommunerIFylke && $kommune->getFylke() && $kommune->getFylke()->getId() == $kommunerIFylke->getId()) {
                $retKommuner[$kommune->getId()] = $kommune;
            }else if(!$kommunerIFylke) {
                $retKommuner[$kommune->getId()] = $kommune;
            }
            
        }

        return $retKommuner;
    }
}