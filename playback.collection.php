<?php
require_once('UKM/playback.class.php');

class playback_collection {
	var $id = null;
	var $filer = null;
	
	public function __construct( $b_id ) {
		$this->setId( $b_id );
	}	

	public function setId( $id ) {
		$this->id = $id;
		return $this;
	}
	public function getId() {
		return $this->id;
	}
		
	public function getAll() {
		if( null == $this->filer ) {
			$this->_load();
		}
		return $this->filer;
	}
	
	public function getAntall() {
		return sizeof( $this->getAll() );
	}
	
	public function harPlayback() {		
		return 0 < $this->getAntall();
	}
	
	private function _load() {
		$this->filer = array();
		$sql = new SQL("SELECT `pb_id` 
						FROM `ukm_playback`
						WHERE `b_id` = '#bid'",
					   array('bid' => $this->getId())
					  );
		$res = $sql->run();

		if( $res ) {
			while( $r = mysql_fetch_assoc( $res ) ) {
				$this->filer[] = new playback( $r['pb_id'] );
			}
		}
	}
}