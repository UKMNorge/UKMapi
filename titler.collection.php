<?php
require_once('UKM/tid.class.php');
	
class titler {
	var $titler = null;
	var $titler_videresendt = null;
	var $titler_ikke_videresendt = null;
	var $table = null;
	var $table_field_title = null;
	var $varighet = 0;

	var $innslag_id = null;
	var $innslag_type = null;
	
	var $monstring_type = null;
	var $monstring_sesong = null;
	var $monstring_id = null;

		
	public function __construct( $innslag_id, $innslag_type, $monstring ) {
		$this->_setInnslagId( $innslag_id );
		$this->_setInnslagType( $innslag_type );
		$this->_setMonstringId( $monstring->getId() );
		$this->_setMonstringType( $monstring->getType() );
		$this->_setMonstringSesong( $monstring->getSesong() );
	}

	/**
	 * Hent alle titler
	 *
	 * @return array [tittel_v2]
	**/
	public function getAll() {
		if( null == $this->titler ) {
			$this->_load();
		}
		return $this->titler;
	}

	/** 
	 * Hent antall titler i samlingen
	 *
	 * @return int sizeof( $this->titler )
	**/
	public function getAntall() {
		return sizeof( $this->getAll() );
	}

	/**
	 * get
	 * Finn en tittel med gitt ID
	 *
	 * @param integer id
	 * @return person
	**/
	public function get( $id ) {
		foreach( $this->getAll() as $tittel ) {
			if( $tittel->getId() == $id ) {
				return $tittel;
			}
		}
		throw new Exception('PERSONER_V2: Kunne ikke finne tittel '. $id .' i innslag '. $this->_getInnslagId());
	}




	/********************************************************************************
	 *
	 *
	 * GET FILTERED SUBSETS FROM COLLECTION
	 *
	 *
	 ********************************************************************************/

	/**
	 * getAllVideresendt
	 * Hent alle titler i innslaget videresendt til gitt mønstring
	 *
	 * @param int $pl_id
	 * @return bool
	**/
	public function getAllVideresendt( $pl_id ) {
		if( null == $this->titler_videresendt ) {
			$this->titler_videresendt = array();
			foreach( $this->getAll() as $tittel ) {
				if( $tittel->erVideresendt( $pl_id ) ) {
					$this->titler_videresendt[] = $tittel;
				}
			}
		}
		return $this->titler_videresendt;
	}

	/**
	 * getAllIkkeVideresendt
	 * Hent alle titler i innslaget videresendt til gitt mønstring
	 *
	 * @param int $pl_id
	 * @return bool
	**/
	public function getAllIkkeVideresendt( $pl_id ) {
		if( null == $this->titler_ikke_videresendt ) {
			$this->titler_ikke_videresendt = array();
			foreach( $this->getAll() as $tittel ) {
				if( !$tittel->erVideresendt( $pl_id ) ) {
					$this->titler_ikke_videresendt[] = $tittel;
				}
			}
		}
		return $this->titler_ikke_videresendt;
	}




	/********************************************************************************
	 *
	 *
	 * DATABASE MODIFYING FUNCTIONS (WRITE)
	 *
	 *
	 ********************************************************************************/

	/**
	 * Fjern en videresendt tittel, og meld av hvis gitt lokalmønstring
	 *
	 * @param write_tittel $tittel
	 * @param write_monstring $monstring
	 *
	 * @return (bool true|throw exception)
	 */
	public function fjern( $tittel, $monstring ) {
		$this->_validateInput( $tittel, $monstring );

		if( $monstring->getType() == 'kommune' ) {
			$res = $this->_fjernLokalt( $tittel, $monstring );
		} else {
			$res = $this->_fjernVideresend( $tittel, $monstring );
		}
		
		if( $res ) {
			return true;
		}
		
		throw new Exception('PERSONER_COLLECTION: Kunne ikke fjerne '. $tittel->getTittel() .' fra innslaget');
	}
	
