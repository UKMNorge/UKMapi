<?php
require_once('UKM/sql.class.php');
require_once('UKM/bilde.class.php');

class bilder {
	
	static $table = 'ukmno_wp_related';
	var $b_id = false;

	public function __construct( $b_id ) {
		$this->b_id = $b_id;
		$this->_load();
	}	
	
	/**
	 * Alle bilder for gitt innslag
	 */
	public function getAll() {
		return $this->bilder;
	}
	
	public function getAllFrom( $pl_type=false ) {
		if( false == $pl_type ) {
			return $this->getAll();
		}
		
		$type_sorted = array();
		foreach( $this->bilder as $bilde ) {
			if( $bilde->getMonstringType() == $pl_type ) {
				$type_sorted[] = $bilde;
			}
		}
		return $type_sorted;
	}
	
	/**
	 * Finn siste bilde
	 *
	**/
	public function getLast() {
		$bilder_copy = copy( $this->bilder );
		ksort( $bilder_copy );
		$last = end( $bilder_copy );
		unset( $bilder_copy );
		return $last;
	}
	
	private function _load() {
		$this->bilder = array();
		$SQL = new SQL("SELECT * FROM `#table`
						LEFT JOIN `ukm_bilder` ON (`#table`.`post_id` = `ukm_bilder`.`id`)
						WHERE `#table`.`b_id` = '#bid'
						AND `post_type` = 'image'",
						array('table'=>self::$table,
							  'bid'=>$this->b_id)
						);
		$get = $SQL->run();
		if( !$get ) {
			return false;
		}		
		while( $r = mysql_fetch_assoc( $get ) ) {
			$bilde = new bilde( $r );
			$this->bilder[ $bilde->getId() ] = $bilde;
		}
	}
}
?>