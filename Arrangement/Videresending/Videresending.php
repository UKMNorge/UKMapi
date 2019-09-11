<?php

namespace UKMNorge\Arrangement\Videresending;

use UKMNorge\Database\SQL\Query;
use Exception, DateTime;
use fylker, kommune;

class Videresending
{

    private $id;
    private $mottakere;
    private $avsendere;

    public function __construct(Int $pl_id)
    {
        $this->id = $pl_id;
    }

    public function harMottaker( Int $mottaker_id ) {
        try {
            $this->getMottaker( $mottaker_id );
            return true;
        } catch( Exception $e ) {
            return false;
        }
    }

    public function getMottaker( Int $mottaker_id ) {
        if( isset( $this->getMottakere()[$mottaker_id] ) ) {
            return $this->mottakere[ $mottaker_id ];
        }
        throw new Exception(
            'Har ikke mottaker '. $mottaker_id
        );
    }

    public function harAvsender( Int $avsender_id ) {
        try {
            $this->getAvsender( $avsender_id );
            return true;
        } catch( Exception $e ) {
            return false;
        }
    }

    public function getAvsender( Int $avsender_id ) {
        if( isset( $this->getAvsendere()[$avsender_id] ) ) {
            return $this->avsendere[ $avsender_id ];
        }
        throw new Exception(
            'Har ikke mottaker '. $avsender_id
        );
    }
    /**
     * Hvem kan denne mønstringen sende innslag til?
     *
     * @return Array Arrangement
     */
    public function getMottakere()
    {
        if (null == $this->mottakere) {
            require_once('UKM/Arrangement/Videresending/Mottaker.php');
            $this->_loadMottakere();
        }
        return $this->mottakere;
    }

    /**
     * Hvem kan sende innslag til denne mønstringen?
     *
     * @return Array Arrangement
     */
    public function getAvsendere()
    {
        if (null == $this->avsendere) {
            require_once('UKM/Arrangement/Videresending/Avsender.php');
            $this->_loadAvsendere();
        }
        return $this->avsendere;
    }

    /**
     * Load avsendere
     *
     * @return void
     */
    private function _loadAvsendere()
    {
        return $this->_load('avsendere');
    }

    /**
     * Load mottakere
     *
     * @return void
     */
    private function _loadMottakere()
    {
        return $this->_load('mottakere');
    }


    public function leggTilMottaker( Mottaker $mottaker ) {
        $this->mottakere[ $mottaker->getId() ] = $mottaker;
        return $this;
    }

    public function leggTilAvsender( Avsender $avsender ) {
        $this->avsendere[ $avsender->getId() ] = $avsender;
        return $this;
    }

    /**
     * Faktisk load
     *
     * @param String $type (avsendere|mottakere)
     * @return void
     */
    private function _load( $type ) {
        require_once('UKM/Database/SQL/select.class.php');
        require_once('UKM/fylker.class.php');
        require_once('UKM/kommune.class.php');

        $sql = new Query(
            "SELECT `rel`.*,
            `place`.`pl_name`, 
            `place`.`pl_start`,
            `place`.`pl_owner_fylke`,
            `place`.`pl_owner_kommune`,
            `place`.`pl_registered`
            FROM `ukm_rel_pl_videresending` AS `rel`
            JOIN `smartukm_place` AS `place`
                ON( `place`.`pl_id` = `rel`.`#motsatt_retning`)
            WHERE `rel`.`#retning` =  '#pl_id'",
            [
                'motsatt_retning' => $type != 'mottakere' ? 'pl_id_sender' : 'pl_id_receiver',
                'retning' => $type == 'mottakere' ? 'pl_id_sender' : 'pl_id_receiver',
                'pl_id' => $this->getId()
            ]
        );

        $res = $sql->run();
        while( $row = Query::fetch( $res ) ) {

            if( $row['pl_owner_fylke'] > 0 ) {
                $eier = fylker::getById( $row['pl_owner_fylke'] );
            }
            elseif( $row['pl_owner_kommune'] > 0 ) {
                $eier = new kommune( $row['pl_owner_kommune'] );
            }

            $class = $type == 'avsendere' ? 
                new Avsender( (Int) $row['pl_id_sender'], (Int) $row['pl_id_receiver'] ):
                new Mottaker( (Int) $row['pl_id_receiver'], (Int) $row['pl_id_sender'] );
            ;

            // "Proxy" navn for hurtig-load
            $class->setProxyData(
                $row['pl_name'],
                $row['pl_registered'] == 'true',
                new DateTime($row['pl_start']),
                $eier
            );
            
            $this->$type[ $class->getId() ] = $class;
        }
    }

    /**
     * Hent Arrangement-ID
     */ 
    public function getId()
    {
        return $this->id;
    }
}
