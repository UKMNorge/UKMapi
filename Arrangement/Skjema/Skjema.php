<?php

namespace UKMNorge\Arrangement\Skjema;

use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Arrangement\Eier;
use UKMNorge\Database\SQL\Query;
use Exception;

require_once('UKM/Autoloader.php');

class Skjema {

    private $id;
    private $sporsmal;
    private $svar_sett;
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
        $db_row = $query->getArray();

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
     * Hent gitt skjema
     *
     * @param Int $id
     * @return Skjema
     */
    public static function getFromId( Int $id ) {
        $query = new Query(
            "SELECT *
            FROM `ukm_videresending_skjema`
            WHERE `id` = '#id'",
            [
                'id' => $id
            ]
        );
        $db_row = $query->getArray();

        if( !$db_row ) {
            throw new Exception(
                'Finner ikke skjema '. $id,
                151002
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
     * Henter alle spørsmål, eller ett gitt spørsmål
     * 
     * @param Int Spørsmål-ID, default null
     * @throws Exception
     * @return Array<Sporsmal>|Sporsmal
     */ 
    public function getSporsmal( Int $sporsmal_id = null)
    {
        if( is_null($sporsmal_id ) ) {
            return $this->getAll();
        }
        if( !isset($this->getAll()[$sporsmal_id])) {
            throw new Exception(
                'Beklager, skjema '. $this->getId() .' har ikke spørsmål '. $sporsmal_id,
                151003
            );
        };
        return $this->getAll()[$sporsmal_id];
    }

    /**
     * Hvor mange overskrifter har skjemaet 
     *
     * @return Int
     */
    public function getAntallOverskrifter() {
        return sizeof( $this->getOverskrifter() );
    }

    /**
     * Hent alle overskrifter
     *
     * @return Array<Sporsmal>
     */
    public function getOverskrifter() {
        $this->overskrifter = [];
        foreach( $this->getAll() as $sporsmal ) {
            if( $sporsmal->getType() == 'overskrift') {
                $this->overskrifter[] = $sporsmal;
            }
        }
        return $this->overskrifter;
    }

    /**
     * Grupper spørsmål etter overskrift
     *
     * @return Array
     */
    public function getSporsmalGruppertPerOverskrift() {
        if( is_null($this->grupper)){
            $this->grupper = [];
            $count = 0;
            $current_index = 0;
            foreach( $this->getAll() as $sporsmal ) {
                if( $count == 0 && $sporsmal->getType() != 'overskrift' ) {
                    $this->grupper[] = Gruppe::createEmpty();
                    $current_index = sizeof($this->grupper)-1;
                }
                
                switch( $sporsmal->getType() ) {
                    case 'overskrift':
                        $this->grupper[] = Gruppe::createFromSporsmal($sporsmal);
                        $current_index = sizeof($this->grupper)-1;
                    break;
                    default:
                        $this->grupper[ $current_index ]->add( $sporsmal );
                }

                $count++;
            }

        }
        return $this->grupper;
    }

    /**
     * Hent alle spørsmål
     * 
     * @return Array<Sporsmal>
     */
    public function getAll() {
        if( is_null($this->sporsmal )) {
            $this->sporsmal = [];

            $select = new Query(
                "SELECT * 
                FROM `ukm_videresending_skjema_sporsmal`
                WHERE `skjema` = '#skjema'
                ORDER BY `rekkefolge` ASC",
                [
                    'skjema' => $this->getId()
                ]
            );
            $res = $select->run();
            while( $db_row = Query::fetch( $res ) ) {
                $this->addSporsmal( Sporsmal::createFromDatabase( $db_row ));
            }
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
        if( is_null($this->svar_sett) ) {
            $this->svar_sett = [];

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
                $this->svar_sett[ intval($db_row['pl_fra']) ] =
                    new SvarSett( $this->getId(), intval($db_row['pl_fra']));
            }
        }

        return $this->svar_sett;
    }

    /**
     * Hent svar fra ett gitt arrangement
     *
     * @param Int $arrangement_id
     * @return SvarSett $svar
     */
    public function getSvarSettFor( Int $arrangement_id ) {
        if( !isset( $this->getSvarSett()[ $arrangement_id ] ) ) {
            $this->svar_sett[ $arrangement_id ] = new SvarSett( $this->getId(), $arrangement_id);
        }
        return $this->getSvarSett()[ $arrangement_id ];
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