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
            /**
             * HENT KOMMUNE & FYLKE FRA GITT OMRÅDE
             */
            case 'kommune':
                $sql = new Query(
                    Arrangement::getLoadQry()
                        . "WHERE `pl_type` = 'kommune' 
                        AND `pl_owner_kommune` = '#omrade_id'",
                    [
                        'omrade_id' => $this->getOmradeId()
                    ]
                );
                break;
            case 'fylke':
                $sql = new Query(
                    Arrangement::getLoadQryFylke()
                        . "WHERE `pl_type` = 'fylke'
                        AND `pl_owner_fylke` = '#omrade_id'",
                    [
                        'omrade_id' => $this->getOmradeId()
                    ]
                );
                break;
            /**
             * HENT ALLE ARRANGEMENT I EN KOMMUNE, UT
             * FRA ET POSTNUMMER
             */
            case 'postnummer':
                $postnummer = new Query(
                    "SELECT `k_id`
                    FROM `smartukm_postalplace`
                    WHERE `postalcode` = '#postnummer'",
                    [
                        'postnummer' => $this->getOmradeId()
                    ]
                );

                $sql = new Query(
                    Arrangement::getLoadQry()
                        . "WHERE `pl_type` = 'kommune' 
                        AND `pl_owner_kommune` = '#omrade_id'",
                    [
                        'omrade_id' => $postnummer->run('field')
                    ]
                );
                break;
            case 'land':
                break;
            default:
                throw new Exception('Ukjent type område '. $this->getOmradeType());
        }

        

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
