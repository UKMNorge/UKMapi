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
use UKMNorge\Innslag\Typer\Typer;

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
                'k_ids' => $this->getAlleKommuneIds(),
                'season' => $this->season
            ]
        );   

        $res = $sql->run('array');
        return (int) intval($res['antall']);
    }

    public function getAldersfordeling() : array {
        $seasonDate = new DateTime($this->season.'-12-31');

        $sql = new Query(
            "SELECT 
                age, 
                COUNT(*) AS participant_count 
            FROM (SELECT 
                DISTINCT participant.p_id, 
                participant.p_dob,
                TIMESTAMPDIFF(YEAR, 
                    FROM_UNIXTIME(participant.p_dob),
                    FROM_UNIXTIME(#dateSeas))
                AS age
            FROM (
                " . $this->getQueryKommune($this->season) . "
            ) AS subquery
                JOIN statistics_before_2024_smartukm_participant AS participant
                ON subquery.p_id = participant.p_id
                ) AS age_subquery
                GROUP BY 
                    age
                ORDER BY 
                    age;
                ",
                [
                    'k_ids' => $this->getAlleKommuneIds(),
                    'season' => $this->season,
                    'dateSeas' => $seasonDate->getTimestamp()
                ]
        );

        $retArr = [];
        $res = $sql->run();

        while($row = Query::fetch($res)) {
            $retArr[] = [
                'age' => $row['age'],
                'antall' => $row['participant_count']
            ];
        }

        return $retArr;
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

    /**
     * Returnerer kjønnsfordeling i kommune
     * 
     * @param int $season
     * @return string SQL spørring
     */
    public function getKjonnsfordeling() {
        $sql = new Query(
            "SELECT p_id, firstname  
            FROM (
                SELECT participant.p_id, participant.p_firstname as firstname
                FROM (
                    " . $this->getQueryKommune($this->season) . "
                    ) AS subquery
                JOIN statistics_before_2024_smartukm_participant AS participant ON participant.p_id=subquery.p_id
            ) AS subqueryOut GROUP BY p_id;
            ",
            [
                'k_ids' => $this->getAlleKommuneIds(),
                'season' => $this->season
            ]
        );

        $retArr = [];
        $res = $sql->run();
        // For each result from $sql call getKjonnByName()
        while($row = Query::fetch($res)) {
            $kjonn = $this->getKjonnByName($row['firstname']);
            $retArr[$kjonn] = 1 + ($retArr[$kjonn] ?? 0);
        }


        return $retArr;
    }

    

    /**
     * Returnerer antall innslag i kommune fordelt på sjanger
     * 
     * OBS: Det hentes innslag kun fra arrangementer på kommuner
     * 
     * @return array[] An array of arrays with keys 'antall' and 'type_navn'.
    */
    public function getSjangerFordeling() : array {
        // > 2019 innslag fra ukm_rel_arrangement_person og fra juli 2024 brukes tabellen ukm_statistics_from_2024
        if($this->season > 2019) {
            $sql = new Query("SELECT 
                DISTINCT innslag.b_id, 
                innslag.bt_id, 
                innslag.b_kategori, 
                arrpers.arrangement_id
            FROM statistics_before_2024_ukm_rel_arrangement_person AS arrpers 
            JOIN statistics_before_2024_smartukm_band AS innslag ON innslag.b_id=arrpers.innslag_id 
            JOIN statistics_before_2024_smartukm_place AS place ON place.pl_id=arrpers.arrangement_id
            JOIN statistics_before_2024_smartukm_rel_pl_k AS rel_kommune ON rel_kommune.pl_id=place.pl_id
            JOIN smartukm_kommune AS kommune ON kommune.id=rel_kommune.k_id
            WHERE kommune.id IN (#k_ids) AND 
                innslag.b_kommune = IN (#k_ids) AND
                place.season='#season' AND 
                innslag.b_status = 8

            UNION 

            SELECT DISTINCT innslag.b_id, innslag.bt_id, innslag.b_kategori, stat.pl_id 
            FROM ukm_statistics_from_2024 AS stat 
            JOIN statistics_before_2024_smartukm_band AS innslag ON innslag.b_id=stat.b_id
            JOIN statistics_before_2024_smartukm_place AS place ON place.pl_id=stat.pl_id 
            WHERE stat.k_id IN (#k_ids)
                AND innslag.b_kommune IN (#k_ids)
                AND stat.fylke='false'
                AND place.season='#season' 
                AND innslag.b_status = 8",
                [
                    'k_ids' => $this->getAlleKommuneIds(),
                    'season' => $this->season
                ]
            );
        }
        // Before 2019 brukes statistics_before_2024_smartukm_rel_pl_b tabell
        else {
            $sql = new Query("SELECT
                    DISTINCT innslag.b_id,
                    innslag.bt_id,
                    innslag.b_kategori,
                    rel_pl_b.pl_id
                FROM 
                    statistics_before_2024_smartukm_band AS innslag
                JOIN statistics_before_2024_smartukm_rel_pl_b AS rel_pl_b ON rel_pl_b.b_id = innslag.b_id
                JOIN statistics_before_2024_smartukm_place as place on place.pl_id=rel_pl_b.pl_id
                JOIN statistics_before_2024_smartukm_rel_pl_k AS rel_kommune ON rel_kommune.pl_id=place.pl_id
                JOIN smartukm_kommune AS kommune ON kommune.id=rel_kommune.k_id
                WHERE kommune.id IN (#k_ids) AND 
                    innslag.b_kommune IN (#k_ids) AND 
                    (innslag.b_status = 8 OR innslag.b_status = 99) AND 
                    place.season='#season'",
                [
                    'k_ids' => $this->getAlleKommuneIds(),
                    'season' => $this->season
                ]
            );
        }


        $retArr = [];
        $innslagArr = [];
        $typeArr = [];
        $res = $sql->run();

        while($row = Query::fetch($res)) {
            try{
                $type = Typer::getById($row['bt_id'], $row['b_kategori']);
                $innslagArr[$type->getKey()][] = $row['b_id'];
                $typeArr[$type->getKey()][] = $type->getNavn();
            }catch(Exception $e) {
                // The type is not found
                if($e->getCode() == 110002) {
                    $innslagArr['ukjent'][] = $row['b_id'];
                    $typeArr['ukjent'][] = 'Ukjent';
                }
            }
        }

        foreach($innslagArr as $key => $value) {
            $retArr[$key]['antall'] = count($value);
            $retArr[$key]['type_navn'] = $typeArr[$key][0];
        }

        return $retArr;
    }

    /**
     * Gjennomsnitt deltakere per kommune
     * 
     * OBS: Det tas IKKE MED i beregningen kommuner som har 0 deltakere
     * 
     * @return int gjennomsnitt deltakere per kommune
     */
    public static function gjennomsnittDeltakereIKommuner(int $season) : int {
        $sql = new Query(" WITH total_persons_per_kommune AS (
            SELECT 
                k_id,
                SUM(total_persons) AS total_persons
            FROM (
                SELECT 
                    kommune.id AS k_id,
                    COUNT(DISTINCT innslag_person.p_id) AS total_persons
                FROM 
                    statistics_before_2024_smartukm_rel_pl_k AS arr_kommune
                JOIN 
                    statistics_before_2024_smartukm_place AS arrangement 
                    ON arrangement.pl_id = arr_kommune.pl_id
                JOIN 
                    statistics_before_2024_smartukm_rel_pl_b AS arr_innslag 
                    ON arr_innslag.pl_id = arrangement.pl_id
                JOIN 
                    statistics_before_2024_smartukm_rel_b_p AS innslag_person 
                    ON innslag_person.b_id = arr_innslag.b_id
                JOIN 
                    statistics_before_2024_smartukm_band AS innslag 
                    ON innslag.b_id = arr_innslag.b_id
                JOIN 
                    smartukm_kommune AS kommune 
                    ON kommune.id = arr_kommune.k_id
                WHERE 
                    arrangement.season = '#season' 
                    AND (innslag.b_status = 8 OR innslag.b_status = 99)
                GROUP BY 
                    kommune.id
        
                UNION ALL
        
                SELECT 
                    k_id AS k_id,
                    COUNT(DISTINCT p_id) AS total_persons
                FROM 
                    ukm_statistics_from_2024
                WHERE 
                    season = '#season'
                GROUP BY 
                    k_id
            ) AS data
            GROUP BY 
                k_id
            )
        
            SELECT 
                SUM(total_persons) / COUNT(DISTINCT k_id) AS average_total_persons_per_kommune
            FROM 
                total_persons_per_kommune;",
                [
                    'season' => $season
                ]
        );
        
        $res = $sql->run('array');
        return (int) intval($res['average_total_persons_per_kommune']);
    }

    /**
     * Returnerer alle id'er som kommunen har hatt tidligere inkludering nåværende
     * 
     * OBS: Noen kommuner har blitt splittet eller slått sammen gjennom år og derfor har de flere id'er
     * 
    * @return string liste av kommune_id separert med komma
     */
    private function getAlleKommuneIds() : string {
        $kommuneIds = array_map(function($kommune) {
            return $kommune->getId();
        }, $this->kommune->getTidligereKommuner());

        $alleKommunerIds = implode(',', $kommuneIds);

        return $alleKommunerIds;
    }
}