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
require_once('UKM/logger.class.php');
require_once('UKM/innslag.class.php');
// For valideringen.
require_once('UKM/advarsel.class.php');

class innslag_writeable extends innslag {
	var $changes = array();
	
	public function __construct( $b_id_or_row ) {
		parent::__construct( $b_id_or_row );
		$this->_resetChanges();
	}

	public function save() {
		if( !UKMlogger::ready() ) {
			throw('Missing logger or bug');
		}
		$smartukm_band = new SQLins();
		$smartukm_tech = new SQLins();
		
		foreach( $this->getChanges() as $change ) {
			$qry = $smartukm_band;
			switch( $change ) {
				case 'navn':
					$field = 'b_name';
					$action = 987;
					$value = $this->getNavn();
					break;
				case 'technical':
					$qry = $smartukm_tech;
					$action = 998;
					$field = 'td_demand';
					$value = $this->getTekniskeBehov();
					break;
			}
			$$qry->add($field, $value);
			UKMlogger::log( $action, $value );
		}
		$smartukm_band->run();
		$smartukm_tech->run();
	}
	
	public function setNavn( $navn ) {
		$this->change('navn');
		parent::setNavn( $navn );
	}
	
	private function _resetChanges() {
		$this->changes = [];
	}
	
	private function _change( $key ) {
		$this->changes[ $key ] = true;
	}
	
}