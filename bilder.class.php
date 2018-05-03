<?php
require_once('UKM/sql.class.php');
require_once('UKM/bilde.class.php');

class bilder {
	
	static $table = 'ukmno_wp_related';
	var $b_id = false;
	var $bilder = null;

	public function __construct( $b_id ) {
		$this->b_id = $b_id;
	}
	
	public function har() {
		return $this->getAntall() > 0;
	}

	public function getAntall() {
		return sizeof( $this->getAll() );
	}
	
	public function first() {
		return $this->getFirst();
	}
	
	public function getFirst() {
		$bilder = $this->getAll();
		$bilde = array_shift( $bilder );
		return $bilde;
	}
	
	/**
	 * Alle bilder for gitt innslag
	 */
	public function getAll() {
		if( null == $this->bilder ) {
			$this->_load();
		}
		return $this->bilder;
	}
	
	public function getAllFrom( $pl_type=false ) {
		if( false == $pl_type ) {
			return $this->getAll();
		}
		
		$type_sorted = array();
		foreach( $this->getAll() as $bilde ) {
			if( $bilde->getMonstringType() == $pl_type ) {
				$type_sorted[] = $bilde;
			}
		}
		return $type_sorted;
	}
	
	
	public function harValgt( $tittel=0 ) {
		try {
			$this->getvalgt( $tittel );
			return true;
		} catch( Exception $e ) {
			return false;
		}
	}
	/**
	 * Hent det bildet som er valgt ved videresending
	 * For kunstner-bilde: velg tittel = 0;
	**/
	public function getValgt( $tittel=0 ) {
		$sql = new SQL("
			SELECT `rel_id` 
			FROM `smartukm_videresending_media`
			WHERE `b_id` = '#innslag'
			AND `t_id` = '#tittel'
			",
			[
				'innslag'	=> $this->b_id,
				'tittel'	=> $tittel,
			]
		);
		$media_id = $sql->run('field', 'rel_id');
		
		if( is_numeric( $media_id ) && $media_id > 0 ) {
			foreach( $this->getAll() as $bilde ) {
				if( $bilde->getRelId() == $media_id ) {
					return $bilde;
				}
			}
		}
		
		throw new Exception('Fant ingen valgte bilder');
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
						JOIN `ukm_bilder` ON (`#table`.`post_id` = `ukm_bilder`.`wp_post` AND `#table`.`b_id` = `ukm_bilder`.`b_id`)
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