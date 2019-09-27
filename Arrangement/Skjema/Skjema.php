<?php

namespace UKMNorge\Arrangement\Skjema;

use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Arrangement\Eier;
use UKMNorge\Database\SQL\Query;
use Exception;

require_once('UKM/Arrangement/Skjema/SvarSett.php');
require_once('UKM/Arrangement/Skjema/Skjema.php');
require_once('UKM/Arrangement/Skjema/Sporsmal.php');
require_once('UKM/Arrangement/Arrangement.php');
require_once('UKM/Arrangement/Eier.php');
require_once('UKM/Database/SQL/select.class.php');

class Skjema {

    private $id;
    private $sporsmal;
    private $svar;
    private $arrangement;
    private $arrangement_id;
    private $eier;

    public function __construct( Int $id, Int $pl_id, String $eier_type, Int $eier_id ) {
        $this->id = $id;
        $this->arrangement_id = $pl_id;
        $this->eier = new Eier( $eier_type, $eier_id );
    }

    /**
     * Hent skjema for arrangement
     *
     * @param Int $pl_id
     * @return Skjema $skjema
     */
    public static function loadFromArrangement( Int $pl_id ) {
        $query = new Query(
            "SELECT *
            FROM `ukm_videresending_skjema`
            WHERE `pl_id` = '#arrangement'",
            [
                'arrangement' => $pl_id
            ]
        );
        $db_row = $query->run('array');

        if( !$db_row ) {
            throw new Exception(
                'Arrangementet har ikke skjema',
                151001
            );
        }
        return new Skjema(
            $db_row['id'],
            $db_row['pl_id'],
            $db_row['eier_type'],
            $db_row['eier_id']
        );
    }

    /**
     * Hent arrangement-objektet
     * Tror det er dårlig praksis å bruke denne altså, da skjema skal lastes
     * fra Arrangement-klassen.
     * 
     * @return Arrangement $arrangement
     */ 
    public function getArrangement()
    {
        if( null == $this->arrangement ) {
            $this->arrangement = new Arrangement( $this->getArrangementId() );
        }
        return $this->arrangement;
    }

    /**
     * Hent arrangement-ID (pl_id)
     *
     * @return Int $pl_id
     */
    public function getArrangementId() {
        return $this->arrangement_id;
    }

    /**
     * Get the value of sporsmal
     */ 
    public function getSporsmal()
    {
        if( null == $this->sporsmal ) {
            $this->sporsmal = [];

            $select = new Query(
                "SELECT * 
                FROM `ukm_videresending_skjema_sporsmal`
                WHERE `skjema` = '#skjema'",
                [
                    'skjema' => $this->getId()
                ]
            );
            $res = $select->run();
            while( $db_row = Query::fetch( $res ) ) {
                $sporsmal = Sporsmal::createFromDatabase( $db_row );
                $this->sporsmal[ $sporsmal->getRekkefolge().'_'.$sporsmal->getId() ] = $sporsmal;
            }
            ksort( $this->sporsmal );
        }
        return $this->sporsmal;
    }

    public function addSporsmal( Sporsmal $sporsmal ) {
        $this->getSporsmal();
        $this->sporsmal[ $sporsmal->getId() ] = $sporsmal;
        return $this;
    }

    /**
     * Hent svarsett for alle som har svart på dette skjemaet
     *
     * @return Array $SvarSett 
     */
    public function getSvarSett() {
        if( null == $this->svar ) {
            $this->svar = [];

            $select = new Query(
                "SELECT * 
                FROM `ukm_videresending_skjema_svar`
                WHERE `skjema` = '#skjema'",
                [
                    'skjema' => $this->getId()
                ]
            );

            $res = $select->run();

            while( $db_row = Query::fetch( $res ) ) {
                $svar = SvarSett::createFromDatabase( $db_row );
                $this->svar[ $svar->getFra() ] = $svar;
            }
        }

        return $this->svar;
    }

    /**
     * Hent svar fra ett gitt arrangement
     *
     * @param Int $pl_id
     * @return SvarSett $svar
     */
    public function getSvarSettFor( Int $pl_id ) {
        if( !isset( $this->getSvar()[ $pl_id ] ) ) {
            throw new Exception(
                'Arrangement '. $pl_id .' har ikke svart på dette skjemaet',
                151002
            );
        }
        return $this->getSvar()[ $pl_id ];
    }

    /**
     * Hent skjema-ID
     * 
     * @return Int $id
     */ 
    public function getId()
    {
        return $this->id;
    }
}