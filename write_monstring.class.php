<?php
/** LOG ACTIONS
INSERT INTO `log_actions` (`log_action_id`, `log_action_verb`, `log_action_element`, `log_action_datatype`, `log_action_identifier`, `log_action_printobject`)
VALUES
	(110, 'endret', 'lenke', 'text', 'smartukm_place|pl_link', 1),
	(111, 'lagt til', 'kontaktperson', 'text', 'smartukm_rel_pl_ab|new', 1),
	(112, 'lagt til', 'kommune', 'text', 'smartukm_rel_pl_k|new', 1),
	(113, 'lagt til', 'skjema for videresending', 'text', 'smartukm_place|pl_form', 0),
	(114, 'fjernet', 'kommune', 'text', 'smartukm_rel_pl_k|delete', 1),
	(115, 'avlyste', 'mønstringen', 'text', 'smartukm_rel_pl|delete', 0),
*/
require_once('UKM/monstring.class.php');
require_once('UKM/logger.class.php');

class write_monstring extends monstring_v2 {
	var $changes = array();
	
	public static function create( $type, $sesong, $navn, $datoer, $geografi ) {
		// Oppdater loggeren til å bruke riktig PL_ID
		UKMlogger::setPlId( 0 );
		
		/**
		 *
		 * SJEKK INPUT-DATA
		 *
		**/
		if( !in_array( $type, array('kommune', 'fylke', 'land') ) ) {
			throw new Exception('Monstring_v2::create: Ukjent type mønstring "'. $type .'"');	
		}
		if( !is_int( $sesong ) ) {
			throw new Exception('Monstring_v2::create: Sesong må være integer');
		}
		if( !is_object( $datoer ) || get_class( $datoer ) !== 'stdClass' ) {
			throw new Exception('Monstring_v2::create: Datoer må være objekt stdClass');
		}
		if( !isset( $datoer->frist ) ) {
			throw new Exception('Monstring_v2::create: Datoobjektet må ha frist');
		}
		if( get_class( $datoer->frist ) !== 'DateTime' ) {
			throw new Exception(
						'Monstring_v2::create: Datoer->frist må være DateTime, ikke '. 
						(is_object( $datoer->frist ) ? get_class( $datoer->frist ) : 
							is_array( $datoer->frist ) ? 'array' : 
								is_integer( $datoer->frist ) ? 'integer' :
									is_string( $datoer->frist ) ? 'string' : 'ukjent datatype'
						)
					);
		}
		switch( $type ) {
			case 'kommune':
				if( !is_array( $geografi ) ) {
					throw new Exception(
								'Monstring_V2::create: Geografiobjekt må være array kommuner, '. 
									(is_object( $datoer->frist ) ? get_class( $datoer->frist ) : 
										is_array( $datoer->frist ) ? 'array' : 
											is_integer( $datoer->frist ) ? 'integer' :
												is_string( $datoer->frist ) ? 'string' : 'ukjent datatype'
									)
							);
				}
				foreach( $geografi as $kommune ) {
					if( !is_object( $kommune ) || get_class( $kommune ) !== 'kommune' ) {
						throw new Exception('Monstring_v2::create: Alle Geografi->kommuneobjekt må være av typen UKMApi::kommune');
					}
				}
				break;
			case 'fylke':
				if( !is_object( $geografi ) || get_class( $geografi ) !== 'fylke' ) {
					throw new Exception('Monstring_v2::create: Geografiobjekt må være av typen UKMApi::fylke');
				}
				break;
			case 'land':
				break;
		}

		/**
		 *
		 * SETT INN RAD I smartukm_place
		 *
		**/		
		$place = new SQLins('smartukm_place');
		$place->add('pl_start', 0);
		$place->add('pl_stop', 0);
		$place->add('pl_public', 0);
		$place->add('pl_missing', 0);
		$place->add('pl_contact', 0);
		$place->add('pl_form', 0);
		
		switch( $type ) {
			case 'kommune':
				$place->add('pl_fylke', 0);
				$place->add('pl_kommune', time());
				$place->add('pl_deadline', $datoer->frist->getTimestamp());
				$place->add('pl_deadline2', $datoer->frist->getTimestamp());
				break;
			case 'fylke':
				$place->add('pl_fylke', $geografi->getId());
				$place->add('pl_kommune', 0);
				$place->add('pl_deadline', $datoer->frist->getTimestamp());
				$place->add('pl_deadline2', $datoer->frist->getTimestamp());
				break;
			case 'land':
				$place->add('pl_fylke', 123456789);
				$place->add('pl_kommune', 123456789);
				$place->add('pl_deadline', $datoer->frist->getTimestamp());
				$place->add('pl_deadline2', $datoer->frist->getTimestamp());
				break;
		}

		$place->add('pl_name', $navn);
		$place->add('season', $sesong);
		
		$result = $place->run();
		$pl_id = $place->insid();
		
		$monstring = new write_monstring( $pl_id );

		// Oppdater loggeren til å bruke riktig PL_ID
		UKMlogger::setPlId( $monstring->getId() );
		
		foreach( $geografi as $kommune ) {
			$monstring->addKommune( $kommune );
		}
		
		$monstring->setPath( 
			self::generatePath(
				'kommune',
				$geografi,
				$sesong
			)
		);
		$monstring->save();
		
		return $monstring;
	}

