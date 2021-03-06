<?php

namespace UKMNorge\Arrangement;

use DateTime;
use UKMNorge\Database\SQL\Query;
use Exception;

require_once('UKM/Autoloader.php');

class Arrangementer
{
    private $omrade_type = null;
    private $omrade_id = null;
    private $arrangementer = [];
    private $filter = null;
    private static $now = null;

    public function __construct(String $omrade_type, Int $omrade_id, $filter = false)
    {
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
     * @return Array<Arrangement>
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
                        . "WHERE `pl_deleted` = 'false'
                        " . $this->getSesongSQL() . "
                        " . $this->getTidligereKommendeFilter() . "
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
                        )
                        ". $this->getSortString(),
                    [
                        'fylke' => $this->getOmradeId(),
                        'season' => $this->getSesong(),
                        'idag' => static::getIDag()
                    ]
                );
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
                        " . $this->getSesongSQL() . "
                        " . $this->getTidligereKommendeFilter() . "
                        AND `pl_deleted` = 'false'
                        ". $this->getSortString(),
                    [
                        'omrade_id' => $this->getOmradeId(),
                        'season' => $this->getSesong(),
                        'idag' => static::getIDag()
                    ]
                );
                break;
            case 'eier-fylke':
                $sql = new Query(
                    Arrangement::getLoadQry()
                        . "WHERE `pl_type` = 'fylke'
                        AND `pl_owner_fylke` = '#omrade_id'
                        " . $this->getSesongSQL() . "
                        " . $this->getTidligereKommendeFilter() . "
                        AND `pl_deleted` = 'false'
                        ". $this->getSortString(),
                    [
                        'omrade_id' => $this->getOmradeId(),
                        'season' => $this->getSesong(),
                        'idag' => static::getIDag()
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
                        " . $this->getSesongSQL() . "
                        " . $this->getTidligereKommendeFilter() . "
                        AND `pl_deleted` = 'false'
                        ". $this->getSortString(),
                    [
                        'omrade_id' => $postnummer->run('field'),
                        'season' => $this->getSesong(),
                        'idag' => static::getIDag()
                    ]
                );
                break;
            case 'alle':
                $sql = new Query(
                    Arrangement::getLoadQry() . "
                        WHERE `pl_deleted` = 'false'
                        " . $this->getSesongSQL() . "
                        " . $this->getTidligereKommendeFilter() ."
                        " . $this->getSortString(),
                    [
                        'season' => $this->getSesong(),
                        'idag' => static::getIDag()
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
     * @return Query
     */
    private function _getKommuneQuery()
    {
        return new Query(
            Arrangement::getLoadQry()
                . "
                    LEFT JOIN `smartukm_rel_pl_k` AS `pl_k`
                    ON(`pl_k`.`pl_id` = `place`.`pl_id`)
                    WHERE `pl_type` = 'kommune' 
                    " . $this->getSesongSQL() . "
                    " . $this->getTidligereKommendeFilter() . "
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
                'season' => $this->getSesong(),
                'idag' => static::getIDag()
            ]
        );
    }

    /**
     * Hent alle arrangement
     * 
     * @return Array<Arrangement>
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
     * 
     * @return Array<Arrangement>
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
     * 
     * @return String
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
        return !is_array($this->filter->getSesong()) ? $this->filter->getSesong() : '';
    }

    /**
     * Hent SQL for å filtrere på sesong fra databasen
     * 
     * For å spare ressurser, vil et filter med satt sesong 
     * legge til dette i SQL-spørringen, slik at vi ikke oppretter
     * mange unødvendige Arrangement-objekter som deretter lukes ut i 
     * filtreringen gjort av getAll().
     *
     * @return String
     */
    private function getSesongSQL()
    {
        if (in_array('sesong', array_keys($this->filter->getFilters()))) {
            // Hvis flere sesonger
            if (is_array($this->filter->getFilters()['sesong'])) {
                return " AND `place`.`season` IN (" . implode(",", $this->filter->getSesong()) . ") ";
            }
            // Hvis en sesong
            return " AND `place`.`season` = '#season' ";
        }
        return '';
    }

    /**
     * Hent SQL for å filtrere på tidligere / kommende
     * 
     * Dette filteret legges automatisk på når man benytter
     * UKMNorge\Arrangement\Tidligere eller 
     * UKMNorge\Arrangement\Kommende
     *
     * @return String
     */
    private function getTidligereKommendeFilter() {
        if($this->erKommende()) {
            return " AND `pl_stop` >= '#idag' ";
        }
        if($this->erTidligere()) {
            return " AND `pl_stop` < '#idag' ";
        }
        return '';
    }

    /**
     * Hent SQL for å sortere listen
     * 
     * @return String
     */
    private function getSortString() {
        return 'ORDER BY `pl_start` ASC, 
        `pl_name` ASC';
    }

    /**
     * Sjekk hvorvidt vi forsøker å filtrere ut tidligere arrangement
     * 
     * @return Bool
     */
    private function erTidligere() {
        return in_array('tidligere', array_keys($this->filter->getFilters()));
    }

    /**
     * Sjekk hvorvidt vi forsøker å filtrere ut kommende arrangement
     * 
     * @return Bool
     */
    private function erKommende() {
        return in_array('kommende', array_keys($this->filter->getFilters()));
    }

    /**
     * Hent timestamp for dagen i dag
     *
     * @return void
     */
    private static function getIDag() {
        if( is_null( static::$now ) ) {
            static::$now = new DateTime('now');
        }
        return static::$now->format('Y-m-d H:i:s');
    }
}