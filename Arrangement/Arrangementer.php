<?php

namespace UKMNorge\Arrangement;

use UKMNorge\Database\SQL\Query;
use Exception;

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

    public static function filterSkipEier( $eier, $arrangementer ) {
        $filtered = [];
        foreach( $arrangementer as $arrangement ) {
            #echo 'SAMMENLIGN: '. $arrangement->getEier()->getId() .' MED '. $eier->getId() ."\r\n";
            if( $arrangement->getEier()->getId() != $eier->getId() ) {
                $filtered[] = $arrangement;
            }
        }
        return $filtered;
    }

    public function _load()
    {
        $this->arrangementer = [];

        switch ($this->getOmradeType()) {

            case 'kommune2':
                throw new Exception('load(' . $this->getOmradeType() . ') mangler implementering i Arrangementer');
                break;
            /*
             * Lokalmønstringer som er eid av en kommune i fylket, 
             * eller som deltar i en fellesmønstring i fylket
             */
            case 'fylke':
                $sql = new Query(
                    Arrangement::getLoadQry()
                        . "WHERE
                        `season` = '#season'
                        AND (
                            (#fylke) IN (
                                SELECT `smartukm_kommune`.`idfylke`
                                    FROM `smartukm_rel_pl_k` 
                                    JOIN `smartukm_kommune`
                                        ON (`smartukm_kommune`.`id` = `smartukm_rel_pl_k`.`k_id`)
                                    WHERE `smartukm_rel_pl_k`.`pl_id` = `place`.`pl_id`
                            )
                            OR
                            (`pl_type` != 'fylke' AND `pl_owner_fylke` = '#fylke')
                        )",
                    [
                        'fylke' => $this->getOmradeId(),
                        'season' => $this->getSesong()
                    ]
                );
                break;
            /**
             * HENT KOMMUNE & FYLKE FRA GITT OMRÅDE
             */
            case 'kommune':
            case 'eier-kommune':
                $sql = new Query(
                    Arrangement::getLoadQry()
                        . "WHERE `pl_type` = 'kommune' 
                        AND `pl_owner_kommune` = '#omrade_id'
                        ",
                    [
                        'omrade_id' => $this->getOmradeId()
                    ]
                );
                break;
            case 'eier-fylke':
                $sql = new Query(
                    Arrangement::getLoadQry()
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
                throw new Exception('Ukjent type område ' . $this->getOmradeType());
        }
        #echo $sql->debug();
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
        return $this->getSesong();
    }

    public function getSesong() {
        return $this->season;
    }
}
