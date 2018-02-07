<?php
require_once('UKM/tid.class.php');
	
class titler {
	var $context = null;
	var $innslag_id = null;
	var $innslag_type = null;

	var $titler = null;
	var $titler_videresendt = null;
	var $titler_ikke_videresendt = null;
	var $titler_alle = null;
	
	var $table = null;
	var $table_field_title = null;
	var $varighet = 0;
	var $varighet_ikke_videresendt = 0;
	
	var $monstring_type = null;
	var $monstring_sesong = null;
	var $monstring_id = null;

		
	public function __construct( $innslag_id, $innslag_type, $context ) {
		$this->_setInnslagId( $innslag_id );
		$this->_setInnslagType( $innslag_type );
		$this->_setContext( $context );

		// Sett hvilken tabell som skal brukes
		$this->_setTable( $this->getInnslagType()->getTabell() );
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
		throw new Exception('TITLER: Kunne ikke finne tittel '. $id .' i innslag '. $this->getInnslagId());
	}




	/********************************************************************************
	 *
	 *
	 * GET FILTERED SUBSETS FROM COLLECTION
	 *
	 *
	 ********************************************************************************/

	/**
	 * getAll(Videresendt)
	 * Hent alle titler i innslaget videresendt til samlingens mønstring
	 *
	 * @return bool
	**/
	public function getAll() {
		if( null == $this->titler_videresendt ) {
			$this->_load();
		}
		return $this->titler_videresendt;
	}

	/**
	 * getAllIkkeVideresendt
	 * Hent alle titler i innslaget som ikke er videresendt til samlingens mønstring
	 *
	 * @return bool
	**/
	public function getAllIkkeVideresendt() {
		if( null == $this->titler_ikke_videresendt ) {
			$this->_load();
		}
		return $this->titler_ikke_videresendt;
	}
	
	/**
	 * getAllIkkeVideresendt
	 * Hent alle titler i innslaget som ikke er videresendt til samlingens mønstring
	 *
	 * @return bool
	**/
	public function getAllInkludertIkkeVideresendt() {
		if( null == $this->titler_alle ) {
			$this->_load();
		}
		return $this->titler_alle;
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
	
	public function setVarighetIkkeVideresendt( $seconds ) {
		$this->varighet_ikke_videresendt = $seconds;
		return $this;
	}
	public function getVarighetIkkeVideresendt() {
		return new tid( $this->varighet_ikke_videresendt );
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
	private function _validateInput( $tittel, $monstring ) {
		// Tittelen
		if( 'write_tittel' != get_class($tittel) ) {
			throw new Exception("TITLER_COLLECTION: Å legge til eller fjerne en tittel krever skriverettigheter til tittelen!");
		}
		if( !is_numeric( $tittel->getId() ) ) {
			throw new Exception("TITLER_COLLECTION: Å legge til eller fjerne en tittel krever at tittelen har en numerisk ID!");
		}
		
		// Innslaget
		if( null == $this->getInnslagId() || empty( $this->getInnslagId() ) ) {
			throw new Exception('TITLER_COLLECTION: Kan ikke legge til/fjerne tittel når innslag-ID er tom');
		}
		if( !is_numeric( $this->getInnslagId() ) ) {
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
		
		UKMlogger::log( 327, $this->getInnslagId(), $tittel->getId() .': '. $tittel->getTittel() );
		$qry = new SQLdel( 
			$this->_getTable(), 
			[
				't_id' => $tittel->getId(),
				'b_id' => $this->getInnslagId(),
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
				'b_id' 		=> $this->getInnslagId(),
				't_id' 		=> $tittel->getId()
			]
		);
		UKMlogger::log( 321, $this->getInnslagId(), $tittel->getId().': '. $tittel->getTittel() .' => '. $monstring->getNavn() );

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
		  		'b_id'		=> $this->getInnslagId(), 
				't_id'		=> $tittel->getId(),
			]
		);
		$test_relasjon = $test_relasjon->run();
		
		// Hvis allerede videresendt, alt ok
		if( mysql_num_rows($test_relasjon) > 0 ) {
			return true;
		}
		// Videresend tittelen
		else {
			$videresend_tittel = new SQLins('smartukm_fylkestep');
			$videresend_tittel->add('pl_id', $monstring->getId() );
			$videresend_tittel->add('b_id', $this->getInnslagId() );
			$videresend_tittel->add('t_id', $tittel->getId() );

			UKMlogger::log( 322, $this->getInnslagId(), $tittel->getId().': '. $tittel->getTittel() .' => '. $monstring->getNavn() );
			$res = $videresend_tittel->run();
		
			if( $res ) {
				return true;
			}
		}

		throw new Exception('TITLER_COLLECTION: Kunne ikke videresende '. $tittel->getTittel() );
	}


	private function _load() {
		$this->titler_videresendt = array();
		$this->titler_ikke_videresendt = array();
		$this->titler_alle = array();
		
		$varighet_videresendt = 0;
		$varighet_ikke_videresendt = 0;

		
		// Til og med 2013-sesongen brukte vi tabellen "landstep" for videresending til land
		if( 2014 > $this->getContext()->getMonstring()->getSesong() && 'land' == $this->getContext()->getMonstring()->getType() ) {
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
							  'b_id' => $this->getInnslagId()
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
							  'b_id' => $this->getInnslagId()
							)
						);
			$res = $SQL->run();
		}
		
		if( $res ) {
			while( $row = mysql_fetch_assoc( $res ) ) {
				// Hvis innslaget er pre 2014 og på landsmønstring jukser vi
				// til at den har pl_ids for å få lik funksjonalitet videre
				if( isset( $row['videre_if_not_empty'] ) ) {
					if( is_numeric( $row['videre_if_not_empty'] ) ) {
						$row['pl_ids'] = $this->getContext()->getMonstring()->getId();
					} else {
						$row['pl_ids'] = null;
					}
				}
				// Legg til tittel i array
				$tittel = new tittel_v2( $row, $this->getInnslagType()->getTabell() );
				$context = context::createInnslag(
					$this->getInnslagId(),								// Innslag ID
					$this->getInnslagType(),							// Innslag type (objekt)
					$this->getContext()->getMonstring()->getId(),		// Mønstring ID
					$this->getContext()->getMonstring()->getType(),		// Mønstring type
					$this->getContext()->getMonstring()->getSesong()	// Mønstring sesong
				);
				$tittel->setContext( $context );

				
				if( $this->getContext()->getMonstring()->getType() == 'kommune' || $tittel->erVideresendt( $this->getContext()->getMonstring()->getId() ) ) {
					$this->titler_videresendt[] = $tittel;
					$varighet_videresendt += $tittel->getVarighetSomSekunder();
				} else {
					$this->titler_ikke_videresendt[] = $tittel;
					$varighet_ikke_videresendt += $tittel->getVarighetSomSekunder();
				}
				
				$this->titler_alle[] = $tittel;
				
			}
			$this->setVarighet( $varighet_videresendt );
			$this->setVarighetIkkeVideresendt( $varighet_ikke_videresendt );
		}
		return $this->titler_videresendt;
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
		throw new Exception('TITLER_COLLECTION: Innslag-type må være angitt som objekt');
	}

	private function _setContext( $context ) {
		$this->context = $context;
		return $this;
	}
	public function getContext() {
		return $this->context;
	}
}