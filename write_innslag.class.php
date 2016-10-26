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
		
		return $band->insid();
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
	
}