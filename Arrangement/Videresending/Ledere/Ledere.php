<?php

namespace UKMNorge\Arrangement\Videresending\Ledere;

use UKMNorge\Collection;
use UKMNorge\Database\SQL\Query;

class Ledere extends Collection
{
    var $arrangement_fra;
    var $arrangement_til;

    /**
     * Opprett en samling ledere
     *
     * @param Int $arrangement_fra
     * @param Int $arrangement_til
     */
    public function __construct(Int $arrangement_fra, Int $arrangement_til)
    {
        $this->arrangement_fra = $arrangement_fra;
        $this->arrangement_til = $arrangement_til;
    }


    /**
     * Last inn ledere fra gitt sted
     *
     * @return void
     */
    public function _load()
    {
        $query = new Query(
            "SELECT *
            FROM `" . Leder::TABLE . "`
            WHERE `arrangement_fra` = '#fra'
            AND `arrangement_til` = '#til'",
            [
                'fra' => $this->getArrangementFraId(),
                'til' => $this->getArrangementTilId()
            ]
        );

        $res = $query->getResults();
        while( $row = Query::fetch($res) ) {
            $this->add(
                Leder::loadFromDatabaseRow( $row )
            );
        }
    }

    /**
     * Get the value of arrangement_fra
     */
    public function getArrangementFraId()
    {
        return $this->arrangement_fra;
    }

    /**
     * Get the value of arrangement_til
     */
    public function getArrangementTilId()
    {
        return $this->arrangement_til;
    }
}
