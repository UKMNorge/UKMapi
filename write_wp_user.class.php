<?php
require_once('UKM/wp_user.class.php');
require_once('UKM/logger.class.php');
require_once('UKM/inc/password.inc.php');

class write_wp_UKM_user extends wp_UKM_user {
	
	/**
	 * Opprett bruker.
	 * write_wp_UKM_user::create vil selv sjekke om brukeren 
	 * finnes i wordpress med gitt brukernavn, og deretter
	 * opprette / koble objektene
	 *
	 * @param string $username
	 * @param string $email
	 * @param string $password
	 * @param int $fylke
	 * @param int $kommune_id
	 * @param int $wp_id
	 *
	 * @return write_wp_UKM_user
	**/
	public static function create( $username, $email, $password, $fylke_id, $kommune_id, $wp_id=false ) {
		global $wpdb; 
		
		$brukerinfo = array(
						'b_name'	=> $username,
						'b_password'=> $password,
						'b_email'	=> $email,
						'b_kommune'	=> $kommune_id,
						'b_fylke' 	=> $fylke_id
					);
		// Vi vet ikke WP-ID (sjekk om den finnes før vi oppretter)
		if( $wp_id == false ) {
			$user = wp_UKM_user::getWPUser( $username, 'username' );
			if( $user == null || $user == false ) {
				$user = write_wp_UKM_user::createWpUser( $username, $password, $email );
			}
		} else {
			$user = wp_UKM_user::getWPUser( $wp_id, 'id' );
			if( $user == null || $user == false ) {
				$user = write_wp_UKM_user::createWpUser( $username, $password, $email );
			}
		}
		$wp_id = $user->ID;
		$brukerinfo['wp_bid'] = $wp_id;
	
		$result = $wpdb->insert('ukm_brukere',$brukerinfo);
		if( !$result ) {
			throw new Exception( $wpdb->last_error );
		}
		
		$bruker_id = $wpdb->insert_id;

		$brukerinfo['b_id'] = $bruker_id;
		$brukerinfo['lock_email'] = 'false';
		
		$user_object = new write_wp_UKM_user( $brukerinfo, 'array' );
		$user_object->forceConsistency();
		return $user_object;
	}
	
	/**
	 * Opprett en wordpress-bruker
	 * 
	 * @param string $username
	 * @param string $password
	 * @param string $email
	**/
	public static function createWpUser( $username, $password, $email ) {
		$user_id = wp_create_user( $username, $password, $email );

		if( !is_string( $user_id ) && !is_numeric( $user_id ) ) {
			throw new Exception(
						'Kunne ikke opprette '. $username .' pga en Wordpress-feil! ('. 
						implode(', ', $user_id->get_error_messages()) .
						')');
		}
		
		return wp_UKM_user::getWPUser( $username, 'username' );
	}
	
	public static function finnEllerOpprettKommuneBruker( $kommune ) {
		$bruker = new write_wp_UKM_user( $kommune->getURLsafe(), 'username' );
		if( !$bruker->exists() || !$bruker->existsWP() ) {
			$bruker = write_wp_UKM_user::create( 
											$kommune->getURLsafe(), 					// Username
											$kommune->getURLsafe().'@fake.ukm.no',		// Email
											UKM_ordpass(),								// Password
											$kommune->getFylke()->getId(),				// Fylke
											$kommune->getId(),							// Kommune
											false										// WP_ID
										);
			$kommunebrukere[ $kommune->getURLsafe() ] = $bruker->getEpost();
			$bruker->forceConsistency(); // Sørger for at WP_ID og B_ID samsvarer i begge objekter
		} else {
			$kommunebrukere[ $kommune->getURLsafe() ] = $bruker->getEpost();
			$bruker->forceConsistency(); // Sørger for at WP_ID og B_ID samsvarer i begge objekter
		}
		return $bruker;
	}

	
	/**
	 * CONSTRUCT
	 * Strengere for skrivbare objekter, type er ikke autoutfylt
	 *
	 * OBS: autolagrer @ setSomething();
	 *
	 * @param (int|string|array) $id_row_or_email
	 * @param string $type
	 * @return $this
	**/
	public function __construct( $id_row_or_email, $type ) {
		parent::__construct( $id_row_or_email, $type );
	}
	
	/**
	 * Sørg for at 
	**/
	public function forceConsistency() {
		$ukm_id = (int) $this->getWPID();
		$wp_id	= (int) $this->getWPUserId( $this->getNavn(), 'username' );
		
		if( $ukm_id !== $wp_id ) {
			$this->setWPID( $wp_id );
		}
	}
	
	/**
	 * setPassord
	 *
	 * @param string $passord
	 * @return $this
	**/
	public function setPassord( $passord ) {
		if( $this->getPassord() == $passord ) {
			return $this;
		}
		
		parent::setPassord( $passord );

		global $wpdb;
		wp_set_password( $passord, $this->getWPID() );
		$wpdb->update(
						'ukm_brukere', 
						array('b_password' => $passord ), 
						array('wp_bid' => $this->getWPID() )
					);
		
		return $this;
	}
	
	/**
	 * setLock
	 *
	 * @param bool $locked
	 * @return $this
	**/
	public function setLock( $lock ) {
		if( $this->getLock() == $lock ) {
			return $this;
		}
		
		parent::setLock( $lock );
		
		global $wpdb;
		$wpdb->update(
						'ukm_brukere', 
						array('lock_email' => $lock ? 'true' : 'false' ), 
						array('wp_bid' => $this->getWPID() )
					);
		return $this;
	}
	
	/**
	 * setWPID
	 *
	 * @param int $id
	 * @return $this
	**/
	public function setWPID( $id ) {
		if( $this->getWPID() == $id ) {
			return $this;
		}
		
		parent::setWPID( $id );
		
		if( !is_numeric( $this->getId() ) ) {
			throw new Exception('Write_wp_user: Kan ikke sette WPID på bruker uten ID');
		}
		global $wpdb;
		$wpdb->update(
						'ukm_brukere', 
						array('wp_bid' => $id ), 
						array('b_id' => $this->getId() )
					);
		return $this;
	}
}