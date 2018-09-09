<?php

require_once('UKM/sql.class.php');
require_once('UKM/avis.class.php');

class aviser {
	
	private $aviser = null;
	private $fylke = null;
	
	
	public function __construct() {
		
	}
	
	public function hasRelation( $kommune ) {
		$sql = new SQL("SELECT `id` FROM `ukm_avis_nedslagsfelt` 
						WHERE `kommune_id` = '#kommune'
						LIMIT 1",
						array('kommune' => $kommune ) 
						);
		$res = $sql->run('field', 'id');
		if( null === $res ) {
			return false;
		}
		return true;
	}
	
	public function reset() {
		$this->aviser = array();
	}
	
	public function getAllByFylke( $fylke_id ) {
		$this->fylke = $fylke_id;
		if( null == $this->aviser ) {
			$this->_load();
		}
		
		return $this->aviser;
	}
	
	public function relate( $avis, $kommune ) {
		$SQL = new SQLins('ukm_avis_nedslagsfelt');
		$SQL->add('avis_id', $avis );
		$SQL->add('kommune_id', $kommune );
		$SQL->run();
	}
	
	public function unrelateAll( $kommuner ) {
		if( is_array( $kommuner ) ) {
			foreach( $kommuner as $kommune ) {
				$this->unrelateAllForKommune( $kommune['id'] );
			}
		}
	}
	
	public function unrelateAllForKommune( $k_id ) {
		$SQLdel = new SQLdel('ukm_avis_nedslagsfelt', array('kommune_id' => $k_id ) );
		$res = $SQLdel->run();
		return $res;
	}
	
	private function _load() {
		$sql = new SQL("SELECT * FROM `ukm_avis`
						WHERE `fylke` = '#fylke'
						ORDER BY `type` ASC,
						`name` ASC",
						array('fylke'=> $this->fylke )
					);
		$res = $sql->run();

		while( $r = SQL::fetch( $res ) ) {
			$this->aviser[] = new avis( $r );
		}
	}
}