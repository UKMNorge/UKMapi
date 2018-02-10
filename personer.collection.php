<?php
require_once('UKM/person.class.php');

class personer {
	var $context = null;
	var $innslag_id = null;
	var $innslag_type = null;
	
	var $personer = null;
	var $personer_videresendt = null;
	var $personer_ikke_videresendt = null;
	var $debug = false;
	
	public function __construct( $innslag_id, $innslag_type, $context ) {
		$this->_setInnslagId( $innslag_id );
		$this->_setInnslagType( $innslag_type );
		$this->_setContext( $context );

		$this->_load();
	}

	/**
	 * getAll
	 * Returner alle personer i innslaget
	 *
	 * @return array $personer
	**/
	public function getAll() {
		return $this->personer;
	}
	
	/**
	 * getSingle
	 * Hent én enkelt person fra innslaget. 
	 * Er beregnet for tittelløse innslag, som aldri har mer enn én person
	 * Kaster Exception hvis innslaget har mer enn én person
	 *
	 * @return person_v2 $person
	**/
	public function getSingle() {
		if( 1 < $this->getAntall() ) {
			throw new Exception( 'PERSON_V2: getSingle() er kun ment for bruk med tittelløse innslag som har ett personobjekt. '
								.'Dette innslaget har '. $this->getAntall() .' personer');	
		}
		$all = $this->getAll();
		return end( $all ); // and only...
	}
	
	/**
	 * getAllVideresendt
	 * Hent alle personer i innslaget videresendt til GITT mønstring
	 *
	 * @param int $pl_id
	 * @return bool
	**/
	public function getAllVideresendt( $pl_id=false ) {
		if( $pl_id == false ) {
			$pl_id = $this->getContext()->getMonstring()->getId();
		} elseif( is_object( $pl_id ) && get_class( $pl_id ) == 'monstring_v2' ) {
			$pl_id = $pl_id->getId();
		}
		if( null == $this->personer_videresendt ) {
			$this->personer_videresendt = array();
			foreach( $this->getAll() as $person ) {
				if( $person->erVideresendt( $pl_id ) ) {
					$this->personer_videresendt[] = $person;
				}
			}
		}
		return $this->personer_videresendt;
	}

	/**
	 * getAllIkkeVideresendt
	 * Hent alle personer i innslaget videresendt til GITT mønstring
	 *
	 * @param int $pl_id
	 * @return bool
	**/
	public function getAllIkkeVideresendt( $pl_id=false ) {
		if( $pl_id == false ) {
			$pl_id = $this->getContext()->getMonstring()->getId();
		} elseif( is_object( $pl_id ) && get_class( $pl_id ) == 'monstring_v2' ) {
			$pl_id = $pl_id->getId();
		}
		if( null == $this->personer_ikke_videresendt ) {
			$this->personer_ikke_videresendt = array();
			foreach( $this->getAll() as $person ) {
				if( !$person->erVideresendt( $pl_id ) ) {
					$this->personer_ikke_videresendt[] = $person;
				}
			}
		}
		return $this->personer_ikke_videresendt;
	}

	/**
	 * getAntall
	 * Hvor mange personer er det i innslaget?
	 * Tar ikke høyde for filtrering på videresendte
	 *
	 * @return int sizeof( $this->getAll() )
	**/
	public function getAntall() {
		return sizeof( $this->getAll() );
	}
	
	public function getAntallVideresendt( $pl_id=false ) {
		return sizeof( $this->getAllVideresendt( $pl_id ) );
	}

	public function getAntallIkkeVideresendt( $pl_id=false ) {
		return sizeof( $this->getAllIkkeVideresendt( $pl_id ) );
	}
	
	/**
	 * get
	 *
	 * Finn en person med gitt ID
	 *
	 * @alias getById
	 *
	 * @param integer id
	 * @return person
	**/
	public function get( $id ) {
		if( is_object( $id ) && get_class( $id ) == 'person_v2' ) {
			$id = $id->getId();
		}
		
		if( !is_numeric( $id ) ) {
			throw new Exception('Kan ikke finne person uten ID', 1);
		}
		foreach( $this->getAll() as $person ) {
			if( $person->getId() == $id ) {
				return $person;
			}
		}
		throw new Exception('PERSONER_COLLECTION: Kunne ikke finne person '. $id .' i innslag '. $this->getInnslagId(), 2); // OBS: code brukes av harPerson
	}
	public function getById( $id ) {
		return $this->get( $id );
	}

	/**
	 * harPerson
	 * Er personen med i innslaget. OBS: Tar ikke høyde for videresending!
	 *
	 * @param object person
	 * @return boolean
	**/
	public function harPerson( $har_person ) {
		try {
			$this->getById( $har_person );
			return true;
		} catch( Exception $e ) {
			if( $e->getCode() == 2 ) {
				return false;
			}
			throw $e;
		}
	}
	public function har( $person ) {
		return $this->harPerson( $person );
	}
	
