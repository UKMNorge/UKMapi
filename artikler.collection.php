<?php
require_once('UKM/sql.class.php');
require_once('UKM/artikkel.class.php');

class artikler {
	
	static $table = 'ukmno_wp_related';
	var $b_id = false;
	var $artikler = null;

	public function __construct( $b_id ) {
		$this->b_id = $b_id;
	}	
	
	/**
	 * Alle artikler for gitt innslag
	 */
	public function getAll() {
		if( null == $this->artikler ) {
			$this->_load();
		}
		return $this->artikler;
	}
	
	public function getAntall() {
		return sizeof( $this->getAll() );
	}
	
	public function getAllFrom( $pl_type=false ) {
		if( false == $pl_type ) {
			return $this->getAll();
		}
		
		$type_sorted = array();
		foreach( $this->artikler as $artikkel ) {
			if( $artikkel->getMonstringType() == $pl_type ) {
				$type_sorted[] = $artikkel;
			}
		}
		return $type_sorted;
	}
	
	/**
	 * Finn siste artikkel
	 *
	**/
	public function getLast() {
		$artikler_copy = copy( $this->artikler );
		ksort( $artikler_copy );
		$last = end( $artikler_copy );
		unset( $artikler_copy );
		return $last;
	}
	
	private function _load() {
		$this->artikler = array();
		$SQL = new SQL("SELECT * FROM `#table`
						WHERE `#table`.`b_id` = '#bid'
						AND `post_type` = 'post'",
						array('table'=>self::$table,
							  'bid'=>$this->b_id)
						);
		$get = $SQL->run();
		if( !$get ) {
			return false;
		}		
		while( $r = mysql_fetch_assoc( $get ) ) {
			$artikkel = new artikkel( $r );
			$this->artikler[ $artikkel->getId() ] = $artikkel;
		}
	}
}
?>