	/**
	 * Opprett mønstringsobjekt
	 * Bruker parent::__construct
	**/
	public function __construct( $b_id_or_row ) {
		parent::__construct( $b_id_or_row, true );
		$this->_resetChanges();
	}
	

	/**
	 * setNavn
	 * 
	 * @param string $navn
	 * @return $this
	**/
	public function setNavn( $navn ) {
		if( $navn == $this->getNavn() ) {
			return $this;
		}
		parent::setNavn( $navn );
		$this->_change('smartukm_place', 'pl_name', 100, $navn);
		return $this;
	}

	/**
	 * setPath
	 * 
	 * @param string $path
	 * @return $this
	**/
	public function setPath( $path ) {
		// Vi kan gjette oss til fylke, men det må likevel lagres
		// i databasen. Lagrer derfor uansett hvis vi snakker om fylke
		if( $path == $this->getPath() && $this->getType() != 'fylke' ) {
			return $this;
		}
		$path = rtrim( trim($path, '/'), '/');
		parent::setPath( $path );
		$this->_change('smartukm_place', 'pl_link', 110, $path);
		return $this;
	}
	
	/**
	 * setSkjema
	 *
	 * @param skjema $skjema eller int $skjemaId
	 * @return $this
	**/
	public function setSkjema( $skjema ) {
		$skjema_id = is_int( $skjema ) ? $skjema : $skjema->getId();
		
		if( $this->getSkjema()->getId() == $skjema_id ) {
			return $this;
		}
		parent::setSkjema( $skjema );
		$this->_change('smartukm_place', 'pl_form', 113, $skjema_id);
		return $this;
	}
	
	/**
	 * addKontaktperson
	 * legger til kontaktperson til kontaktperson-collection
	 * krever kall til save for å lagre til database
	 *
	 * @param kontaktperson $kontakt
	 * @return $this
	**/
	public function addKontaktperson( $kontakt ) {
		if( parent::getKontaktpersoner()->har( $kontakt ) ) {
			return $this;
		}

		$this->_checkSaveRequirements();
		parent::getKontaktpersoner()->add( $kontakt );
		
		// AUTO-lagre
		$rel_pl_ab = new SQLins('smartukm_rel_pl_ab');
		$rel_pl_ab->add('pl_id', $this->getId());
		$rel_pl_ab->add('ab_id', $kontakt->getId());
		$rel_pl_ab->add('order', time());
		#echo $rel_pl_ab->debug();
		$rel_pl_ab->run();
		
		UKMlogger::log( 111, 
						$this->getId(), 
						$kontakt->getId() .': '. $kontakt->getNavn()
					);

		return $this;
	}
	
