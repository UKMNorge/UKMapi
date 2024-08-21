<?php

namespace UKMNorge\Statistikk\Objekter;

use UKMNorge\Database\SQL\Query;
use UKMNorge\Statistikk\Objekter\StatistikkSuper;
use UKMNorge\Statistikk\StatistikkManager;
use UKMNorge\Geografi\Fylke;
use UKMNorge\Innslag\Typer\Typer;


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

    /**
     * Returnerer antall innslag i fylke fordelt på sjanger
     * 
     * OBS: Det hentes innslag kun fra arrangementer på kommunenivå i et fylke
     * 
     * @param int $excludePlId - arrangementet som skal ekskluderes fra statistikken. Vanligvis kan pl_id brukes av arrangementet hvor statistikk hentes fra
     * @return array[] An array of arrays with keys 'antall' and 'type_navn'.
    */
    public function getSjangerFordeling(int $excludePlId=-1) : array {
        if($excludePlId == null) {
            $excludePlId = -1;
        }

        // > 2019 innslag fra ukm_rel_arrangement_person og fra juli 2024 brukes tabellen ukm_statistics_from_2024
        if($this->season > 2019) {
            $sql = new Query("SELECT 
                DISTINCT innslag.b_id, 
                innslag.bt_id, 
                innslag.b_kategori, 
                arrpers.arrangement_id,
            FROM statistics_before_2024_ukm_rel_arrangement_person AS arrpers 
            JOIN statistics_before_2024_smartukm_band AS innslag ON innslag.b_id=arrpers.innslag_id 
            JOIN statistics_before_2024_smartukm_place AS place ON place.pl_id=arrpers.arrangement_id
            JOIN statistics_before_2024_smartukm_rel_pl_k AS rel_kommune ON rel_kommune.pl_id=place.pl_id
            JOIN smartukm_kommune AS kommune ON kommune.id=rel_kommune.k_id
            WHERE kommune.idfylke='#fylkeId' 
                AND place.season='#season' 
                AND innslag.b_status = 8 
                AND arrpers.arrangement_id!='#plId'

            UNION 

            SELECT DISTINCT innslag.b_id, innslag.bt_id, innslag.b_kategori, stat.pl_id 
            FROM ukm_statistics_from_2024 AS stat 
            JOIN statistics_before_2024_smartukm_band AS innslag ON innslag.b_id=stat.b_id
            JOIN statistics_before_2024_smartukm_place AS place ON place.pl_id=stat.pl_id 
            WHERE stat.f_id='#fylkeId' 
                AND stat.fylke='false'
                AND place.season='#season' 
                AND innslag.b_status = 8 
                AND stat.pl_id!='#plId'",
                [
                    'fylkeId' => $this->fylke->getId(),
                    'plId' => $excludePlId, // exclude arrangementet if needed
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
                WHERE kommune.idfylke='#fylkeId' 
                AND (innslag.b_status = 8 OR innslag.b_status = 99)
                AND place.season='#season'
                AND place.pl_id!='#plId'", // excluded arrangementet
                [
                    'fylkeId' => $this->fylke->getId(),
                    'plId' => $excludePlId, // exclude arrangementet if needed
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

}