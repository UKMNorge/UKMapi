<?php
	
require_once('UKM/sql.class.php');
require_once('UKM/mediabruker.class.php');
	
class mediabrukere_collection {

	private $mediabrukere = null;
	
	public function getAll() {
		if( null == $this->mediabrukere ) {
			$this->_load();
		}
		return $this->mediabrukere;
	}
	
	private function _load() {
		$sql = new SQL( mediabruker::loadQuery() );
		$res = $sql->run();
		
		while( $row = mysql_fetch_assoc( $res ) ) {
			$this->mediabrukere[] = new mediabruker( $row );
		}
	}
}