	/**
	 * addKommune
	 * Legger til kommune til lokalmønstringen
 	 * krever kall til save for å lagre til database
 	 *
 	 * @param kommune $kommune
 	 * @return $this
 	**/
 	public function leggTilKommune( $kommune ) {
	 	return $this->addKommune( $kommune );
 	}
	public function addKommune( $kommune ) {
		if( $this->getType() != 'kommune' ) {
			throw new Exception('Mønstring: kan ikke legge til kommune på andre enn kommune(/lokal)-mønstringer)');
		}
		if( parent::getKommuner()->har( $kommune ) ) {
			return $this;
		}
		
		$this->_checkSaveRequirements();
		parent::getKommuner()->add( $kommune );
		
		$rel_pl_k = new SQLins('smartukm_rel_pl_k');
		$rel_pl_k->add('pl_id', $this->getId());
		$rel_pl_k->add('season', $this->getSesong());
		$rel_pl_k->add('k_id', $kommune->getId());
		#echo $rel_pl_k->debug();
		$rel_pl_k->run();

		UKMlogger::log( 112, 
						$this->getId(), 
						$kommune->getId() .': '. $kommune->getNavn()
					);
		return $this;
	}

	/**
	 * avlys
	 * 
	 * !! OBS, OBS !!
	 * Skal denne kun benyttes fra UKM Norge-admin,
	 * da bloggen må endres for at alt skal fungere som ønsket.
	 * !! OBS, OBS !! 
	 *
	**/
	public function avlys() {
		if( $this->getType() != 'kommune' ) {
			throw new Exception('Mønstring: kun lokalmønstringer kan avlyses');
		}
		if( !$this->erSingelmonstring() ) {
			throw new Exception('Mønstring: kun enkeltmønstringer kan avlyses');
		}
		if( !is_numeric( $this->getId() ) ) {
			throw new Exception('Mønstring: kan ikke fjerne kommune da mønstring ikke har numerisk ID');
		}
		if( !is_numeric( $this->getSesong() ) ) {
			throw new Exception('Mønstring: kan ikke fjerne kommune da sesong ikke har numerisk verdi');
		}
		$this->_checkSaveRequirements();
		
		// Fjern kommune-relasjonen
		$this->fjernKommune( $this->getKommune() );
		
		// Fjern databasefelter som identifiserer mønstringen ("soft delete")
		$monstringsnavn = $this->getNavn();
		$this->setNavn( 'SLETTET: '. $this->getNavn() );
		$this->setPath( NULL );
		$this->save();
		
		UKMlogger::log(
			115,
			$this->getId(),
			$monstringsnavn
		);
		
		
		return $this;

	}
	
	/**
	 * fjernKommune
	 * Trekker en kommune ut av mønstringen, 
	 * uavhengig om mønstringen har en eller flere kommuner.
	 *
	 * @param kommune $kommune
	 * @return $this
	**/
	public function fjernKommune( $kommune ) {
		if( $this->getType() != 'kommune' ) {
			throw new Exception('Mønstring: kan ikke fjerne kommune fra annet enn lokalmønstringer!');
		}
		if( !is_numeric( $this->getId() ) ) {
			throw new Exception('Mønstring: kan ikke fjerne kommune da mønstring ikke har numerisk ID');
		}
		if( !is_numeric( $this->getSesong() ) ) {
			throw new Exception('Mønstring: kan ikke fjerne kommune da sesong ikke har numerisk verdi');
		}
		$this->_checkSaveRequirements();
		
		// Fjern fra databasen
		$rel_pl_k_del = new SQLdel(
			'smartukm_rel_pl_k', 
			array(
				'pl_id' => $this->getId(),
				'k_id'	=> $kommune->getId(),
				'season'=> $this->getSesong()
			)
		);
		//echo $rel_pl_k_del->debug();
		$rel_pl_k_del->run();
		
		// Logg
		UKMlogger::log(
			114,
			$this->getId(),
			$kommune->getId() .': '. $kommune->getNavn()
		);
		
		// Fjern kommune-id fra mønstringsobjektet (bruker dette for å laste kommuner)
		if (($key = array_search($kommune->getId(), $this->kommuner_id)) !== false) {
			unset($this->kommuner_id[$key]);
		}
		parent::_resetKommuner();
		
		return $this;
	}

