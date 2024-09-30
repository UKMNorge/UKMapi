<?php

namespace UKMNorge\Statistikk\Objekter;

use DateTime;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Statistikk\Objekter\StatistikkSuper;
// use UKMNorge\Statistikk\StatistikkManager;
use UKMNorge\Geografi\Kommune;
use UKMNorge\API\SSB\Klass;
use UKMNorge\Geografi\Fylke;

use Exception;

class StatistikkKommune extends StatistikkSuper {
    private Kommune $kommune;
    private int $season;
    // private StatistikkManager $sm;

    public function __construct(Kommune $kommune, $season) {
        // $this->sm = new StatistikkManager();
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

    /**
     * Returnerer alle aktivitet i alle kommuner i en sesong 
     * Aktive kommuner regnes de som har minst 1 arrangement i sesongen
     * 
     * @return string SQL spørring
     */

     public static function getAlleKommunerAktivitet($season) {
        $retArr = [];
        $retArr['season'] = $season;
        $alleKommunerIFylke = StatistikkKommune::getAlleKommunerFraSSB($season);
        
        foreach($alleKommunerIFylke as $kommune) {
            $retArr['kommuner'][$kommune->getId()] = ['navn' => $kommune->getNavn(), 'aktivitet' => false];
        }

        $sql = new Query(
            "SELECT DISTINCT kommune_id, kommune_navn
            FROM (
                -- Before sommer 2024
                SELECT kommune.id AS kommune_id, kommune.name AS kommune_navn
                FROM smartukm_kommune AS kommune
                JOIN statistics_before_2024_smartukm_rel_pl_k AS pl_k ON pl_k.k_id = kommune.id
                WHERE pl_k.season='#season'

                UNION
                -- sommer 2024 and later
                SELECT stat.k_id AS kommune_id, kommune.name AS kommune_navn
                FROM ukm_statistics_from_2024 AS stat
                JOIN smartukm_kommune AS kommune ON kommune.id=stat.k_id
                WHERE season='#season' AND fylke='false' AND land='false'
            ) AS combined_results
            GROUP BY kommune_id",
            [
                'season' => $season
            ]
        );

        $res = $sql->run();
        

        while($row = Query::fetch($res)) {
            $retArr['kommuner'][$row['kommune_id']] = ['navn' => $row['kommune_navn'], 'aktivitet' => true];
        }

        return $retArr;
    }
}