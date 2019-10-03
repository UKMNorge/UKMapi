<?php

namespace UKMNorge\Logger;

use Exception;
use UKMNorge\Database\SQL\Insert;

require_once('UKM/Autoloader.php');

class Logger {
	static $user = null;
	static $system = null;
	static $pl_id = null;
	
	static function log( $action, $object_id, $value ) {
		if( !self::ready() ) {
			throw new Exception('Logger incorrect set up.');
		}
		
		$object = substr($action, 0, (strlen($action)-2));
		
		$sql = new Insert('log_log');
		$sql->add( 'log_u_id', self::getUser() );
		$sql->add( 'log_system_id', self::getSystem() );
		$sql->add( 'log_pl_id', self::getPlId() );

		$sql->add( 'log_action', $action );
		$sql->add( 'log_object', $object );
		$sql->add( 'log_the_object_id', $object_id );

		#$sql->add( 'log_time', $time ); // GJØRES AV MYSQL (DEFAULT: CURRENT_TIMESTAMP)
		$sql->showError();
		$insert_id = $sql->run();
		if( !$insert_id ) {
			#debug_print_backtrace();
			throw new Exception("UKMlogger: Klarte ikke å logge til log_log! Feilmelding: ".$sql->getError());
		}
        
        if( is_object( $value ) && get_class( $value ) == 'DateTime' ) {
            $value = $value->format('Y-m-d H:i:s'); // Fordi databasen lagrer datoer som int
        }

		$sql = new Insert('log_value');
		$sql->add('log_id', $insert_id);
		$sql->add('log_value', addslashes( $value ));
		$res = $sql->run();
		if( !$res ) {
			throw new Exception("UKMlogger: Klarte ikke å logge til log_value!  Feilmelding: ".$sql->getError());
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
    
    static function initWP( $pl_id ) {
        self::setSystem('wordpress');
        global $current_user;
        if( get_class( $current_user ) !== 'WP_User' ) {
            throw new Exception('UKMlogger: ugyldig $current_user');
        }
        self::setUser(  $current_user->ID );
        self::setPlId( $pl_id );
    }
}