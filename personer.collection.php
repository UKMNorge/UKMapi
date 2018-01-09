<?php
require_once('UKM/person.class.php');

class personer {
	
	var $b_id = null;
	var $personer = null;
	var $personer_videresendt = null;
	var $personer_ikke_videresendt = null;
	var $debug = false;
	
	public function __construct( $b_id ) {
		$this->b_id = $b_id;
		
		$this->_load();
	}
	
	/**
	 * 
	 * Relaterer en person til dette innslaget.
	 *
	 * @param write_person $person
	 *
	 * @return $this
	 */
	public function leggTil($person) {
		if('write_person' != get_class($person)) {
			throw new Exception("PERSONER_V2: Kan ikke fjerne en person uten skriverettigheter fra et innslag.");
		}
		if(!is_numeric($person->getId()) ) {
			throw new Exception("PERSONER_V2: Kan ikke slette en person fra et innslag uten en numerisk ID!");
		}

		if(null == $this->_getBID() || empty($this->_getBID()) ) {
			throw new Exception("PERSONER_V2: Kan ikke slette med tom b_id.");
		}

		$sql = new SQL("SELECT COUNT(*) 
						FROM smartukm_rel_b_p 
						WHERE 'b_id' = '#b_id' 
							AND 'p_id' = '#p_id'",
						array(	'b_id' => $this->_getBID(), 
								'p_id' => $person->getId()) 
						);
		$exists = $sql->run('field', 'COUNT(*)');

		if($exists) {
			return true;
		}

		$sql = new SQLins("smartukm_rel_b_p");
		$sql->add('b_id', $this->_getBID());
		$sql->add('p_id', $person->getId());
		/*$sql->add('b_p_year', $this->getSesong());
		$sql->add('season', $this->getSesong());*/

		$res = $sql->run();
		if(false == $res)
			return false;
			
		$this->_load();
		return true;
	}
	
	public function fjern($person) {
		if('write_person' != get_class($person)) {
			throw new Exception("PERSONER_V2: Kan ikke fjerne en person uten skriverettigheter fra et innslag.");
		}
		if(!is_numeric($person->getId()) ) {
			throw new Exception("PERSONER_V2: Kan ikke slette en person fra et innslag uten en numerisk ID!");
		}

		if(null == $this->_getBID() || empty($this->_getBID()) ) {
			throw new Exception("PERSONER_V2: Kan ikke slette med tom b_id.");
		}

		$sql = new SQLdel("smartukm_rel_b_p", 
			array( 	'b_id' => $this->_getBID(),
					'p_id' => $person->getId(),
					));

		$res = $sql->run();
		return $res;
	}

	/**
	 * 
	 * Relaterer en person til dette innslaget.
	 *
	 * @param write_person $person
	 *
	 * @return $this
	 */
	public function videresend($person, $monstring_til, $monstring_fra) {
		if('write_person' != get_class($person)) {
			throw new Exception("PERSONER_V2: Kan ikke videresende en person uten skriverettigheter på personen.");
		}
		if(!is_numeric($person->getId()) ) {
			throw new Exception("PERSONER_V2: Kan ikke videresende en person i et innslag uten en numerisk ID!");
		}

		if('write_monstring' != get_class($monstring_til)) {
			throw new Exception("PERSONER_V2: Kan ikke videresende en person uten skriverettigheter på til-mønstringen.");
		}
		if(!is_numeric($monstring_til->getId()) ) {
			throw new Exception("PERSONER_V2: Kan ikke videresende en person i en til-mønstring uten en numerisk ID!");
		}

		if('write_monstring' != get_class($monstring_fra)) {
			throw new Exception("PERSONER_V2: Kan ikke videresende en person uten skriverettigheter på fra-mønstringen.");
		}
		if(!is_numeric($monstring_fra->getId()) ) {
			throw new Exception("PERSONER_V2: Kan ikke videresende en person i en fra-mønstring uten en numerisk ID!");
		}
		
		
		// FOR INNSLAG I KATEGORI 1 (SCENE) FØLGER ALLE DELTAKERE ALLTID INNSLAGET VIDERE
		$innslag = new innslag_v2( $this->_getBID() );
		if( $innslag->getType()->getId() == 1 ) {
			return true;
		}
		
		/**
			Videresendingsmodulen viser titler, og ikke innslag for å gjøre det enklere for brukeren.
			Videresending skjer derfor på tittel-nivå, og for å ikke tilfeldigvis fjerne en person fra en mønstring
			benyttes $tittel-id som et input-parameter.
			Dette bør løses i videresendingsmodulen, ikke i API.
			API V2 skal  derfor ikke ta hensyn til disse, men gjør det inntil videresendingsfunksjonen er skrevet om.
		**/
		
		if( $innslag->getType()->harTitler() ) {
			$t_ids = '';
			foreach( $innslag->getTitler( $monstring_til )->getAllVideresendt( $monstring_til ) as $tittel ) {
				$t_ids .= '|t_'. $tittel->getId() .'|';
			}
		} else {
			$t_ids ='|t_notitle|';
		}
		
		$test_relasjon = new SQL(
			"SELECT * FROM `smartukm_fylkestep_p`
				WHERE `pl_id` = '#pl_id'
				AND `b_id` = '#b_id'
				AND `pl_from` = '#pl_from'
				AND `p_id` = '#p_id'",
			[
				'pl_id'		=> $monstring_til->getId(), 
				'pl_from'	=> $monstring_fra->getId(),
		  		'b_id'		=> $this->_getBID(), 
				'p_id'		=> $person->getId(),
			]
		);
		$test_relasjon = $test_relasjon->run();
		
		if( mysql_num_rows($test_relasjon) == 0 ) {
			$videresend_person = new SQLins('smartukm_fylkestep_p');
			$videresend_person->add('pl_id', $monstring_til->getId() );
			$videresend_person->add('pl_from', $monstring_fra->getId() );
			$videresend_person->add('b_id', $this->_getBID() );
			$videresend_person->add('p_id', $person->getId() );
			$videresend_person->add('t_ids', $t_ids );
			$res = $videresend_person->run();
		} else {
			$row = mysql_fetch_assoc( $test_relasjon );
			$videresend_person = new SQLins(
				'smartukm_fylkestep_p', 
				[
					'pl_id' 	=> $monstring_til->getId(),
					'pl_from'	=> $monstring_fra->getId(),
					'b_id' 		=> $this->_getBID(),
					'p_id' 		=> $person->getId()
				 ]
			);
			$videresend_person->add('t_ids', $t_ids );
			$res = $videresend_person->run();
			
		}
		
		if( $res ) {
			return true;
		}

		throw new Exception('PERSONER_COLLECTION: Kunne ikke videresende '. $person->getNavn() .' fra '. $innslag->getNavn() );
	}

