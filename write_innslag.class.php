<?php

/**
	HVORDAN CONSTRUCT OG SET FUNKER:
	- konstruktøren kjører parent::construct
	- alle settere er allerede overskrevet fra write-klassen
		- setter sjekker om objektet er lastet inn ($this->_loaded), noe det ikke er
		- logger derfor endring (change) og setter verdien via parent::setter
	- etter foreldre-konstruktøren er ferdig resetter vi changes
		changes brukes av save-funksjonen for å avgjøre hvilke verdier som er endret
	- alle settere sjekker om getteren gir samme verdi som setteren før den logger endring (change)
**/
	
require_once('UKM/innslag.class.php');

// For valideringen.
require_once('UKM/advarsel.class.php');

class write_innslag extends innslag_v2 {
	var $changes = array();
	var $loaded = false;
	
	public function __construct( $b_id_or_row ) {
		parent::__construct( $b_id_or_row, true );
		$this->_setLoaded();
	}

	public static function create( $kommune, $monstring, $type, $navn, $contact ) {
		if( !UKMlogger::ready() ) {
			throw new Exception('Logger is missing or incorrect set up.');
		}
		if( 'monstring_v2' != get_class($monstring) ) {
			throw new Exception("WRITE_INNSLAG: Krever mønstrings-objekt, ikke ".get_class($monstring)."." );
		}
		if( 'kommune' != get_class($kommune) ) {
			throw new Exception("WRITE_INNSLAG: Krever kommune-objekt, ikke ".get_class($kommune)."." );
		}
		if( 'innslag_type' != get_class($type) ) {
			throw new Exception("WRITE_INNSLAG: Krever at $type er av klassen innslag_type.");
		}
		if( 'write_person' != get_class($contact) ) {
			throw new Exception("WRITE_INNSLAG: Krever skrivbar person, ikke ".get_class($contact));	
		}
		if( empty($navn) ) {
			throw new Exception("WRITE_INNSLAG: Må ha innslagsnavn.");
		}

		if( !in_array($type->getKey(), array('scene', 'musikk', 'dans', 'teater', 'litteratur', 'film', 'video', 'utstilling', 'konferansier', 'nettredaksjon', 'arrangor') ) ) {
			throw new Exception("WRITE_INNSLAG: Kan kun opprette innslag for sceneinnslag, ikke ".$type->getKey().".");	
		}

		## CREATE INNSLAG-SQL
		$band = new SQLins('smartukm_band');
		$band->add('b_season', $monstring->getSesong() );
		$band->add('b_status', 8); ## Hvorfor får innslaget b_status 8 her???
		$band->add('b_name', $navn);
		$band->add('b_kommune', $kommune->getId());
		$band->add('b_year', date('Y'));
		$band->add('b_subscr_time', time());
		$band->add('bt_id', $type->getId() );
		$band->add('b_contact', $contact->getId() );

		if( 1 == $type->getId() ) {
			$band->add('b_kategori', $type->getKey() );
		}

		$bandres = $band->run();
		if( 1 != $bandres ) {
			throw new Exception("WRITE_INNSLAG: Klarte ikke å opprette et nytt innslag.");
		}

		$tech = new SQLins('smartukm_technical');
		$tech->add('b_id', $band->insid() );
		$tech->add('pl_id', $monstring->getId() );
		
		$techres = $tech->run();
		if( 1 != $techres ) {
			throw new Exception("WRITE_INNSLAG: Klarte ikke å opprette tekniske behov-rad i tabellen.");
		}		

		$rel = new SQLins('smartukm_rel_pl_b');
		$rel->add('pl_id', $monstring->getId() );
		$rel->add('b_id', $band->insid() );
		$rel->add('season', $monstring->getSesong() );
		
		$relres = $rel->run();
		if( 1 != $relres ) {
			throw new Exception("WRITE_INNSLAG: Klarte ikke å melde på det nye innslaget til mønstringen.");
		}

		// TODO: Oppdater statistikk
		#$innslag = new innslag( $b_id, false );
		#$innslag->statistikk_oppdater();
		return new write_innslag( (int)$band->insid() ); // Tror ikke cast er nødvendig, men det er gjort sånn i write_person.
	}	


