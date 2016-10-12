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

	public function save() {
		if( !UKMlogger::ready() ) {
			throw new Exception('Logger is missing or incorrect set up.');
		}
		$smartukm_band = new SQLins('smartukm_band', array('b_id'=>$this->getId()));
		$smartukm_tech = new SQLins('smartukm_technical_demand', array('b_id'=>$this->getId()));
		
		foreach( $this->getChanges() as $change ) {
			$tabell = $change['tabell'];	#smartukm_band
			$qry 	= $$tabell;				#$smartukm_band = SQLins
			UKMlogger::log( $change['action'], $this->getId(), $change['value'] );
			$qry->add( $change['felt'], $change['value'] );
		}
		if( $smartukm_band->hasChanges() ) {
			echo $qry->debug();
			$smartukm_band->run();
		}
		if( $smartukm_tech->hasChanges() ) {
			$smartukm_tech->run();
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