	/**
	 * 
	 * Relaterer en person til dette innslaget.
	 *
	 * @param write_person $person
	 *
	 * @return $this
	 */
	public function avmeld($person, $monstring_til, $monstring_fra) {
		if('write_person' != get_class($person)) {
			throw new Exception("PERSONER_V2: Kan ikke avmelde en person uten skriverettigheter på personen.");
		}
		if(!is_numeric($person->getId()) ) {
			throw new Exception("PERSONER_V2: Kan ikke avmelde en person i et innslag uten en numerisk ID!");
		}

		if('write_monstring' != get_class($monstring_til)) {
			throw new Exception("PERSONER_V2: Kan ikke avmelde en person uten skriverettigheter på til-mønstringen.");
		}
		if(!is_numeric($monstring_til->getId()) ) {
			throw new Exception("PERSONER_V2: Kan ikke avmelde en person i en til-mønstring uten en numerisk ID!");
		}

		if('write_monstring' != get_class($monstring_fra)) {
			throw new Exception("PERSONER_V2: Kan ikke avmelde en person uten skriverettigheter på fra-mønstringen.");
		}
		if(!is_numeric($monstring_fra->getId()) ) {
			throw new Exception("PERSONER_V2: Kan ikke avmelde en person i en fra-mønstring uten en numerisk ID!");
		}

		// FOR INNSLAG I KATEGORI 1 (SCENE) FØLGER ALLE DELTAKERE ALLTID INNSLAGET VIDERE
		$innslag = new innslag_v2( $this->_getBID() );
		if( $innslag->getType()->getId() == 1 ) {
			return true;
		}
		
		/**
			Videresendingsmodulen viser titler, og ikke innslag for å gjøre det enklere for brukeren.
			Videresending skjer derfor på tittel-nivå, og for å ikke tilfeldigvis fjerne en person fra en mønstring
			benyttes $tittel-id som et input-parameter.
			Dette bør løses i videresendingsmodulen, ikke i API.
			API V2 skal  derfor ikke ta hensyn til disse, men gjør det inntil videresendingsfunksjonen er skrevet om.
		**/
		
		if( $innslag->getType()->harTitler() ) {
			$t_ids = '';
			foreach( $innslag->getTitler( $monstring_til )->getAllVideresendt( $monstring_til ) as $tittel ) {
				$t_ids .= '|t_'. $tittel->getId() .'|';
			}
		} else {
			$t_ids ='|t_notitle|';
		}
		
		$videresend_person = new SQLdel(
			'smartukm_fylkestep_p', 
			[
				'pl_id' 	=> $monstring_til->getId(),
				'pl_from'	=> $monstring_fra->getId(),
				'b_id' 		=> $this->_getBID(),
				'p_id' 		=> $person->getId()
			]
		);
		$res = $videresend_person->run();
		
		if( $res ) {
			return true;
		}

		throw new Exception('PERSONER_COLLECTION: Kunne ikke avmelde '. $person->getNavn() .' fra '. $innslag->getNavn() );
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
	 * harPerson
	 * Er personen med i innslaget. OBS: Tar ikke høyde for videresending!
	 *
	 * @param object person
	 * @return boolean
	**/
	public function harPerson( $har_person ) {
		foreach( $this->getAll() as $person ) {
			if( $person->getId() == $har_person->getId() ) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * getById
	 * Finn en person med gitt ID
	 *
	 * @param integer id
	 * @return person
	**/
	public function getById( $id ) {
		foreach( $this->getAll() as $person ) {
			if( $person->getId() == $id ) {
				return $person;
			}
		}
		throw new Exception('PERSONER_V2: Kunne ikke finne person '. $id .' i innslag '. $this->_getBID());
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
						array('bid' => $this->_getBID() ));
		$res = $SQL->run();
		if( isset( $_GET['debug'] ) || $this->debug )  {
			echo $SQL->debug();
		}
		while( $r = mysql_fetch_assoc( $res ) ) {
			$person = new person_v2( $r );
			$this->personer[ $person->getId() ] = $person;
		}
	}
	
	private function _setBID( $bid ) {
		$this->b_id = $bid;
		return $this;
	}
	private function _getBID() {
		return $this->b_id;
	}
}
