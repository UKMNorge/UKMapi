<?php
require_once('UKM/person.class.php');

class personer {
	
	var $innslag_id = null;
	var $innslag_type = null;
	var $personer = null;
	var $personer_videresendt = null;
	var $personer_ikke_videresendt = null;
	var $debug = false;
	
	public function __construct( $innslag_id, $innslag_type ) {
		$this->_setInnslagType( $innslag_type );
		$this->_setInnslagId( $innslag_id );
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
	 * Hent alle personer i innslaget videresendt til gitt mønstring
	 *
	 * @param int $pl_id
	 * @return bool
	**/
	public function getAllVideresendt( $pl_id ) {
		if( is_object( $pl_id ) && get_class( $pl_id ) == 'monstring_v2' ) {
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
	 * Hent alle personer i innslaget videresendt til gitt mønstring
	 *
	 * @param int $pl_id
	 * @return bool
	**/
	public function getAllIkkeVideresendt( $pl_id ) {
		if( is_object( $pl_id ) && get_class( $pl_id ) == 'monstring_v2' ) {
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
	
	/**
	 * getById
	 * Finn en person med gitt ID
	 *
	 * @param integer id
	 * @return person
	**/
	public function getById( $id ) {
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
		throw new Exception('PERSONER_COLLECTION: Kunne ikke finne person '. $id .' i innslag '. $this->_getInnslagId(), 2);
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
	 * DATABASE MODIFYING FUNCTIONS (WRITE)
	 *
	 *
	 ********************************************************************************/

	/**
	 * Legger til en person i innslaget, og videresender til gitt mønstring
	 *
	 * @param write_person $person
	 * @param write_monstring $monstring
	 *
	 * @return (bool true|throw exception)
	 */
	public function leggTil( $person, $monstring ) {
		$this->_validateInput( $person, $monstring );

		// Alltid legg til personen lokalt
		$res = $this->_leggTilLokalt( $person );

		// Videresend personen hvis ikke lokalmønstring
		if( $res && $monstring->getType() != 'kommune' ) {
			$res = $this->_leggTilVideresend( $person, $monstring );
		}
		
		if( $res ) {
			return $this;
		}
		
		throw new Exception('PERSONER_COLLECTION: Kunne ikke legge til '. $person->getNavn() .' i innslaget');
	}
	
	/**
	 * Fjern en videresendt person, og avmelder hvis gitt lokalmønstring
	 *
	 * @param write_person $person
	 * @param write_monstring $monstring
	 *
	 * @return (bool true|throw exception)
	 */
	public function fjern( $person, $monstring ) {
		$this->_validateInput( $person, $monstring );

		if( $monstring->getType() == 'kommune' ) {
			$res = $this->_fjernLokalt( $person, $monstring );
		} else {
			$res = $this->_fjernVideresend( $person, $monstring );
		}
		
		if( $res ) {
			return true;
		}
		
		throw new Exception('PERSONER_COLLECTION: Kunne ikke fjerne '. $person->getNavn() .' fra innslaget');
	}
	


	/********************************************************************************
	 *
	 *
	 * PRIVATE HELPER FUNCTIONS
	 *
	 *
	 ********************************************************************************/
	
	/**
	 * Valider at alle input-parametre er klare for write-actions
	 *
	 * @param write_person $person
	 * @param write_monstring $monstring
	 * @return void
	**/
	private function _validateInput( $person, $monstring ) {
		// Personen
		if( 'write_person' != get_class( $person ) ) {
			throw new Exception('PERSONER_COLLECTION: Kan ikke legge til eller fjerne en person som ikke er skrivbar');
		}
		if( !is_numeric( $person->getId() ) ) {
			throw new Exception('PERSONER_COLLECTION: Kan ikke legge til eller fjerne en person uten numerisk ID!');
		}
		
		// Innslaget
		if( null == $this->_getInnslagId() || empty( $this->_getInnslagId() ) ) {
			throw new Exception('PERSONER_COLLECTION: Kan ikke legge til eller fjerne en person når innslag-ID er tom');
		}
		if( !is_numeric( $this->_getInnslagId() ) ) {
			throw new Exception('PERSONER_COLLECTION: Kan ikke legge til eller fjerne en person i innslag med ikke-numerisk ID');
		}
		
		// Mønstringen
		if( 'write_monstring' != get_class( $monstring ) ) {
			throw new Exception("PERSONER_COLLECTION: Kan ikke legge til eller fjerne en person uten skriverettigheter for mønstringen.");
		}
		if( !is_numeric( $monstring->getId() ) ) {
			throw new Exception("PERSONER_COLLECTION: Kan ikke legge til eller fjerne en person i en mønstring uten numerisk ID!");
		}
	}
	
	/**
	 * Legg til en person på lokalnivå (ikke videresend)
	 *
	 * @param write_person $person
	 * @return bool $success
	**/
	private function _leggTilLokalt( $person ) {
		// Er personen allerede lagt til i innslaget?
		$sql = new SQL("SELECT COUNT(*) 
						FROM smartukm_rel_b_p 
						WHERE 'b_id' = '#b_id' 
							AND 'p_id' = '#p_id'",
						array(	'b_id' => $this->_getInnslagId(), 
								'p_id' => $person->getId()) 
						);
		$exists = $sql->run('field', 'COUNT(*)');
		if($exists) {
			return true;
		}

		// Legg til i innslaget
		$sql = new SQLins("smartukm_rel_b_p");
		$sql->add('b_id', $this->_getInnslagId());
		$sql->add('p_id', $person->getId());

		UKMlogger::log( 324, $this->_getInnslagId(), $person->getId().': '. $person->getNavn() );
		$res = $sql->run();
		
		if(false == $res)
			return false;
		
		// Load re-initierer collection-arrayet
		$this->_load();
		
		return true;
	}
	
	/**
	 * Legg til en person på videresendt nivå
	 * Vil automatisk legge til personen på lokalt nivå
	 *
	 * @param write_person $person
	 * @param write_monstring $monstring
	**/
	private function _leggTilVideresend( $person, $monstring ) {
		// FOR INNSLAG I KATEGORI 1 (SCENE) FØLGER ALLE DELTAKERE ALLTID INNSLAGET VIDERE
		if( $this->_getInnslagType()->getId() == 1 ) {
			return true;
		}
		
		$test_relasjon = new SQL(
			"SELECT * FROM `smartukm_fylkestep_p`
				WHERE `pl_id` = '#pl_id'
				AND `b_id` = '#b_id'
				AND `p_id` = '#p_id'",
			[
				'pl_id'		=> $monstring->getId(), 
		  		'b_id'		=> $this->_getInnslagId(), 
				'p_id'		=> $person->getId(),
			]
		);
		$test_relasjon = $test_relasjon->run();
		
		// Hvis allerede videresendt, alt ok
		if( mysql_num_rows($test_relasjon) > 0 ) {
			return true;
		}
		// Videresend personen
		else {
			$videresend_person = new SQLins('smartukm_fylkestep_p');
			$videresend_person->add('pl_id', $monstring->getId() );
			$videresend_person->add('b_id', $this->_getInnslagId() );
			$videresend_person->add('p_id', $person->getId() );

			UKMlogger::log( 320, $this->_getInnslagId(), $person->getId().': '. $person->getNavn() .' => '. $monstring->getNavn() );
			$res = $videresend_person->run();
		
			if( $res ) {
				return true;
			}
		}

		throw new Exception('PERSONER_COLLECTION: Kunne ikke videresende '. $person->getNavn() .' fra innslaget' );
	}


	/**
	 * Fjerner en person helt fra innslaget (avmelding lokalnivå)
	 *
	 * @param write_person $person
	 * @param write_monstring $monstring
	 *
	 * @return (bool true|throw exception)
	 */	 
	private function _fjernLokalt( $person, $monstring ) {
		$sql = new SQLdel("smartukm_rel_b_p", 
			array( 	'b_id' => $this->_getInnslagId(),
					'p_id' => $person->getId(),
					));
		UKMlogger::log( 325, $this->_getInnslagId(), $person->getId().': '. $person->getNavn() );
		$res = $sql->run();
		if( $res ) {
			return true;
		}
		throw new Exception('PERSONER_COLLECTION: Kunne ikke fjerne personen fra innslaget');
	}

	/**
	 * 
	 * Avrelaterer en person til dette innslaget.
	 *
	 * @param write_person $person
	 * @param write_monstring $monstring
	 *
	 * @return (bool true|throw exception)
	 */
	public function _fjernVideresend( $person, $monstring ) {
		// FOR INNSLAG I KATEGORI 1 (SCENE) FØLGER ALLE DELTAKERE ALLTID INNSLAGET VIDERE
		if( $this->_getInnslagType()->getId() == 1 ) {
			return false;
		}

		$videresend_person = new SQLdel(
			'smartukm_fylkestep_p', 
			[
				'pl_id' 	=> $monstring->getId(),
				'b_id' 		=> $this->_getInnslagId(),
				'p_id' 		=> $person->getId()
			]
		);
		UKMlogger::log( 321, $this->_getInnslagId(), $person->getId().': '. $person->getNavn() .' => '. $monstring->getNavn() );

		$res = $videresend_person->run();
		
		if( $res ) {
			return true;
		}

		throw new Exception('PERSONER_COLLECTION: Kunne ikke avmelde '. $person->getNavn() .' fra innslaget' );
	}


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
						array('bid' => $this->_getInnslagId() ));
		$res = $SQL->run();
		if( isset( $_GET['debug'] ) || $this->debug )  {
			echo $SQL->debug();
		}
		if($res === false) {
			throw new Exception("PERSONER_COLLECTION: Klarte ikke hente personer og roller - kan databaseskjema være utdatert?");
		}
		while( $r = mysql_fetch_assoc( $res ) ) {
			$person = new person_v2( $r );
			$this->personer[ $person->getId() ] = $person;
		}
	}
	
	private function _setInnslagId( $bid ) {
		$this->innslag_id = $bid;
		return $this;
	}
	private function _getInnslagId() {
		return $this->innslag_id;
	}
	
	private function _setInnslagType( $type ) {
		if( is_object( $type ) && get_class( $type ) == 'innslag_type' ) {
			$this->innslag_type = $type;
			return $this;
		}
		throw new Exception('PERSONER_COLLECTION: Innslag-type må være angitt som objekt');
	}
	
	private function _getInnslagType() {
		return $this->innslag_type;
	}
}