	/**
	 * Legger til en tittel i innslaget, og videresender til gitt mønstring
	 *
	 * @param write_tittel $tittel
	 * @param write_monstring $monstring
	 *
	 * @return (bool true|throw exception)
	 */
	public function leggTil( $tittel, $monstring ) {
		// En tittel er alltid lagt til lokalt (pga databaserelasjonen)
		// Videresend tittelen hvis ikke lokalmønstring
		if( $monstring->getType() == 'kommune' ) {
			return true;
		}

		$this->_validateInput( $tittel, $monstring );

		$res = $this->_leggTilVideresend( $tittel, $monstring );
		if( $res ) {
			return $this;
		}
		
		$this->_load();

		throw new Exception('TITLER: Kunne ikke legge til '. $tittel->getTittel() .' i innslaget');
	}



	/********************************************************************************
	 *
	 *
	 * SETTERS AND GETTERS
	 *
	 *
	 ********************************************************************************/

	public function setVarighet( $seconds ) {
		$this->varighet = $seconds;
		return $this;
	}
	public function getVarighet() {
		return new tid( $this->varighet );
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
	 * @param write_tittel $tittel
	 * @param write_monstring $monstring
	 * @return void
	**/
	private function _validateInput( $person, $monstring ) {
		// Tittelen
		if( 'write_tittel' != get_class($tittel) ) {
			throw new Exception("TITLER_COLLECTION: Fjerning av tittel krever skriverettigheter til tittelen!");
		}
		if( !is_numeric( $tittel->getId() ) ) {
			throw new Exception("TITLER_COLLECTION: Fjerning av en tittel krever at tittelen har en numerisk ID!");
		}
		
		// Innslaget
		if( null == $this->_getInnslagId() || empty( $this->_getInnslagId() ) ) {
			throw new Exception('TITLER_COLLECTION: Kan ikke legge til/fjerne tittel når innslag-ID er tom');
		}
		if( !is_numeric( $this->_getInnslagId() ) ) {
			throw new Exception('TITLER_COLLECTION: Kan ikke legge til/fjerne tittel i innslag med ikke-numerisk ID');
		}
		
		// Mønstringen
		if( 'write_monstring' != get_class( $monstring ) ) {
			throw new Exception("TITLER_COLLECTION: Kan ikke legge til/fjerne tittel uten skriverettigheter til mønstringen.");
		}
		if( !is_numeric( $monstring->getId() ) ) {
			throw new Exception("TITLER_COLLECTION: Kan ikke legge til/fjerne tittel når mønstringen ikke har en numerisk ID!");
		}
	}
	
	/**
	 * Fjern en tittel fra innslaget helt
	 *
	 * @param write_tittel $tittel
	 * @param write_monstring $monstring
	 * @return (bool true|throw exception)
	 **/
	private function _fjernLokalt( $tittel, $monstring ) {
		if( $monstring->getType() !== 'kommune' ) {
			throw new Exception('TITLER_COLLECTION: Trenger en lokalmønstring for å kunne slette tittelen!');
		}
		
		UKMlogger::log( 327, $this->_getInnslagId(), $tittel->getId() .': '. $tittel->getTittel() );
		$qry = new SQLdel( 
			$this->_getTable(), 
			[
				't_id' => $tittel->getId(),
				'b_id' => $this->_getInnslagId(),
			]
		);
		$res = $qry->run();

		if($res == 1) {
			return true;
		}

		throw new Exception("TITLER: Klarte ikke fjerne tittel " . $tittel->getTittel());
	}
	
	/**
	 * 
	 * Avrelaterer en tittel fra dette innslaget.
	 *
	 * @param write_tittel $tittel
	 * @param write_monstring $monstring
	 *
	 * @return (bool true|throw exception)
	 */
	public function _fjernVideresend( $tittel, $monstring ) {
		$videresend_tittel = new SQLdel(
			'smartukm_fylkestep', 
			[
				'pl_id' 	=> $monstring->getId(),
				'b_id' 		=> $this->_getInnslagId(),
				't_id' 		=> $tittel->getId()
			]
		);
		UKMlogger::log( 321, $this->_getInnslagId(), $tittel->getId().': '. $tittel->getTittel() .' => '. $monstring->getNavn() );

		$res = $videresend_tittel->run();
		
		if( $res ) {
			return true;
		}

		throw new Exception('TITLER_COLLECTION: Kunne ikke avmelde '. $tittel->getTittel() .' fra innslaget' );
 	}
 	
	/**
	 * Legg til en tittel på videresendt nivå
	 *
	 * @param write_tittel $tittel
	 * @param write_monstring $monstring
	**/
	private function _leggTilVideresend( $tittel, $monstring ) {

		$test_relasjon = new SQL(
			"SELECT * FROM `smartukm_fylkestep`
				WHERE `pl_id` = '#pl_id'
				AND `b_id` = '#b_id'
				AND `t_id` = '#t_id'",
			[
				'pl_id'		=> $monstring->getId(), 
		  		'b_id'		=> $this->_getInnslagId(), 
				't_id'		=> $tittel->getId(),
			]
		);
		$test_relasjon = $test_relasjon->run();
		
		// Hvis allerede videresendt, alt ok
		if( mysql_num_rows($test_relasjon) > 0 ) {
			return true;
		}
		// Videresend personen
		else {
			$videresend_tittel = new SQLins('smartukm_fylkestep');
			$videresend_tittel->add('pl_id', $monstring->getId() );
			$videresend_tittel->add('b_id', $this->_getInnslagId() );
			$videresend_tittel->add('t_id', $tittel->getId() );

			UKMlogger::log( 322, $this->_getInnslagId(), $tittel->getId().': '. $tittel->getTittel() .' => '. $monstring->getNavn() );
			$res = $videresend_tittel->run();
		
			if( $res ) {
				return true;
			}
		}

		throw new Exception('TITLER_COLLECTION: Kunne ikke videresende '. $tittel->getTittel() );
	}


	private function _load() {
		$this->titler = array();
		
		// Til og med 2013-sesongen brukte vi tabellen "landstep" for videresending til land
		if( 2014 > $this->_getMonstringSesong() && 'land' == $this->_getMonstringType() ) {
			$SQL = new SQL("SELECT `title`.*,
								   `videre`.`id` AS `videre_if_not_empty`
							FROM `#table` AS `title`
							LEFT JOIN `smartukm_landstep` AS `videre`
								ON(`videre`.`b_id` = `title`.`b_id` AND `videre`.`t_id` = `title`.`t_id`)
							WHERE `title`.`b_id` = '#b_id'
							GROUP BY `title`.`t_id`
							ORDER BY `title`.`#titlefield`",
						array('table' => $this->_getTable(),
							  'titlefield' => $this->_getTableFieldnameTitle(),
							  'b_id' => $this->_getInnslagId()
							)
						);
		} else {
			$SQL = new SQL("SELECT `title`.*,
								GROUP_CONCAT(`videre`.`pl_id`) AS `pl_ids`
							FROM `#table` AS `title`
							LEFT JOIN `smartukm_fylkestep` AS `videre`
								ON(`videre`.`b_id` = `title`.`b_id` AND `videre`.`t_id` = `title`.`t_id`)
							WHERE `title`.`b_id` = '#b_id'
							AND `title`.`t_id` > 0
							GROUP BY `title`.`t_id`
							ORDER BY `title`.`#titlefield`",
						array('table' => $this->_getTable(),
							  'titlefield' => $this->_getTableFieldnameTitle(),
							  'b_id' => $this->_getInnslagId()
							)
						);
			$res = $SQL->run();
		}
		
		if( $res ) {
			$varighet = 0;
			while( $row = mysql_fetch_assoc( $res ) ) {
				// Hvis innslaget er pre 2014 og på landsmønstring jukser vi
				// til at den har pl_ids for å få lik funksjonalitet videre
				if( isset( $row['videre_if_not_empty'] ) ) {
					if( is_numeric( $row['videre_if_not_empty'] ) ) {
						$row['pl_ids'] = $this->_getMonstringId();
					} else {
						$row['pl_ids'] = null;
					}
				}
				// Legg til tittel i array
				$tittel = new tittel_v2( $row, $this->_getInnslagType()->getTabell() );
				$this->titler[] = $tittel;
				
				$varighet += $tittel->getVarighetSomSekunder();
			}
			$this->setVarighet( $varighet );
		}
		return $this->titler;
	}

		
	/**
	 * Sett ID
	 *
	 * @param integer id 
	 *
	 * @return $this
	**/
	public function _setInnslagId( $id ) {
		$this->innslag_id = $id;
		return $this;
	}
	/**
	 * Hent ID
	 * @return integer $id
	**/
	public function _getInnslagId() {
		return $this->innslag_id;
	}
	
	/**
	 * Sett type
	 * Hvilken kategori faller innslaget inn under?
	 *
	 * @param integer $type
	 * @param string $kategori
	 *
	 * @return $this;
	**/
	public function _setInnslagType( $type ) {
		$this->innslag_type = $type; 

		// Sett hvilken tabell som skal brukes
		$this->_setTable( $this->_getInnslagType()->getTabell() );
		return $this;
	}
	/**
	 * Hent type
	 * Hvilken kategori innslaget faller inn under
	 *
	 * @return innslag_type $type
	**/
	private function _getInnslagType( ) {
		return $this->innslag_type;
	}
	
	/**
	 * Sett mønstringsid (PLID)
	 *
	 * @param string $type
	 * @return $this
	**/
	private function _setMonstringId( $pl_id ) {
		$this->monstring_id = $pl_id;
		return $this;
	}
	/**
	 * Hent mønstringsid (PLID)
	 *
	 * @return $this
	**/
	private function _getMonstringId() {
		return $this->monstring_id;
	}
	
	/**
	 * Sett mønstringstype
	 *
	 * @param string $type
	 * @return $this
	**/
	private function _setMonstringType( $type ) {
		$this->monstring_type = $type;
		return $this;
	}
	/**
	 * Hent mønstringstype
	 *
	 * @return string $type
	**/
	private function _getMonstringType() {
		return $this->monstring_type;
	}
	
	/**
	 * Sett sesong
	 *
	 * @param int $seson
	 * @return $this
	**/
	private function _setMonstringSesong( $sesong ) {
		$this->monstring_sesong = $sesong;
		return $this;
	}
	/**
	 * Hent sesong
	 *
	 * @return int $sesong
	**/
	private function _getMonstringSesong() {
		return $this->monstring_sesong;
	}	
	/**
	 * Sett tabellnavn
	 *
	 * @param $table
	 * @return $this
	**/
	private function _setTable( $table ) {
		$this->table = $table;
		
		// Sett navn på tittelfeltet
		switch( $table ) {
			case 'smartukm_titles_exhibition':
				$fieldname_title = 't_e_title';
				break;
			case 'smartukm_titles_other':
				$fieldname_title = 't_o_function';
				break;
			case 'smartukm_titles_scene':
				$fieldname_title = 't_name';
				break;
			case 'smartukm_titles_video':
				$fieldname_title = 't_v_title';
				break;
			default:
				throw new Exception('TITLER: Tittel-type ('.$type .') ikke støttet');
		}
		$this->_setTableFieldnameTitle( $fieldname_title );

		return $this;
	}

	/**
	 * Hent tabellnavn
	 *
	 * @return string $tabellnavn
	**/
	private function _getTable() {
		return $this->table;
	}
	
	/**
	 * Sett navn på tittelfelt
	 *
	 * @param $tittelfelt
	 * @return $this
	**/
	private function _setTableFieldnameTitle( $tittelfelt ) {
		$this->table_field_title = $tittelfelt;
		return $this;
	}
	/**
	 * Hent navn på tittelfelt
	 *
	 * @return string $tittelfelt
	**/
	private function _getTableFieldnameTitle() {
		return $this->table_field_title;
	}

}