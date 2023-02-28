<?php

namespace UKMNorge\Arrangement\Videresending\Request;

use UKMNorge\Collection;
use UKMNorge\Database\SQL\Query;

class RequestVideresendinger extends Collection {
    var $query;

    /**
     * Opprett RequestVideresendinger-objekt
     * Aksepterer query
     * 
     * @param Int $arrangement_fra
     * @return void
     */
    public function __construct(Query $query) {
        $this->query = $query;
    }

    /**
     * Last inn alle hovedledere
     *
     * @return void
     */
    public function _load() {
        $res = $this->query->run();

        while( $row = Query::fetch($res ) ) {
            $this->add(
                new RequestVideresending(
                    intval($row['id']),
                    intval($row['arrangement_fra']),
                    intval($row['arrangement_til']),
                    $row['dato'] ? $row['dato'] : '',
                    $row['completed'] && $row['completed'] == 1 ? true : false
                )
            );
        }
    }

    /**
     * Hent alle request fra arrangement
     * 
     * @param Int $arrangement_fra
     * @return RequestVideresendinger
     */
    public static function getAllFraArrangement(Int $arrangement_fra) {
        $query = new Query(
            "SELECT * 
            FROM `". RequestVideresending::TABLE ."`
            WHERE `arrangement_fra` = '#fra'",
            [
                'fra' => $arrangement_fra,
            ]
        );

        return new RequestVideresendinger($query);
    }

    /**
     * Hent alle request til arrangement
     * 
     * @param Int $arrangement_til
     * @return RequestVideresendinger
     */
    public static function getAllTilArrangement(Int $arrangement_til) {
        $query = new Query(
            "SELECT * 
            FROM `". RequestVideresending::TABLE ."`
            WHERE `arrangement_til` = '#til'",
            [
                'til' => $arrangement_til,
            ]
        );

        return new RequestVideresendinger($query);
    }
}