	public function save() {
		if( !UKMlogger::ready() ) {
			throw new Exception('Logger is missing or incorrect set up.');
		}
		$smartukm_band = new SQLins('smartukm_band', array('b_id'=>$this->getId()));
		$smartukm_tech = new SQLins('smartukm_technical_demand', array('b_id'=>$this->getId()));
		$smartukm_rel_b_p = null;

		foreach( $this->getChanges() as $change ) {
			if( 411 == $change['action'] ) {
				$person = $change['value'];
				$change['value'] = $person->getRolle();
				$smartukm_rel_b_p = new SQLins('smartukm_rel_b_p', array('b_id' => $this->getId(), 'p_id' => $person->getId()));
				$tabell = 'smartukm_rel_b_p';
				$smartukm_rel_b_p->add('instrument_object', json_encode($person->getRolleObject()) );
			}
			
			$tabell = $change['tabell'];	#smartukm_band
			$qry 	= $$tabell;				#$smartukm_band = SQLins
			$qry->add( $change['felt'], $change['value'] );
		
			UKMlogger::log( $change['action'], $this->getId(), $change['value'] );
		}
		if( $smartukm_band->hasChanges() ) {
			#echo $qry->debug();
			$smartukm_band->run();
		}
		if( $smartukm_tech->hasChanges() ) {
			$smartukm_tech->run();
		}
		if( null != $smartukm_rel_b_p) {
			$smartukm_rel_b_p->run();
		}
	}

	private function _setLoaded() {
		$this->loaded = true;
		$this->_resetChanges();
		return $this;
	}
	private function _loaded() {
		return $this->loaded;
	}
	
	public function getChanges() {
		return $this->changes;
	}
	
	public function setNavn( $navn ) {
		if( $this->_loaded() && $this->getNavn() == $navn ) {
			return false;
		}
		parent::setNavn( $navn );
		$this->_change('smartukm_band', 'b_name', 301, $navn);
		return true;
	}	
	
	public function setSjanger( $sjanger ) {
		if( $this->_loaded() &&  $this->getSjanger() == $sjanger ) {
			return false;
		}
		$this->_change('smartukm_band', 'b_sjanger', 306, $sjanger);
		parent::setSjanger( $sjanger );
		return true;
	}	
	public function setBeskrivelse( $beskrivelse ) {
		if( $this->_loaded() &&  $this->getBeskrivelse() == $beskrivelse ) {
			return false;
		}
		$this->_change('smartukm_band', 'b_description', 309, $beskrivelse);
		parent::setBeskrivelse( $beskrivelse );
	}	
	public function setKommune( $kommune_id ) {
		if( $this->_loaded() &&  $this->getKommune()->getId() == $kommune_id ) {
			return false;
		}
		$this->_change('smartukm_band', 'b_kommune', 307, $kommune_id);
		parent::setKommune( $kommune_id );
	}	

	/**
	 * setKontaktperson
	 * @param write_person
	 * @return $this
	 */
	public function setKontaktperson( $person ) {
		if( 'write_person' != get_class($person) ) {
			throw new Exception("INNSLAG_V2: Krever skrivbart personobjekt for å endre kontaktperson.");
		}

		$this->_change('smartukm_band', 'b_contact', 302, $person->getId());
		parent::setKontaktperson($person);
		parent::setKontaktpersonId($person->getId());

		return $this;
	}

	/**
	 * setRolle på person.
	 *
	 * @param write_person
     * @param rolle string
     *
     * @return this
	 */
	public function setRolle( $person, $rolle ) {
		if( 'write_person' != get_class($person) ) {
			throw new Exception("INNSLAG_V2: setRolle krever skrivbart personobjekt (write_person).");
		}

		$person->setRolle($rolle);
		$this->_change('smartukm_rel_b_p', 'instrument', 411, $person);
		return $this;
	}
	
	private function _resetChanges() {
		$this->changes = [];
	}
	
	private function _change( $tabell, $felt, $action, $value ) {
		$data = array(	'tabell'	=> $tabell,
						'felt'		=> $felt,
						'action'	=> $action,
						'value'		=> $value
					);
		$this->changes[ $tabell .'|'. $felt ] = $data;
	}
	

