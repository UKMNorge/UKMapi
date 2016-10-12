<?php

require_once('UKM/person.class.php');

class write_person extends person_v2 {
	var $changes = array();
	var $loaded = false;

	public function __construct( $b_id_or_row ) {
		parent::__construct($b_id_or_row);
		$this->_setLoaded();
	}

	### STATIC FUNCTIONS

	/**
	 * fodselsdatoFraAlder
	 * @param integer $alder
	 * @return integer unix timestamp.
	 */
	public static function fodselsdatoFraAlder($alder) {
		return mktime(0,0,0,0,1,1, (int)date("Y") - (int)$DATA['alder']);
	}

	/**
	 * create()
	 *
	 * Oppretter et nytt personobjekt og sender det til databasen.
	 *
	 * @param
	 * @return write_person
	 */
	public static function create($fornavn, $etternavn, $mobil, $fodselsdato, $kommune_id) {
		require_once('UKM/kommune.class.php');

		if(!is_string($fornavn) || empty($fornavn) || !is_string($etternavn) || empty($etternavn) ) {
			throw new Exception("PERSON_V2: Fornavn og etternavn må være en streng.");
		}
		if( !is_numeric($mobil) || 8 != strlen($mobil) ) {
			throw new Exception("PERSON_V2: Mobilnummeret må bestå kun av tall og være 8 siffer langt!");
		}
		if( !is_numeric($fodselsdato) ) {
			throw new Exception("PERSON_V2: Fødselsdatoen må være et Unix Timestamp.");
		}
		if( !is_numeric($kommune_id) ) {
			throw new Exception("PERSON_V2: Kommune-ID må være et tall.");
		}

		$kommune = new kommune($kommune_id);
		// Har vi denne personen?
		$p_id = self::finnEksisterendePerson($fornavn, $etternavn, $mobil);
		if(false == $p_id) {
			$sql = new SQLins("smartukm_participant");
			$sql->add('p_firstname', $fornavn);
			$sql->add('p_lastname', $etternavn);
			$sql->add('p_phone', $mobil);
			$sql->add('p_kommune', $kommune->getId());
			#echo $sql->debug();
			$res = $sql->run(); 

			if(false == $res) {
				throw new Exception("PERSON_V2: Klarte ikke å opprette et personobjekt for ".$fornavn." ". $etternavn.".");
			}
			$p_id = $sql->insid();
		}
		
		return new write_person((int)$p_id);
	}

	public static function finnEksisterendePerson($firstname, $lastname, $phone) {
		$qry = new SQL("SELECT `p_id` FROM `smartukm_participant` 
						WHERE `p_firstname`='#firstname' 
						AND `p_lastname`='#lastname' 
						AND `p_phone`='#phone'", 
						array('firstname'=>$firstname, 
							  'lastname'=>$lastname, 
							  'phone'=>(int)$phone));
		$p_id = $qry->run('field', 'p_id');

		return $p_id;
	}

	### END OF STATIC FUNCTIONS


	public function save() {
		if( !UKMlogger::ready() ) {
			throw new Exception('Logger is missing or incorrect set up.');
		}
		$smartukm_participant = new SQLins('smartukm_participant', array('p_id'=>$this->getId()));
		
		foreach( $this->getChanges() as $change ) {	
			$tabell = $change['tabell'];	#smartukm_band
			$qry 	= $$tabell;				#$smartukm_band = SQLins
			$qry->add( $change['felt'], $change['value'] );
		
			UKMlogger::log( $change['action'], $this->getId(), $change['value'] );
		}

		if( $smartukm_participant->hasChanges() ) {
			#echo $qry->debug();
			$smartukm_participant->run();
		}
	}

	public function setFornavn($fornavn) {
		if($this->_loaded() && $this->getFornavn() == $fornavn) {
			return false;
		}
		parent::setFornavn($fornavn);
		$this->_change('smartukm_participant', 'p_firstname', 401, $fornavn );
		return $this;
	}

	public function setEtternavn($etternavn) {
		if($this->_loaded() && $this->getEtternavn() == $etternavn) {
			return false;
		}
		parent::setEtternavn($etternavn);
		$this->_change('smartukm_participant', 'p_lastname', 402, $etternavn );
		return $this;
	}

	public function setMobil($mobil) {
		if($this->_loaded() && $this->getMobil() == $mobil) {
			return false;
		}

		parent::setMobil($mobil);
		$this->_change('smartukm_participant', 'p_phone', 405, $mobil);
		return $this;
	}

	public function setEpost($epost) {
		if($this->_loaded() && $this->getEpost() == $epost) {
			return false;
		}
		parent::setEpost($epost);
		$this->_change('smartukm_participant', 'p_email', 404, $epost);
		return $this;
	}

	public function setFodselsdato( $fodselsdato ) {
		if($this->_loaded() && $this->getFodselsdato() == $fodselsdato ) {
			return false;
		}
		parent::setFodselsdato($fodselsdato);
		$this->_change('smartukm_participant', 'p_dob', 403, $fodselsdato);
		return $this;
	}

	public function setKommune( $kommune ) {
		if($this->_loaded() && $this->getKommune() == $kommune ) {
			return false;
		}
		parent::setKommune($kommune);
		$this->_change('smartukm_participant', 'p_kommune', 406, $kommune);
		return $this;
	}

	### STANDARDFUNKSJONER FOR LAGRING
	private function _loaded() {
		return $this->loaded;
	}

	private function _setLoaded() {
		$this->loaded = true;
		$this->_resetChanges();
		return $this;
	}

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
}