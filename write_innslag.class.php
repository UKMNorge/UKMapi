<?php

class UKMlogger {
	var $user_id = null;
	var $system_id = null;
	
	static function log( $action, $value ) {
		$sql = new SQLins('smartukm_log_log');
		$sql->add( self::getUser() );
		$sql->add( self::getSystem() );
		$sql->add( $action );
		$sql->add( $value );
		
		$sql->debug();
	}
	
	static function ready() {
		return false;
	}
	
	static function setUser( $user ) {
		self::$user = $user;
		return self;
	}
	static function getUser() {
		return self::$user;
	}
	
	static function setSystem( $system ) {
		self::$system = $system;
		return self;
	}
	static function getSystem() {
		return self::$system;
	}
	
	static function setID( $system, $user ) {
		self::setSystem( $system );
		self::setUser( $user );
		return self;
	}
	
}

UKMlogger::setID( 'wordpress', '123' );

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