	/**
	 * Denne funksjonen validerer innslaget, sjekker at det har all påkrevd informasjon,
	 * genererer evt. advarsler og oppgraderer b_status ut fra grad av fullførthet.
	 *
	 * Eventuelle feilmeldinger lagres i b_status_text som et serialisert array av objekter, 
	 * slik at de kan hentes ut uten å kjøre hele valideringsprosessen på nytt. 
	 *
	 * Funksjonen er entry-point for validering, og kaller underfunksjoner som returnerer 
	 * en advarsel (se advarsel.class.php), et array av advarsler eller null. 
	 *
	 * For å legge til en sjekk som returnerer advarsel eller null: $advarsler[] = $this->_valideringsTest();
	 * For å legge til en sjekk som returnerer et array med advarsler eller null : $advarsler += $this->valideringsTest();
	 * Dette kan gjøres sånn fordi vi tømmer arrayet for null-verdier før vi returnerer.
	 *
	 * Dette er den moderne utgaven av $innslag->validateBand2().
	 *
	 * @return array
	 *
	 */
	public function valider() {
		$advarsler = array();
		// Felles for alle:
		$advarsler[] = $this->_validerInnslagsNavn();
		$advarsler[] = $this->_validerBeskrivelse();
		$advarsler += $this->_validerKontaktperson();
		$advarsler += $this->_validerDeltakere();

		// Type-spesifikke sjekker:
		switch ( $this->getType()->getKey() ) {
			case 'musikk':
				$advarsler[] = $this->_validerSjanger();
				$advarsler[] = $this->_validerTekniskeBehov();
				break;
			case 'scene':
				break;
			default:
				throw new Exception("WRITE_INNSLAG: Kan ikke validere innslag av typen ".$this->getType()->getName() );
		}

		// Fjern null-verdier og verdier som er false fra arrayet.
		$advarsler = array_filter($advarsler);
		return $advarsler;
	}

	/**
	 * Alle innslag må ha et innslagsnavn.
	 * return advarsel or null
	 */
	private function _validerInnslagsNavn() {
		if( empty( $this->getNavn() ) ) {
			return advarsel::ny('innslag', 'Innslaget mangler navn', 'danger');
		}
		return null;
	}

	/**
	 * Har innslaget en OK beskrivelse?
	 */
	private function _validerBeskrivelse() {
		$advarsel = null;
		$beskrivelse = $this->getBeskrivelse();

		if( empty( $beskrivelse ) ) {
			$advarsel = advarsel::ny('innslag', 'Innslaget mangler beskrivelse', 'warning');
		} elseif ( $beskrivelse < 5 ) {
			$advarsel = advarsel::ny('innslag', 'Beskrivelsen til innslaget er under 5 tegn', 'warning');
		}

		return $advarsel;
	}

	/**
	 * Sjekk om innslaget har en sjanger
	 */
	private function _validerSjanger() {
		$advarsel = null;
		if( empty( $this->getSjanger() ) ) {
			return advarsel::ny('innslag', 'Innslaget mangler sjanger', 'warning');
		}
		return null;
	}

