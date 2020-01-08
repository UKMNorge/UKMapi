<?php

namespace UKMNorge\Avis;

use UKMNorge\Database\SQL\Query;
use UKMNorge\Geografi\Kommune;

class Avis {
	private $id;
	private $name;
	private $url;
	private $email;
	private $fylke;
	private $type;
	private $kommuner = array();
	
	public function __construct( $avis ) {
		if( is_numeric( $avis ) ) {
			$this->_load_from_id( $avis );
		} else {
			$this->_load_from_row( $avis );
		}
	}
	
	public function isRelated( $kommune ) {
		$sql = new Query(
            "SELECT `id` 
            FROM `ukm_avis_nedslagsfelt`
            WHERE `avis_id` = '#avis'
            AND `kommune_id` = '#kommune'",
            [
                'avis' => $this->id,
                'kommune' => $kommune
            ]
        );
		$res = $sql->getField();
		if( null === $res ) {
			return false;
		}
		return true;
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function getEmail() {
		return $this->email;
	}
	
	public function getType() {
		return $this->type;
	}
	
	public function getFylke() {
		return $this->getKommune()->getFylke();
	}
	public function getKommune() {
		if( !isset( $this->kommuner[0] ) ) {
			return false;
		}
		return $this->kommuner[0];
	}
	
	public function getNedslagsfelt() {
		$this->_loadNedslagsfelt();
		
		return $this->kommuner;		
	}
	
	public function getNedslagsfeltAsCSV() {
		$this->_loadNedslagsfelt();
		
		return $this->kommuneid_array;		
	}
	
	private function _loadNedslagsfelt() {
		if( sizeof( $this->kommuner ) > 0 ) {
			return;
		}

		$this->kommuner = array();
		$this->kommuneid_array = array();
		
		$sql = new Query(
            "SELECT `kommune_id`
            FROM `ukm_avis_nedslagsfelt`
			WHERE `avis_id` = '#avis'",
			[
                'avis' => $this->id
            ]
		);
		$res = $sql->run();
		
		while( $r = Query::fetch( $res ) ) {
			$this->kommuner[] = new Kommune( $r['kommune_id'] );
			$this->kommuneid_array[] = $r['kommune_id'];
		}
	}
	
	private function _load_from_id( $id ) {
		$SQL = new Query(
            "SELECT * 
            FROM `ukm_avis` 
            WHERE `id` = '#id'",
            [
                'id' => $id
            ]
        );
		$res = $SQL->getArray();
		$this->_load_from_row( $res );
	}
	
	private function _load_from_row( $row ) {
		$this->id = $row['id'];
		$this->name = $row['name'];
		$this->url = $row['url'];
		$this->email = $row['email'];
		$this->fylke = $row['fylke'];
		$this->type = $row['type'];
	}
}