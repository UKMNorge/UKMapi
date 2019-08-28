<?php

namespace UKMNorge\Arrangement;

use UKMNorge\Database\SQL\Query;

class Arrangementer
{
    private $omrade_type = null;
    private $omrade_id = null;
    private $season = null;
    private $arrangementer = [];

    public function __construct(Int $season, String $omrade_type, Int $omrade_id)
    {
        $this->season = $season;
        $this->omrade_type = $omrade_type;
        $this->omrade_id = $omrade_id;
    }

    public function _load()
    {
        $this->arrangementer = [];
        switch ($this->getOmradeType()) {
            case 'kommune':
                $query = "AND `pl_owner_kommune` = '#omrade_id'";
                break;
            case 'fylke':
                $query = "AND `pl_owner_fylke` = '#omrade_id'";
                break;
            case 'land':

                break;
        }
        $sql = new Query(
            Arrangement::getLoadQryFylke()
                . "WHERE `pl_type` = '#type' " .
                $query,
            [
                'type' => $this->getOmradeType(),
                'omrade_id' => $this->getOmradeId()
            ]
        );

        $res = $sql->run();
        while ($row = Query::fetch($res)) {
            $this->arrangementer[$row['pl_id']] = new Arrangement($row);
        }
    }

    /**
     * Get the value of arrangementer
     */
    public function getAll()
    {
        if (sizeof($this->arrangementer) == 0) {
            $this->_load();
        }
        return $this->arrangementer;
    }

    /**
     * Get the value of omrade_type
     */
    public function getOmradeType()
    {
        return $this->omrade_type;
    }

    /**
     * Get the value of omrade_id
     */
    public function getOmradeId()
    {
        return $this->omrade_id;
    }

    /**
     * Get the value of season
     */
    public function getSeason()
    {
        return $this->season;
    }
}
