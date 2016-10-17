<?php

/**
 *
 * write_tittel.class.php
 *
 *
 *
 *
 */

require_once('UKM/sql.class.php');
require_once('UKM/tittel.class.php');

class write_tittel extends tittel_v2 {
	var $changes = array();
	var $loaded = false;

	public function __construct( $b_id_or_row, $table) {
		## TODO: SJEKK AT VI HAR BEGGE ARGUMENTER
		parent::__construct( $b_id_or_row, $table );
		$this->_setLoaded();
	}

	/**
 	 *
 	 */
	public static function create( $table, $b_id ) {
		if( !UKMlogger::ready() ) {
			throw new Exception('Logger is missing or incorrectly set up');
		}

		switch( $table ) {
			case 'smartukm_titles_scene':
				$qry = new SQLins('smartukm_titles_scene');
				$action = 501;
			break;

			default:
				throw new Exception('WRITE_TITTEL: Kan kun opprette en ny tittel for scene. '.$table.' er ikke støttet enda.');
		}

		$qry->add('b_id', $b_id);
		$res = $qry->run();
		if( $res ) {
			// Logg oppretting av ny tittel for band $b_id med id insid();
			UKMlogger::log( $action, $b_id, $qry->insid() );
			return $qry->insid();
		}
		else {
			return false;
		}
	}

	public function save() {
		if( !UKMlogger::ready() ) {
			throw new Exception('Logger is missing or incorrectly set up.');
		}

		$qry = new SQLins($this->getTable(), array('t_id' => $this->getId()));

		foreach( $this->getChanges() as $change ) {
			$qry->add( $change['felt'], $change['value'] );
			UKMlogger::log( $change['action'], $this->getId(), $change['value'] );
		}

		if( $qry->hasChanges() ) {
			$qry->run();
		}

		return true;
	}

	public function setTittel( $tittel ) {
		if( $this->_loaded() && $this->getTittel() == $tittel ) {
			return false;
		}

		$this->_change($this->tabell, 't_name', 502, $tittel);
		parent::setTittel($tittel);
		return true;
	}

	public function setVarighet( $varighet) {
		if( $this->_loaded() && $this->getVarighet() == $varighet ) {
			return false;
		}

		$this->_change($this->tabell, 't_time', 503, $varighet);
		parent::setVarighet( $varighet );
		return true;
	}

	public function setInstrumental( $instrumental ) {
		if( $this->_loaded() && $this->erInstrumental() == $instrumental ) {
			return false;
		}

		$this->_change($this->tabell, 't_instrumental', 504, $instrumental);
		parent::setInstrumental( $instrumental );
		return true;
	}

	public function setSelvlaget( $selvlaget ) {
		if( $this->_loaded() && $this->erSelvlaget() == $selvlaget ) {
			return false;
		}

		$this->_change($this->tabell, 't_selfmade', 505, $selvlaget);
		parent::setSelvlaget( $selvlaget );
		return true;
	}

	public function setTekstAv( $tekstforfatter ) {
		if( $this->_loaded() && $this->getTekstAv() == $tekstforfatter ) {
			return false;
		}

		$this->_change($this->tabell, 't_titleby', 506, $tekstforfatter);
		parent::setTekstAv( $selvlaget );
		return true;
	}

	public function setMelodiAv( $melodi_av ) {
		if( $this->_loaded() && $this->getMelodiAv() == $melodi_av ) {
			return false;
		}

		$this->_change($this->tabell, 't_musicby', 506, $melodi_av);
		parent::setMelodiAv( $selvlaget );
		return true;
	}

	#### INTERNAL FUNCTIONS
	public function getChanges() {
		return $this->changes;
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
	
	private function _setLoaded() {
		$this->loaded = true;
		$this->_resetChanges();
		return $this;
	}

	private function _loaded() {
		return $this->loaded;
	}	
}