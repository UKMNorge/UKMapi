<?php

namespace UKMNorge\Arrangement;

use UKMNorge\Database\SQL\Query;
use Exception;

require_once('UKM/Autoloader.php');

class Arrangementer
{
    private $omrade_type = null;
    private $omrade_id = null;
    private $season = null;
    private $arrangementer = [];
    private $filter = null;

    public function __construct(Int $season, String $omrade_type, Int $omrade_id, $filter = false)
    {
        $this->season = $season;
        $this->omrade_type = $omrade_type;
        $this->omrade_id = $omrade_id;
        if ($filter) {
            if (get_class($filter) != 'UKMNorge\Arrangement\Filter') {
                throw new Exception(
                    'Arrangement-filter må være av klassen Filter! (' . get_class($filter) . ')',
                    150003
                );
            }
            $this->filter = $filter;
        } else {
            $this->filter = new Filter();
        }
    }

    /**
     * Hent første element
     *
     * @return Arrangement
     */
    public function getFirst()
    {
        $alle = $this->getAll();
        return array_pop($alle);
    }

    /**
     * Finn kun de som er eid av gitt eier
     *
     * @param Eier $eier
     * @param Array<Arrangement> $arrangementer
     * @return void
     */
    public static function filterSkipEier(Eier $eier, array $arrangementer)
    {
        $filtered = [];
        foreach ($arrangementer as $arrangement) {
            #echo 'SAMMENLIGN: '. $arrangement->getEier()->getId() .' MED '. $eier->getId() ."\r\n";
            if ($arrangement->getEier()->getId() != $eier->getId()) {
                $filtered[] = $arrangement;
            }
        }
        return $filtered;
    }

    /**
     * Last inn alle arrangement
     *
     * @return void
     */
    public function _load()
    {
        $this->arrangementer = [];

        switch ($this->getOmradeType()) {

            case 'kommune2':
                throw new Exception(
                    'load(' . $this->getOmradeType() . ') mangler implementering i Arrangementer',
                    150002
                );
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
                        AND `pl_deleted` = 'false'
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
                #echo $sql->debug();
                break;
                /**
                 * HENT KOMMUNE & FYLKE FRA GITT OMRÅDE
                 */
            case 'kommune':
                $sql = $this->_getKommuneQuery();
                break;
            case 'eier-kommune':
                $sql = new Query(
                    Arrangement::getLoadQry()
                        . "WHERE `pl_type` = 'kommune' 
                        AND `pl_owner_kommune` = '#omrade_id'
                        AND `season` = '#season'
                        AND `pl_deleted` = 'false'
                        ",
                    [
                        'omrade_id' => $this->getOmradeId(),
                        'season' => $this->getSesong()
                    ]
                );
                break;
            case 'eier-fylke':
                $sql = new Query(
                    Arrangement::getLoadQry()
                        . "WHERE `pl_type` = 'fylke'
                        AND `pl_owner_fylke` = '#omrade_id'
                        AND `season` = '#season'
                        AND `pl_deleted` = 'false'
                        ",
                    [
                        'omrade_id' => $this->getOmradeId(),
                        'season' => $this->getSesong()
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
                        AND `pl_owner_kommune` = '#omrade_id'
                        AND `season` = '#season'
                        AND `pl_deleted` = 'false'",
                    [
                        'omrade_id' => $postnummer->run('field'),
                        'season' => $this->getSesong()
                    ]
                );
                break;
            case 'alle':
                $sql = new Query(
                    Arrangement::getLoadQry() . "
                    WHERE `season` = '#season'
                    AND `pl_deleted` = 'false'",
                    [
                        'season' => $this->getSesong()
                    ]
                );
                break;
            case 'land':
            default:
                throw new Exception(
                    'Ukjent type område ' . $this->getOmradeType(),
                    150001
                );
        }
        #echo $sql->debug();
        $res = $sql->run();
        while ($row = Query::fetch($res)) {
            $arrangement = new Arrangement($row);
            if ($this->filter->passesFilter($arrangement)) {
                $this->arrangementer[$row['pl_id']] = $arrangement;
            }
        }

        // Når vi snakker eier-kommune, bør også
        // arrangementer man er deleier i være med (eller?)
        if ($this->getOmradeType() == 'eier-kommune') {
            $res2 = $this->_getKommuneQuery()->run();
            while ($row = Query::fetch($res2)) {
                $arrangement = new Arrangement($row);
                if ($this->filter->passesFilter($arrangement)) {
                    $this->arrangementer[$row['pl_id']] = $arrangement;
                }
            }
        }
    }

    /**
     * Lag sql-spørring for kommune-arrangement
     *
     * @return String SQL
     */
    private function _getKommuneQuery()
    {
        return new Query(
            Arrangement::getLoadQry()
                . "
                LEFT JOIN `smartukm_rel_pl_k` AS `pl_k`
                    ON(`pl_k`.`pl_id` = `place`.`pl_id`)
                WHERE `pl_type` = 'kommune' 
                AND `place`.`season` = '#season'
                AND `pl_deleted` = 'false'
                AND
                    (
                        `place`.`pl_owner_kommune` = '#omrade_id'
                        OR
                        `pl_k`.`k_id` = '#omrade_id'
                    )
                ",
            [
                'omrade_id' => (int) $this->getOmradeId(),
                'season' => $this->getSesong()
            ]
        );
    }

    /**
     * Hent alle arrangement
     */
    public function getAll()
    {
        if (sizeof($this->arrangementer) == 0) {
            $this->_load();
        }
        return $this->arrangementer;
    }

    /**
     * Hent alle synlige arrangement
     */
    public function getAllSynlige()
    {
        if (sizeof($this->arrangementer) == 0) {
            $this->_load();
        }

        return array_filter($this->arrangementer, function ($arr) {
            if ($arr->erSynlig()) {
                return true;
            }
            return false;
        });
    }

    /**
     * Finnes det noen arrangement i denne samlingen?
     *
     * @return Bool
     */
    public function har()
    {
        return sizeof($this->getAll()) > 0;
    }

    /**
     * Hent antall arrangement i denne samlingen
     *
     * @return Int
     */
    public function getAntall()
    {
        return sizeof($this->getAll());
    }

    /**
     * Hent områdets type
     */
    public function getOmradeType()
    {
        return $this->omrade_type;
    }

    /**
     * Hent områdets ID
     */
    public function getOmradeId()
    {
        return $this->omrade_id;
    }

    /**
     * Hent aktiv sesong for denne samlingen
     *
     * @return Int
     */
    public function getSeason()
    {
        return $this->getSesong();
    }

    /**
     * Hent aktiv sesong for denne samlingen
     *
     * @return Int
     */
    public function getSesong()
    {
        return $this->season;
    }
}
