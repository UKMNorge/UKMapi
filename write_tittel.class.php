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
		parent::__construct( $b_id_or_row, $table );
		$this->_setLoaded();
	}

	/**
 	 * Oppretter en ny tittel.
 	 * @param string $table - Tabellnavnet vi skal bruke.
 	 * @param int $b_id - Innslags-id som tittelen skal tilhøre.
	 *
 	 * @return false or integer (insert-ID).
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
			case 'smartukm_titles_video':
				$qry = new SQLins('smartukm_titles_video');
				$action = 510; 
				break;
			case 'smartukm_titles_exhibition':
				$qry = new SQLins('smartukm_titles_exhibition');
				$action = 514;
				break;
			default:
				throw new Exception('WRITE_TITTEL: Kan kun opprette en ny tittel for scene, video eller utstilling. '.$table.' er ikke støttet enda.');
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

		if( 'smartukm_titles_scene' == $this->getTable() ) {
			$this->_change($this->tabell, 't_name', 502, $tittel);	
		} 
		elseif( 'smartukm_titles_video' == $this->getTable() ) {
			$this->_change($this->tabell, 't_v_title', 511, $tittel);	
		} 
		elseif( 'smartukm_titles_exhibition' == $this->getTable() ) {
			$this->_change($this->tabell, 't_e_title', 515, $tittel);
		}

		parent::setTittel($tittel);
		return true;
	}

	public function setVarighet( $varighet) {
		if( $this->_loaded() && $this->getVarighet() == $varighet ) {
			return false;
		}

		if( $this->getTable() == 'smartukm_titles_video' ) {
			$this->_change($this->tabell, 't_v_time', 512, $varighet);
		}
		elseif( $this->getTable() == 'smartukm_titles_scene' ) {
			$this->_change($this->tabell, 't_time', 503, $varighet);
		}

		parent::setVarighet( $varighet );
		return true;
	}

	public function setFormat( $format ) {
		if( $this->_loaded() && $this->getFormat() == $format ) {
			return false;
		}

		$this->_change($this->tabell, 't_v_format', 513, $format);
		parent::setFormat( $format );
		return true;
	}

	public function setType( $type ) {
		if( $this->_loaded() && $this->getType() == $type ) {
			return false;
		}

		$this->_change($this->tabell, 't_e_type', 516, $type);
		parent::setType( $type );
		return true;
	}

	public function setBeskrivelse( $beskrivelse ) {
		if( $this->_loaded() && $this->getBeskrivelse() == $beskrivelse ) {
			return false;
		}

		$this->_change($this->tabell, 't_e_comments', 517, $beskrivelse);
		parent::setBeskrivelse( $beskrivelse );
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

		$this->_change($this->tabell, 't_musicby', 507, $melodi_av);
		parent::setMelodiAv( $selvlaget );
		return true;
	}

	public function setKoreografiAv( $koreografi_av ) {
		if( $this->_loaded() && $this->getKoreografiAv() == $koreografi_av ) {
			return false;
		}

		$this->_change($this->tabell, 't_coreography', 508, $koreografi_av);
		parent::setKoreografiAv( $koreografi_av );
		return true;
	}

	public function setLitteraturLesOpp( $lese_opp ) {
		if( $this->_loaded() && $this->getLitteraturLesOpp() == $lese_opp ) {
			return false;
		}

		$this->_change($this->tabell, 't_litterature_read', 509, $lese_opp);
		parent::setLitteraturLesOpp( $lese_opp );
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