	/**
	 * Sjekk at vi har all påkrevd informasjon om kontaktpersonen til innslaget
	 * @return array
	 */
	private function _validerKontaktperson() {
		$advarsler = array();
		$kontaktperson = $this->getKontaktperson();

		if ( null == $kontaktperson ) {
			return array(advarsel::ny('innslag', 'Innslaget manger kontaktperson', 'danger') );
		}

		if( empty( $kontaktperson->getFirstname() ) ) {
			$advarsler[] = advarsel::ny('kontaktperson', 'Kontaktpersonen mangler fornavn', 'danger');
		}
		if( empty( $kontaktperson->getLastname() ) ) {
			$advarsler[] = advarsel::ny('kontaktperson', 'Kontaktpersonen mangler etternavn', 'danger');
		}

		if( empty( $kontaktperson->getEpost() ) ) {
			$advarsler[] = advarsel::ny('kontaktperson', 'Kontaktpersonen mangler e-post-adresse', 'danger');
		} else {
			$advarsler[] = $this->_validerEpost($kontaktperson->getEpost();
		}

		$mobil = $kontaktperson->getMobil();
		if ( null == $mobil || strlen( $mobil ) < 8 ) {
			$advarsler[] = advarsel::ny('kontaktperson', 'Kontaktpersonens mangler mobilnummer', 'danger');
		}
		if( '12345678' == $mobil ||
			'00000000' == $mobil ||
			'11111111' == $mobil ||
			'22222222' == $mobil ||
			'33333333' == $mobil ||
			'44444444' == $mobil ||
			'55555555' == $mobil ||
			'66666666' == $mobil ||
			'77777777' == $mobil ||
			'88888888' == $mobil ||
			'99999999' == $mobil ||
			'12341234' == $mobil ||
			'87654321' == $mobil ||
			'23456789' == $mobil ||
			'98765432' == $mobil ||
			) {
			$advarsler[] = advarsel::ny('kontaktperson', 'Kontaktpersonen har et ugyldig mobilnummer', 'danger');
		}

		if( empty( $kontaktperson->getAdresse() ) || $kontaktperson->getAdresse() < 3 ) {
			$advarsler[] = advarsel::ny('kontaktperson', 'Adressen til kontaktpersonen må være lenger enn 3 bokstaver', 'warning');
		}
		if( empty( $kontaktperson->getPostnummer() || strlen( $kontaktperson->getPostnummer() ) !== 4) ) {
			$advarsler[] = advarsel::ny('kontaktperson', 'Postnummeret til kontaktpersonen må være på fire siffer', 'warning');
		}

		return $advarsler;
	}

	/**
	 * Validerer en epostadresse. Returnerer et advarselobjekt hvis det er en feil, eller null hvis ingen feil.
	 * @param $email
	 * @return advarsel or null
	 */
	private function _validerEpost($email) {
		$isValid = true;
		$atIndex = strrpos($email, "@");
		if( is_bool($atIndex) && !$atIndex )
		{
			$isValid = false;
		}
		else
		{
			$domain = substr($email, $atIndex+1);
			$local = substr($email, 0, $atIndex);
			$localLen = strlen($local);
			$domainLen = strlen($domain);
			if( $localLen < 1 || $localLen > 64 ) {
				// local part length exceeded
				$isValid = false;
			}
			elseif( $domainLen < 1 || $domainLen > 255 ) {
				// domain part length exceeded
				$isValid = false;
			}
			elseif( $local[0] == '.' || $local[$localLen-1] == '.' ) {
				// local part starts or ends with '.'
				$isValid = false;
			}
			elseif( preg_match('/\\.\\./', $local) ) {
				// local part has two consecutive dots
				$isValid = false;
			}
			elseif( !preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain) ) {
				// character not valid in domain part
				$isValid = false;
			}
			elseif( preg_match('/\\.\\./', $domain) ) {
				// domain part has two consecutive dots
				$isValid = false;
			}
			elseif( !preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\","",$local)) ) {
				// character not valid in local part unless 
				// local part is quoted
				if( !preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\","",$local)) ) {
					$isValid = false;
				}
			}
	       	if( $isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A")) )
	      	{
	        	// domain not found in DNS
	        	$isValid = false;
	      	}
	   	}

		if( false == $isValid ) {
			return advarsel::ny('epost', 'E-postadressen er ikke en godkjent e-post-adresse', 'warning');
		}
		return null;
	}

	/**
	 * Validerer alle deltakere i innslaget. Returnerer et array med eventuelle advarsler.
	 *
	 * @return array
	 */
	private function _validerDeltakere() {
		$advarsler = array();
		$deltakere = $this->getPersoner()->getAll();

		if( count($deltakere) == 0 ) {
			$advarsler[] = advarsel::ny('innslag', 'Innslaget har ingen deltakere', 'danger');
			return $advarsler;
		}
		foreach( $deltakere as $deltaker ) {
			$w = $this->_validerEnkeltdeltaker($deltaker);
			$advarsler = $advarsler + $w;
		}
		return $advarsler;
	}

	/**
	 * Validerer en enkeltperson i innslaget. Skal kun brukes fra _validerDeltakere()
	 *
	 * @param person_v2
	 * @return array
	 */
	private function _validerEnkeltdeltaker($deltaker) {
		$advarsler = array();
		$whatmissing = array();

		if( empty( $deltaker->getFornavn() ) && strlen($deltaker->getFornavn()) < 3 ) {
			$advarsler[] = advarsel::ny('person', 'En deltaker mangler fornavn', 'warning');	
	    }
		if( empty( $deltaker->getEtternavn() ) && strlen( $deltaker->getEtternavn() < 3 ) {
			$advarsler[] = advarsel::ny('person', 'En deltaker mangler etternavn', 'warning');
		}
	    if( empty( $deltaker->getMobil() ) || strlen( $deltaker->getMobil() ) !== 8 ) {
			$advarsler[] = advarsel::ny('person', 'En deltaker mangler mobilnummer', 'danger');	
	    }
	    if( empty( $deltaker->getRolle() ) ) {
	    	$advarsler[] = advarsel::ny('person', 'En deltaker mangler rolle eller instrument', 'warning');
	    }
		
	    return $advarsler;
	}

	/**
	 * Valider tekniske behov, at de finnes og er lange nok.
	 * @return advarsel or null
	 */
	private function _validerTekniskeBehov() {
		$advarsel = null;
		$teknisk = $this->getTekniskeBehov();

		if( empty( $teknisk ) ) {
			$advarsel = advarsel::ny('innslag', 'Innslaget mangler tekniske behov', 'warning');
		} elseif ( $teknisk < 5 ) {
			$advarsel = advarsel::ny('teknisk', 'De tekniske behovene til innslaget er under 5 tegn', 'warning');
		}
		return $advarsel;
	}

	// TODO: Tittel-validering + 

}