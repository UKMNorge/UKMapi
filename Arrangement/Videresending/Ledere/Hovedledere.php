<?php

namespace UKMNorge\Arrangement\Videresending\Ledere;

use UKMNorge\Collection;
use UKMNorge\Database\SQL\Query;

class Hovedledere extends Collection {
    var $arrangement_fra;
    var $arrangement_til;

    public function __construct( Int $arrangement_fra, Int $arrangement_til ) 
    {
        $this->arrangement_fra = $arrangement_fra;
        $this->arrangement_til = $arrangement_til;
    }

    /**
     * Last inn alle hovedledere
     *
     * @return void
     */
    public function _load() {
        $query = new Query(
            "SELECT * 
            FROM `". Hovedleder::TABLE ."`
            WHERE `arrangement_fra` = '#fra'
            AND `arrangement_til` = '#til'",
            [
                'fra' => $this->getArrangementFraId(),
                'til' => $this->getArrangementTilId()
            ]
        );
        $res = $query->run();

        while( $row = Query::fetch($res ) ) {
            $this->add(
                HovedLeder::getByData(
                    $this->getArrangementFraId(),
                    $this->getArrangementTilId(),
                    intval($row['l_id']),
                    $row['dato']
                )
            );
        }
    }

    public function get( $dato ) {
        $hovedleder = parent::get($dato);

        if(!$hovedleder ) {
            $hovedleder = Hovedleder::createEmpty(
                $this->getArrangementFraId(),
                $this->getArrangementTilId(),
                $dato
            );
        }
        return $hovedleder;
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