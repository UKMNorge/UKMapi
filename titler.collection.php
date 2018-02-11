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
		if( is_object( $id ) && get_class( $id ) == 'tittel_v2' ) {
			$id = $id->getId();
		}
		foreach( $this->getAll() as $tittel ) {
			if( $tittel->getId() == $id ) {
				return $tittel;
			}
		}
		// Ingen grunn til å ikke la folk finne tittelen de leter etter
		// når de har ID (så fremt den tilhører innslaget da)
		foreach( $this->getAllIkkeVideresendt() as $tittel ) {
			if( $tittel->getId() == $id ) {
				return $tittel;
			}
		}
		throw new Exception('TITLER: Kunne ikke finne tittel '. $id .' i innslag '. $this->getInnslagId(), 2); // OBS: code brukes av har()
	}

	/**
	 * Er tittelen med i innslaget. OBS: Tar ikke høyde for videresending!
	 *
	 * @param object person
	 * @return boolean
	**/
	public function har( $har_tittel ) {
		try {
			$this->get( $har_tittel );
			return true;
		} catch( Exception $e ) {
			if( $e->getCode() == 2 ) {
				return false;
			}
			throw $e;
		}
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
	 * MODIFISER COLLECTIONS
	 *
	 *
	 ********************************************************************************/
	public function leggTil( $tittel ) {
		try {
			write_tittel::validerTittel( $tittel );
		} catch( Exception $e ) {
			throw new Exception(
				'Kunne ikke legge til tittel. '. $e->getMessage(),
				10801
			);
		}
		
		// Hvis tittelen allerede er lagt til kan vi skippe resten
		if( $this->har( $tittel ) ) {
			return true;
		}
		
		// Gi tittelen riktig context (hent fra collection, samme som new tittel herfra)
		$tittel->setContext( $this->getContextInnslag() );
		
		// Legg til at tittelen skal være videresendt
		if( $tittel->getContext()->getMonstring()->getType() != 'kommune' ) {
			$status_videresendt = $tittel->getVideresendtTil(); // henter et array av mønstringID'er tittelen er videresendt til
			$status_videresendt[] = $tittel->getContext()->getMonstring()->getid(); // legg til denne mønstringen
			$tittel->setVideresendtTil( $status_videresendt ); // "lagre"
		}
		
		// Legg til tittelen i collection
		$this->titler_videresendt[] = $tittel;

		return true;
	}

	public function fjern( $tittel ) {
		try {
			write_tittel::validerTittel( $tittel );
		} catch( Exception $e ) {
			throw new Exception(
				'Kunne ikke fjerne tittel. '. $e->getMessage(),
				10801
			);
		}

		// Hvis tittelen ikke er her, så slipper vi å fjerne den
		if( !$this->har( $tittel ) ) {
			return true;
		}
	
		foreach( $this->titler_videresendt as $pos => $tittel_search ) {
			if( $tittel->getId() == $tittel_search->getId() ) {
				unset( $this->titler_videresendt[ $pos ] );
			}
		}
	
		return true;
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
				$tittel->setContext( $this->getContextInnslag() );

				
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