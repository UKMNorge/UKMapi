<?php
require_once('UKM/sql.class.php');
require_once('UKM/kommune.class.php');

class avis {
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
		$sql = new SQL("SELECT * FROM `ukm_avis_nedslagsfelt`
						WHERE `avis_id` = '#avis'
						AND `kommune_id` = '#kommune'",
						array( 'avis' => $this->id, 'kommune' => $kommune )
					);
		$res = $sql->run('field', 'id');
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
		
		$sql = new SQL("SELECT `kommune_id` FROM `ukm_avis_nedslagsfelt`
				WHERE `avis_id` = '#avis'",
				array( 'avis' => $this->id )
			);
		$res = $sql->run();
		
		while( $r = SQL::fetch( $res ) ) {
			$this->kommuner[] = new kommune( $r['kommune_id'] );
			$this->kommuneid_array[] = $r['kommune_id'];
		}
	}
	
	private function _load_from_id( $id ) {
		$SQL = new SQL("SELECT * FROM `ukm_avis` WHERE `id` = '#id'",
						array('id' => $id ) );
		$res = $SQL->run('array');
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