	/**
	 * save
	 * Lagrer alle registrerte endringer
	 *
	 * @return $this
	**/
	public function save() {
		$this->_checkSaveRequirements();
		
		$smartukm_place = new SQLins('smartukm_place', array('pl_id' => $this->getId() ) );
		
		foreach( $this->_getChanges() as $change ) {
			$tabell = $change['tabell'];
			$$tabell->add( $change['felt'], $change['value'] );
			UKMlogger::log( $change['action'], $this->getId(), $change['value'] );
		}

		if( $smartukm_place->hasChanges() ) {
			#echo $smartukm_place->debug();
			$smartukm_place->run();
		}
		return $this;
	}
	
	public static function generatePath( $type, $geografi, $sesong, $skipCheck=false ) {
		switch( $type ) {
			case 'kommune':
				// Legg til kommunerelasjoner og bygg link
				$kommuner = [];
				foreach( $geografi as $kommune ) {
					$kommuner[] = $kommune->getURLsafe();
				}
				sort( $kommuner );
				$link = implode('-', $kommuner );
				
				if( $skipCheck ) {
					return $link;
				}
				// Sjekk om linken er i bruk for gitt sesong
				$linkCheck = new SQL(
									"SELECT `pl_id`
									 FROM `smartukm_place`
									 WHERE `pl_link` = '#link'
									 AND `season` = '#season'",
									array(
										'link'=> $link, 
										'season'=> $sesong,
										)
									);
				$linkExists = $linkCheck->run('field', 'pl_id');
				if( false !== $linkExists && is_numeric( $linkExists ) ) {
					$fylke = $kommune->getFylke(); // Bruker siste kommune fra foreach
					$link = $fylke->getURLsafe() .'-'. $link;
				}
				break;
			case 'fylke':
				$link = $geografi->getURLsafe();
				break;
			case 'land':
				$link = 'festivalen';
				break;
			case 'default':
				throw new Exception('WRITE_MONSTRING::createLink() kan ikke generer link for ukjent type mønstring!');
		}
		return $link;
	}

	/**
	 * INTERNE FUNKSJONER FOR LAGRING
	**/
	/**	
	 * _getChanges
	 * Henter alle endringer
	**/
	private function _getChanges() {
		return $this->changes;
	}
	
	/**
	 * _resetChanges
	 * Nullstiller info om hvilke endringer som er gjort
	 *
	**/
	private function _resetChanges() {
		$this->changes = [];
	}
	
	/**
	 * Lagre at en endring har skjedd
	**/
	private function _change( $tabell, $felt, $action, $value ) {
		$data = array(	'tabell'	=> $tabell,
						'felt'		=> $felt,
						'action'	=> $action,
						'value'		=> $value
					);
		$this->changes[ $tabell .'|'. $felt ] = $data;
	}
	
	/**
	 * Sjekk at alt er som det skal før vi kan starte lagring
	**/
	private function _checkSaveRequirements() {
		if( !UKMlogger::ready() ) {
			throw new Exception('Logger is missing or incorrect set up.');
		}
		
		if( !is_numeric( $this->getId() ) ) {
			throw new Exception('Monstring: Mangler mønstringsID for å kunne lagre');
		}
	}
}