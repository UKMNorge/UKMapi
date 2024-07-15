<?php

namespace UKMNorge\Statistikk\Objekter;

use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Statistikk\Objekter\StatistikkSuper;
use UKMNorge\Statistikk\StatistikkManager;
use UKMNorge\Innslag\Typer\Typer;

use Exception;
use DateTime;

class StatistikkArrangement extends StatistikkSuper {
    private Arrangement $arrangement;
    private StatistikkManager $sm;


    public function __construct(Arrangement $arrangement) {
        $this->sm = new StatistikkManager();
        // Check if the user has access to the arrangement
        if($this->sm::hasAccessToArrangement($arrangement) == false) {
            // throw new Exception('Ingen tilgang til arrangement ' . $arrangement->getId(), 401);
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

    /**
     * Returnerer antall deltakere i arrangementet fordelt på alder
     * 
     * OBS: det brukes sesong år og 31. desember som dato når deltakere deltok i arrangementet.
     * 
    * @return array[] An array of arrays with keys 'age' and 'antall'.
    */
    public function getAldersfordeling() : array {
        $arrangementDate = new DateTime($this->arrangement->getSesong().'-12-31');
        
        $sql = new Query(
            "SELECT 
                age, 
                COUNT(*) AS participant_count 
            FROM (SELECT 
                DISTINCT participant.p_id, 
                participant.p_dob,
                TIMESTAMPDIFF(YEAR, 
                    FROM_UNIXTIME(participant.p_dob),
                    FROM_UNIXTIME(#arrangementDate))
                AS age
            FROM (
                " . $this->getQueryArrangement($this->arrangement) . "
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
                    'plId' => $this->arrangement->getId(),
                    'arrangementDate' => $arrangementDate->getTimestamp()
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
     * Returnerer antall innslag i arrangementet fordelt på sjanger
     * 
     * OBS: det brukes sesong år og 31. desember som dato når deltakere deltok i arrangementet.
     *
     * @return array[]
    */
    public function getSjangerfordeling() : array {

        // > 2019 innslag fra ukm_rel_arrangement_person og fra juli 2024 brukes tabellen ukm_statistics_from_2024
        if($this->arrangement->getSesong() > 2019) {
            $sql = new Query("SELECT 
                        DISTINCT innslag.b_id,
                        innslag.bt_id,
                        innslag.b_kategori,
                        arrpers.arrangement_id
                    FROM statistics_before_2024_ukm_rel_arrangement_person AS arrpers
                    JOIN statistics_before_2024_smartukm_band AS innslag ON innslag.b_id=arrpers.innslag_id
                    WHERE arrpers.arrangement_id='#plId' AND innslag.b_status = 8
                    
                    UNION
            
                    SELECT 
                        DISTINCT innslag.b_id,
                        innslag.bt_id,
                        innslag.b_kategori,
                        stat.pl_id
                    FROM 
                        ukm_statistics_from_2024 AS stat
                    JOIN 
                        smartukm_band AS innslag ON innslag.b_id=stat.b_id
                    WHERE stat.pl_id='#plId' AND innslag.b_status = 8",
                [
                    'plId' => $this->arrangement->getId(),
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
                    WHERE rel_pl_b.pl_id='#plId' AND (innslag.b_status = 8 OR innslag.b_status = 99)
                ",
                [
                    'plId' => $this->arrangement->getId(),
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
}