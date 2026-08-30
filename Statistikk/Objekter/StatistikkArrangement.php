<?php

namespace UKMNorge\Statistikk\Objekter;

use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Statistikk\Objekter\StatistikkSuper;
use UKMNorge\Innslag\Typer\Typer;

use Exception;
use DateTime;

class StatistikkArrangement extends StatistikkSuper {
    private int $arrangementId;
    private int $season;


    public function __construct(int $arrangementId, int $season) {
        // Check if the user has access to the arrangement
        $this->arrangementId = $arrangementId;
        $this->season = $season;
    }

    /**
    * Returnerer antall unike deltakere i arrangementet
    *
    * @return int antall unike deltakere.
    */
    public function getAntallUnikeDeltakere(bool $kunUfullforte = false) : int {
        return $this->runAntall(true, $kunUfullforte);
    }

    /**
    * Returnerer antall IKKE UNIKE deltakere i arrangementet
    *
    * @return int antall deltakere.
    */
    public function getAntallDeltakere(bool $kunUfullforte = false) : int {
        return $this->runAntall(false, $kunUfullforte);
    }

    private function runAntall($unique = false, bool $kunUfullforte = false) : int {

        $select = $unique ? "COUNT(DISTINCT p_id)" : "COUNT(p_id)";
        $sql = new Query(
            "SELECT " . $select . " as antall
            FROM (
                " . $this->getQueryArrangement($this->season, false, $kunUfullforte) . "
            ) AS subquery;",
            [
                'plId' => $this->arrangementId
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
        $arrangementDate = new DateTime($this->season.'-12-31');
        
        // GROUP BY p_id først: samme person kan dukke opp flere ganger i UNION
        // (før/etter 2024) med ulik p_dob, og DISTINCT p_id,p_dob telle dem dobbelt.
        $sql = new Query(
            "SELECT 
                age, 
                COUNT(*) AS participant_count 
            FROM (
                SELECT 
                    p_id,
                    TIMESTAMPDIFF(YEAR, 
                        FROM_UNIXTIME(MAX(p_dob)),
                        FROM_UNIXTIME(#arrangementDate))
                    AS age
                FROM (
                    " . $this->getQueryArrangement($this->season, true) . "
                ) AS subquery
                GROUP BY p_id
            ) AS age_subquery
            GROUP BY age
            ORDER BY age;
                ",
                [
                    'plId' => $this->arrangementId,
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
     * @return array[] An array of arrays with keys 'antall' and 'type_navn'.
    */
    public function getSjangerfordeling() : array {
        if($this->season > 2023) {
            $joinQuery = "JOIN ukm_statistics_from_2024 AS stat ON stat.b_id = subquery.b_id GROUP BY subquery.b_id";
        } else {
            $joinQuery = "JOIN statistics_before_2024_smartukm_band AS stat ON stat.b_id = subquery.b_id";
        }

        $sql = new Query("
            SELECT
                DISTINCT subqueryOut.b_id,
                subqueryOut.b_kategori,
                subqueryOut.bt_id
            FROM (
                SELECT
                    subquery.b_id AS b_id,
                    stat.b_kategori AS b_kategori,
                    stat.bt_id AS bt_id
                FROM (
                " . $this->getQueryArrangement($this->season) . "
                ) AS subquery
                " . $joinQuery . "
            ) AS subqueryOut
            ",
            [
                'plId' => $this->arrangementId,
            ]
        );

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
     * Returnerer antall deltakere i arrangementet fordelt på kjønn
     *
     * Det brukes navn for å identifisere kjønn
     * 
     * @return array[]
    */
    public function getKjonnsfordeling() {

        $sql = new Query(
            "SELECT p_id, firstname  
            FROM (
                SELECT participant.p_id, participant.p_firstname as firstname
                FROM (
                    " . $this->getQueryArrangement($this->season) . "
                    ) AS subquery
                JOIN statistics_before_2024_smartukm_participant AS participant ON participant.p_id=subquery.p_id
            ) AS subqueryOut GROUP BY p_id;
            ",
            [
                'plId' => $this->arrangementId,
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
     * Returnerer alle arrangementer, uansett sesong
     * 
     * 
     * @return int antall arrangementer.
    */
    public static function getAntallArrangementer() {
        $sql = new Query("
            SELECT COUNT(DISTINCT pl_id) AS antall
            FROM (
                SELECT DISTINCT pl_id
                FROM smartukm_place
                WHERE pl_deleted='false'

                UNION

                SELECT DISTINCT pl_id
                FROM statistics_before_2024_smartukm_place
                WHERE pl_deleted='false'
            ) AS combined_results
        ");

        $res = $sql->run('array');
        return (int) intval($res['antall']);
    }

    /**
     * Returnerer antall arrangementtyper. Typene kan være arrangement (workshop) eller møsntring (festival).
     * OBS: Det blir kun arrangementer lokalt (uten fylke og land) som blir telt.
     * 
     * @return [] monstring=>int, workshop=>int
    */
public static function getAntallArrangementTyperLokalt() {
        $sql = new Query("
            SELECT pl_subtype, COUNT(*) AS count
            FROM (
                SELECT DISTINCT pl_id, pl_subtype
                FROM smartukm_place
                WHERE pl_subtype IN ('monstring', 'arrangement')
                AND pl_deleted='false'
                AND pl_type!='fylke'
                
                UNION
                
                SELECT DISTINCT pl_id, pl_subtype
                FROM statistics_before_2024_smartukm_place
                WHERE pl_subtype IN ('monstring', 'arrangement')
                AND pl_deleted='false'
                AND pl_type!='fylke'
            ) AS combined
            GROUP BY pl_subtype;
        ");

        $res = $sql->run();
        $retArr = [];

        while($row = Query::fetch($res)) {
            $retArr[$row['pl_subtype']] = intval($row['count']);
        }

        return $retArr;
    }
}