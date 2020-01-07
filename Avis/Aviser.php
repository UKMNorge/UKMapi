<?php

use UKMNorge\Database\SQL\Delete;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Query;

class Aviser {
	
	private $aviser = null;
	private $fylke = null;
	
	
	public function __construct() {
		
	}
	
	public function hasRelation( $kommune ) {
		$sql = new Query(
            "SELECT `id` 
            FROM `ukm_avis_nedslagsfelt` 
            WHERE `kommune_id` = '#kommune'
            LIMIT 1",
            [
                'kommune' => $kommune
            ] 
        );
		$res = $sql->getField();
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
		$SQL = new Insert('ukm_avis_nedslagsfelt');
		$SQL->add('avis_id', $avis );
		$SQL->add('kommune_id', $kommune );
		$SQL->run();
	}
	
	public function unrelateAll( $kommuner ) {
		if( is_array( $kommuner ) ) {
			foreach( $kommuner as $kommune ) {
				if( get_class( $kommune ) !== 'kommune' ) {
					throw new Exception('unrelateAll krever et array av Kommune-objekter');
				}
				$this->unrelateAllForKommune( $kommune->getId() );
			}
		}
	}
	
	public function unrelateAllForKommune( $k_id ) {
		$SQLdel = new Delete(
            'ukm_avis_nedslagsfelt',
            [
                'kommune_id' => $k_id
            ]
        );
		$res = $SQLdel->run();
		return $res;
	}
	
	private function _load() {
		$sql = new Query(
            "SELECT * 
            FROM `ukm_avis`
            WHERE `fylke` = '#fylke'
            ORDER BY `type` ASC,
            `name` ASC",
            [
                'fylke'=> $this->fylke
            ]
        );
		$res = $sql->run();

		while( $r = SQL::fetch( $res ) ) {
			$this->aviser[] = new Avis( $r );
		}
	}
}