<?php

namespace UKMNorge\Statistikk\Objekter;

use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Geografi\Kommune;

class StatistikkSuper {
    public function __construct() {

    }

    protected function getQueryArrangement(Arrangement $arrangement) : String {
        $retQuery = '';
        if($arrangement->getSesong() > 2019) {
            $retQuery = "SELECT person_id as p_id, innslag_id as b_id
                FROM `statistics_before_2024_ukm_rel_arrangement_person`
                JOIN `statistics_before_2024_smartukm_band` AS `innslag`
                ON `innslag`.`b_id` = `statistics_before_2024_ukm_rel_arrangement_person`.`innslag_id`
                WHERE `arrangement_id` = '#plId'
                AND `b_status` = 8
                GROUP BY p_id, b_id"; // Fordi en person kan ikke delta 2 ganger i samme innslag (b_id)
        }
        else {
            $retQuery = "SELECT b_p.p_id, b.b_id
                FROM statistics_before_2024_smartukm_rel_pl_b AS pl_b
                JOIN statistics_before_2024_smartukm_rel_b_p AS b_p ON b_p.b_id = pl_b.b_id
                JOIN statistics_before_2024_smartukm_band AS b ON b.b_id = b_p.b_id
                JOIN statistics_before_2024_smartukm_place arrangement ON arrangement.pl_id = pl_b.pl_id
                JOIN statistics_before_2024_smartukm_participant AS p ON p.p_id = b_p.p_id
                WHERE pl_b.pl_id='#plId' 
                AND (b.b_status = 8 OR b.b_status = 99)
                GROUP BY b_p.b_id, b_p.p_id";
        }

        // If arrangementet er fra 2024
        if($arrangement->getSesong() > 2023) {
            $retQuery .= " UNION SELECT p_id, b_id
            FROM ukm_statistics_from_2024
            WHERE pl_id='#plId'
            GROUP BY p_id, b_id";
        }

        return $retQuery;
    }
    
    protected function getQueryKommune(int $season) : String {
        $retQuery = '';
        if($season > 2019) {
            $retQuery = "SELECT 
                arrang_person.person_id as p_id, 
                innslag.b_id as b_id, 
                arrang_person.arrangement_id
            FROM 
                statistics_before_2024_ukm_rel_arrangement_person AS arrang_person
            JOIN 
                statistics_before_2024_smartukm_band AS innslag 
                ON innslag.b_id = arrang_person.innslag_id
            JOIN 
                statistics_before_2024_smartukm_rel_pl_k AS arrang_kommune 
                ON arrang_kommune.pl_id = arrang_person.arrangement_id
            JOIN 
                smartukm_kommune AS kommune 
                ON kommune.id = arrang_kommune.k_id
            JOIN 
                statistics_before_2024_smartukm_place AS arrangement 
                ON arrangement.pl_id = arrang_person.arrangement_id
            WHERE 
                kommune.id = '#k_id' AND 
                arrangement.season='#season' AND
                innslag.b_status = 8
            GROUP BY 
                p_id, b_id";
        }
        // <= 2019
        else {
            $retQuery = "SELECT p_id, arr_innslag.b_id as b_id
            FROM statistics_before_2024_smartukm_rel_pl_k AS arr_kommune
            JOIN statistics_before_2024_smartukm_place AS arrangement ON arrangement.pl_id=arr_kommune.pl_id
            JOIN statistics_before_2024_smartukm_rel_pl_b AS arr_innslag ON arr_innslag.pl_id=arrangement.pl_id
            JOIN statistics_before_2024_smartukm_rel_b_p AS innslag_person ON innslag_person.b_id = arr_innslag.b_id
            JOIN statistics_before_2024_smartukm_band AS innslag ON innslag.b_id=arr_innslag.b_id
            WHERE arr_kommune.`k_id`='#k_id' AND arrangement.season='#season' AND (innslag.b_status = 8 OR innslag.b_status = 99)
            GROUP BY arr_innslag.b_id, p_id";
        }

        // If season er fra 2024
        if($season > 2023) {
            $retQuery .= " UNION SELECT p_id, b_id
            FROM ukm_statistics_from_2024
            WHERE k_id='#k_id' AND season='#season
            GROUP BY p_id, b_id";
        }

        return $retQuery;
    }


    // FYLKE
    // OBS: Det hentes innslag fra kommuner i fylke og ikke fylke arrangerte arrangementer
    protected function getQueryFylke(int $season) : String {
        $retQuery = '';
        // >2019
        if($season > 2019) {
            $retQuery = "SELECT 
                arrang_person.person_id as p_id, 
                innslag.b_id as b_id
            FROM 
                statistics_before_2024_ukm_rel_arrangement_person AS arrang_person
            JOIN 
                statistics_before_2024_smartukm_band AS innslag 
                ON innslag.b_id = arrang_person.innslag_id
            JOIN 
                statistics_before_2024_smartukm_rel_pl_k AS arrang_kommune 
                ON arrang_kommune.pl_id = arrang_person.arrangement_id
            JOIN 
                smartukm_kommune AS kommune 
                ON kommune.id = arrang_kommune.k_id
            JOIN 
                statistics_before_2024_smartukm_place AS arrangement 
                ON arrangement.pl_id = arrang_person.arrangement_id
            WHERE 
                kommune.idfylke = '#fylke_id' AND 
                arrangement.season='#season' AND
                innslag.b_status = 8
            GROUP BY 
                p_id, b_id";
        }
        // <= 2019
        else {
            $retQuery = "SELECT p_id, arr_innslag.b_id as b_id
            FROM statistics_before_2024_smartukm_rel_pl_k AS arr_kommune
            JOIN statistics_before_2024_smartukm_place AS arrangement ON arrangement.pl_id=arr_kommune.pl_id
            JOIN statistics_before_2024_smartukm_rel_pl_b AS arr_innslag ON arr_innslag.pl_id=arrangement.pl_id
            JOIN statistics_before_2024_smartukm_rel_b_p AS innslag_person ON innslag_person.b_id = arr_innslag.b_id
            JOIN statistics_before_2024_smartukm_band AS innslag ON innslag.b_id=arr_innslag.b_id
            JOIN 
                smartukm_kommune AS kommune 
                ON kommune.id = arr_kommune.k_id
            WHERE kommune.idfylke='#fylke_id' AND arrangement.season='#season' AND (innslag.b_status = 8 OR innslag.b_status = 99)
            GROUP BY arr_innslag.b_id, p_id";
        }

        // If season er fra 2024
        // OBS: Det hentes innslag fra kommuner i fylke og ikke fylke arrangerte arrangementer
        if($season > 2023) {
            $retQuery .= " UNION SELECT p_id, b_id
            FROM ukm_statistics_from_2024
            WHERE f_id='#fylke_id' 
            AND fylke='false'
            AND land='false'
            AND season='#season'";
        }

        return $retQuery;
    }

    protected function getKjonnByName(string $fornavn) : string {
        $first_name = explode(" ", str_replace("-", " ", $fornavn));
        $first_name = $first_name[0];

        $qry = "SELECT `kjonn`
				FROM `ukm_navn`
				WHERE `navn` = '" . $first_name . "' ";

        $qry = new Query($qry);
        $res = $qry->run('field', 'kjonn');

        return ($res == null) ? 'unknown' : $res;
    }

}