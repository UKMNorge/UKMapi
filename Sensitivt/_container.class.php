<?php

use UKMNorge\Database\SQL\Insert;

/**
 * SENSITIVT:
 * Access-control og logger for sensitiv person-informasjon
 * Lesing logges alltid. 
 * 
 * Informasjonshentingen kan derfor være lite effektiv,
 * men det skal heller ikke brukes så ofte, så det er ok.
 * 
 * Klassen instantieres som en egenskap på et objekt, f.eks Person,
 * og aksesseres via ->getSensitivt()->getSomething...
 * 
**/



abstract class container {
    static $user = null;
    static $system = null;
    static $pl_id = null;
    
    private function _read() {
        if( !self::ready() ) {
            throw new Exception(
                'Sensitiv informasjon ikke tilgjengelig uten korrekt requester ID',
                117001
            );
        }
        
        $object = substr($action, 0, (strlen($action)-2));
        
        $sql = new Insert('log_sensitivt');
        $sql->add( 'log_u_id', self::getUser() );
        $sql->add( 'log_system_id', self::getSystem() );
        $sql->add( 'log_pl_id', self::getPlId() );
        $sql->add( 'log_ip', self::getIp() );

        $sql->add( 'log_type', static::getType() );
        $sql->add( 'log_id', $this->getId() );

        $sql->showError();
        $insert_id = $sql->run();
        if( !$insert_id ) {
            throw new Exception(
                "UKMlogger: Klarte ikke å logge til log_sensitvit! Feilmelding: ".$sql->getError(),
                117002
            );
        }
        
        return true;
    }
    
    static function ready() {
        if( null == self::getUser() || null == self::getPlId() || null == self::getSystem() ) {
            return false;
        }
        return true;
    }
    
    static function setPlId( $pl_id ) {
        self::$pl_id = $pl_id;
    }
    static function getPlId() {
        return self::$pl_id;
    }
    
    static function setUser( $user ) {
        self::$user = $user;
    }
    static function getUser() {
        return self::$user;
    }
    
    static function setSystem( $system ) {
        self::$system = $system;
    }
    static function getSystem() {
        return self::$system;
    }
    
    static function setID( $system, $user, $pl_id ) {
        self::setSystem( $system );
        self::setUser( $user );
        self::setPlId( $pl_id );    
    }

    static function setRequesterId( $system, $user, $pl_id ) {
		self::setSystem( $system );
		self::setUser( $user );
		self::setPlId( $pl_id );
	}
}