	/**
	 * harVideresendtPerson
	 * Er personen med i innslaget og videresendt til gitt mønstring?
	 *
	 * @param objekt person
	 * @param int pl_id
	 *
	**/
	public function harVideresendtPerson( $har_person, $pl_id ) {
		foreach( $this->getAll() as $person ) {
			if( $person->getId() == $har_person->getId() && $person->erVideresendt( $pl_id ) ) {
				return true;
			}
		}
	}



	/********************************************************************************
	 *
	 *
	 * MODIFISER COLLECTIONS
	 *
	 *
	 ********************************************************************************/
	public function leggTil( $person ) {
		try {
			write_person::validerPerson( $person );
		} catch( Exception $e ) {
			throw new Exception(
				'Kunne ikke legge til person. '. $e->getMessage(),
				10601
			);
		}
		
		// Hvis personen allerede er lagt til kan vi skippe resten
		if( $this->harPerson( $person ) ) {
			return true;
		}
		
		// Gi personen riktig context (hent fra collection, samme som new person herfra)
		$person->setContext( $this->getContextInnslag() );
		
		// Legg til at personen skal være videresendt
		if( $person->getContext()->getMonstring()->getType() != 'kommune' ) {
			$status_videresendt = $person->getVideresendtTil();
			$status_videresendt[] = $person->getContext()->getMonstring()->getid();
			$person->setVideresendtTil( $status_videresendt );
		}
		
		// Legg til personen i collection
		$this->personer[ $person->getId() ] = $person;

		return true;
	}

	public function fjern( $person ) {
		try {
			write_person::validerPerson( $person );
		} catch( Exception $e ) {
			throw new Exception(
				'Kunne ikke fjerne person. '. $e->getMessage(),
				10601
			);
		}
		
		if( !$this->harPerson( $person ) ) {
			return true;
		}
		
		unset( $this->personer[ $person->getId() ] );

		return true;
	}



	/********************************************************************************
	 *
	 *
	 * INTERNE HJELPE-FUNKSJONER
	 *
	 *
	 ********************************************************************************/
	/**
	 * Last inn alle personer i samlingen
	**/
	private function _load() {
		$this->personer = array();
		
		$SQL = new SQL("SELECT 
							`participant`.*, 
							`relation`.`instrument`,
							`relation`.`instrument_object`,
							GROUP_CONCAT(`smartukm_fylkestep_p`.`pl_id`) AS `pl_ids`,
							`band`.`bt_id`
						FROM `smartukm_participant` AS `participant` 
						JOIN `smartukm_rel_b_p` AS `relation` 
							ON (`relation`.`p_id` = `participant`.`p_id`) 
						LEFT JOIN `smartukm_fylkestep_p`
							ON(`smartukm_fylkestep_p`.`b_id` = '#bid' AND `smartukm_fylkestep_p`.`p_id` = `participant`.`p_id`)
						JOIN `smartukm_band` AS `band`
							ON(`band`.`b_id` = `relation`.`b_id`)
						WHERE `relation`.`b_id` = '#bid'
						GROUP BY `participant`.`p_id`
						ORDER BY 
							`participant`.`p_firstname` ASC, 
							`participant`.`p_lastname` ASC",
						array('bid' => $this->getInnslagId() ));
		$res = $SQL->run();
		if( isset( $_GET['debug'] ) || $this->debug )  {
			echo $SQL->debug();
		}
		if($res === false) {
			throw new Exception("PERSONER_COLLECTION: Klarte ikke hente personer og roller - kan databaseskjema være utdatert?");
		}
		while( $r = mysql_fetch_assoc( $res ) ) {
			$person = new person_v2( $r );
			$person->setContext( $this->getContextInnslag() );
			$this->personer[ $person->getId() ] = $person;
		}
	}
	

	public function getInnslagId() {
		return $this->innslag_id;
	}
	private function _setInnslagId( $bid ) {
		$this->innslag_id = $bid;
		return $this;
	}	

	public function getInnslagType() {
		return $this->innslag_type;
	}
	private function _setInnslagType( $type ) {
		if( is_object( $type ) && get_class( $type ) == 'innslag_type' ) {
			$this->innslag_type = $type;
			return $this;
		}
		throw new Exception('PERSONER_COLLECTION: Innslag-type må være angitt som objekt');
	}

	private function _setContext( $context ) {
		$this->context = $context;
		return $this;
	}
	public function getContext() {
		return $this->context;
	}
	
	public function getContextInnslag() {
		return context::createInnslag(
			$this->getInnslagId(),								// Innslag ID
			$this->getInnslagType(),							// Innslag type (objekt)
			$this->getContext()->getMonstring()->getId(),		// Mønstring ID
			$this->getContext()->getMonstring()->getType(),		// Mønstring type
			$this->getContext()->getMonstring()->getSesong()	// Mønstring sesong
		);
	}
}
