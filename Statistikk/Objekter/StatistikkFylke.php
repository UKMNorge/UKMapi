<?php

namespace UKMNorge\Statistikk\Objekter;

use UKMNorge\Database\SQL\Query;
use UKMNorge\Statistikk\Objekter\StatistikkSuper;
use UKMNorge\Statistikk\StatistikkManager;
use UKMNorge\Geografi\Fylke;

use Exception;
use DateTime;


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

    /**
    * Returnerer gjennomsnitt av UNIKE deltakere i arrangemeter 
    * Dette går gjennom alle kommuner og beregner gjennomsnittet
    *
    * OBS: Det tas ikke i beregning fylkesarrangementer
    *
    * @return int antall unike deltakere.
    */
    public function getGjennomsnittDeltakere($season) : int {
        $sql = new Query("
            WITH ParticipantCount AS (
                SELECT 
                    arrangement.pl_id,
                    COUNT(DISTINCT innslag_person.p_id) AS participant_count
                FROM
                    statistics_before_2024_smartukm_rel_pl_k AS arr_kommune
                    JOIN statistics_before_2024_smartukm_place AS arrangement ON arrangement.pl_id = arr_kommune.pl_id
                    JOIN statistics_before_2024_smartukm_rel_pl_b AS arr_innslag ON arr_innslag.pl_id = arrangement.pl_id
                    JOIN statistics_before_2024_smartukm_rel_b_p AS innslag_person ON innslag_person.b_id = arr_innslag.b_id
                    JOIN statistics_before_2024_smartukm_band AS innslag ON innslag.b_id = arr_innslag.b_id
                    JOIN smartukm_kommune AS kommune ON kommune.id = arr_kommune.k_id
                WHERE 
                    kommune.idfylke = '#fylke_id' 
                    AND arrangement.season = '#season' 
                    AND (innslag.b_status = 8 OR innslag.b_status = 99)
                GROUP BY 
                    arrangement.pl_id
            )
            SELECT 
                AVG(participant_count) AS average_p
            FROM 
                ParticipantCount
        ", [
            'fylke_id' => $this->fylke->getId(),
            'season' => $season
        ]);

        $res = $sql->run('array');
        return (int) intval($res['average_p']);
    }


    /**
     * Returnerer antall UNIKE deltakere fordelt på alder i fylke
     * Det velges alle participant fra alle arrangement i fylke i en sesong.
     * 
     * OBS: det brukes sesong år og 31. desember som dato når deltakere deltok i arrangementet.
     * 
    * @return array[] An array of arrays with keys 'age' and 'antall'.
    */
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
                " . $this->getQueryFylke($this->season) . "
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
                    'fylke_id' => $this->fylke->getId(),
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

}