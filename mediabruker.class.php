<?php
require_once('UKM/sql.class.php');	

class write_mediabruker extends mediabruker {
	public static function create( $delta_user_id, $wp_user_id, $region ) {
		$SQL = new SQLins('ukm_mediabruker');
		$SQL->add('delta_user_id', $delta_user_id);
		$SQL->add('wp_user_id', $wp_user_id);
		$SQL->add('region', $region);
		$res = $SQL->run();

		$insid = $SQL->insid();
		
		return new write_mediabruker( $insid );
	}
}

class mediabruker {	
	private $region = null;
	private $delta_user_id = null;
	private $wp_user_id = null;
	private $registrert = null;
	
	public static function loadQuery() {
		return 'SELECT *
				FROM `ukm_mediabruker`';
	}
	
	public function __construct( $row_or_id ) {
		if( is_numeric( $row_or_id ) ) {
			$SQL = new SQL( mediabruker::loadQuery() 
						. "WHERE `id` = '#id'",
						array('id' => $row_or_id ));
			$row_or_id = $SQL->run('array');
		}
		$this->_loadByRow( $row_or_id );
	}
	
	private function _loadByRow( $row ) {
		if( !is_array( $row ) ) {
			throw new Exception('MEDIABRUKER: _loadByRow krever associative array som input');
		}

		$this->region = $row['region'];
		$this->delta_user_id = $row['delta_user_id'];
		$this->wp_user_id = $row['wp_user_id'];
		$this->registrert = $row['registrert'];
	}
	
	public function getRegion() {
		return $this->region;
	}
	
	public function getDeltaId() {
		return $this->delta_user_id;
	}
	
	public function getWordpressId() {
		return $this->wp_user_id;
	}
	
	public function getRegistrert() {
		return $this->registrert;
	}
}