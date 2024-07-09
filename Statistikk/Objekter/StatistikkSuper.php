<?php

namespace UKMNorge\Statistikk\Objekter;

use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Database\SQL\Query;


class StatistikkSuper {
    public function __construct() {

    }

    protected function getQueryArrangement(Arrangement $arrangement) : String {
        if($arrangement->getSesong() > 2019) {
            return "SELECT * 
                FROM `statistics_before_2024_ukm_rel_arrangement_person`
                JOIN `statistics_before_2024_smartukm_band` AS `innslag`
                ON `innslag`.`b_id` = `statistics_before_2024_ukm_rel_arrangement_person`.`innslag_id`
                WHERE `arrangement_id` = '#plId'
                AND `b_status` = 8";
        }
        else {
            return "SELECT pl_b.pl_id, b.b_id, b.b_name, b_p.p_id, p.p_firstname, arrangement.pl_type
                FROM statistics_before_2024_smartukm_rel_pl_b AS pl_b
                JOIN statistics_before_2024_smartukm_rel_b_p AS b_p ON b_p.b_id = pl_b.b_id
                JOIN statistics_before_2024_smartukm_band AS b ON b.b_id = b_p.b_id
                JOIN statistics_before_2024_smartukm_place arrangement ON arrangement.pl_id = pl_b.pl_id
                JOIN statistics_before_2024_smartukm_participant AS p ON p.p_id = b_p.p_id
                WHERE pl_b.pl_id=#plId 
                AND (b.b_status = 8 OR b.b_status = 99)
                GROUP BY b_p.b_id, b_p.p_id";
        }
    }

}