<?php

namespace UKMNorge\Sensitivt;

require_once('UKM/Sensitivt/Requester.php');

use SQL, SQLins, Exception;

abstract class Sensitivt {

    private static $requester = null;

    /**
     * Constructor
     * 
     * $id brukes ikke av abstract, men er med for å ha riktig
     * antall parametre
     *
     * @param Requester $requester
     * @param Int $id
     */
    public function __construct( $id ) {
        $this->id = $id;
    }

    public static function setRequester( $requester ) {
        if( !is_object( $requester ) || get_class( $requester ) !== 'UKMNorge\Sensitivt\Requester' ) {
            throw new Exception(
                'Kan ikke lese ut sensitiv data uten gitt requester-objekt',
                117001
            );
        }

        if( !$requester->isReady() ) {
            throw new Exception(
                'Kan ikke lese ut sensitiv data uten godkjent requester-objekt',
                117002
            );
        }

        self::$requester = $requester;
    }

    /**
     * ID for objektet vi henter informasjon om
     * Child-klassen setter type objekt
     *
     * @return Int $id
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Informasjon om personen som henter informasjonen
     * Settes statisk på Sensitivt-klassen, da dette kun skal settes én gang per script,
     * og aldri kunne endres underveis.
     *
     * @return UKMNorge\Sensitivt\Requester $requester
     */
    public function getRequester() {
        return self::$requester;
    }

   
    /**
     * Returner første data-rad fra databasen
     *
     * @param $res
     * @return Array $data
     */
    public function getFirstRow( $res ) {
        return SQL::fetch( $res );
    }

    /**
     * Gjennomfør en database-spørring
     * Sensitivt-klassene skal ikke bruke SQL direkte. Gjennom denne funksjonen sikrer 
     * vi riktig logging 
     *
     * @param String $sql
     * @param Array $data
     * @return UKMNorge\SQL->run() $res // eh, uh.
     */
    public function query( $sql, $data ) {
        if( self::getRequester()->isReady() ) {

            $this->log();
            $sql = new SQL( $sql, $data );

            $res = $sql->run();
            return $res;
        }

        throw new Exception(
            'Sensitivt kan ikke kjøre spørringer uten godkjent requester-objekt',
            117003
        );
    }

    /**
     * 
     *
     * @param [type] $field
     * @param [type] $value
     * @return void
     */
    public function update( $field, $value ) {
        if( self::getRequester()->isReady() ) {

            $this->log('write');

            // TODO: SJEKK OM RAD ER SATT INN FRA FØR AV, ELLER OM DEN SKAL INSERTES
            $sql = new SQLins( 
                static::DB_TABLE, 
                [
                    static::DB_ID => $this->getId()
                 ]
            );
            $sql->add( $field, $value );
            echo $sql->debug();
            $res = $sql->run();
            
            if( $res ) {
                return true;
            }

            throw new Exception(
                'Sensitivt felt '. $field .' ble ikke lagret',
                107006
            );
        }

        throw new Exception(
            'Sensitivt kan ikke lagre uten godkjent requester-objekt',
            117005
        );
    }

    /**
     * Logg forespørsel om informasjon
     *
     * @param String $direction read|write
     * @throws Exception hvis logging feilet
     * @return bool true
     */
    public function log( $direction='read' ) {
        $sql = new SQLins('log_sensitivt');
        
        $sql->add( 'direction', $direction);

        $sql->add( 'object_id', $this->getId() );
        $sql->add( 'object_type', get_called_class() );

        $sql->add( 'user_id', self::getRequester()->getMonstringId() );
        $sql->add( 'user_system', self::getRequester()->getSystem() );
        $sql->add( 'user_ip', self::getRequester()->getIp() );
        
        $sql->showError();
        $insert_id = $sql->run();
        if( !$insert_id ) {
            throw new Exception(
                'Kunne ikke logge forespørsel om sensitiv informasjon'
                .' ('. $sql->getError() .') '
                ,
                117004
            );
        }
        
        return true;
    }
}