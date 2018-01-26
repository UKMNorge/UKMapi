<?php
require_once('UKM/sql.class.php');

class nominasjon_voksen extends nominasjon_voksen_placeholder {
		public function __construct( $nominasjon_id ) {
		if( !is_numeric( $nominasjon_id ) ) {
			throw new Exception('NOMINASJON_VOKSEN: Trenger numerisk nominasjons-ID for å opprette voksen',1);
		}
		
		$sql = new SQL(
			"SELECT * 
			FROM `ukm_nominasjon_voksen`
			WHERE `nominasjon` = '#nominasjon'",
			['nominasjon' => $nominasjon_id ]
		);
		$res = $sql->run('array');
		
		if( !is_array($res) ) {
			throw new Exception('NOMINASJON_VOKSEN: Kunne ikke finne voksen for nominasjon '. $nominasjon_id, 2);
		}
		
		$this->setId( $res['id'] );
		$this->setNominasjon( $res['nominasjon'] );
		$this->setNavn( $res['navn'] );
		$this->setMobil( $res['mobil'] );
		$this->setRolle( $res['rolle'] );
	}
	
	public function save() {
		return write_nominasjon::saveVoksen( $this );
	}

}

class nominasjon_voksen_placeholder {
	var $id;
	var $nominasjon;
	var $navn;
	var $mobil;
	var $rolle;
	
	public function __construct( $garbage ) {
		// Do nothing
	}
	
	public function getId() {
		return $this->id;
	}
	public function setId( $id ) {
		$this->id = $id;
		return $this;
	}
	
	public function getNominasjon() {
		return $this->nominasjon;
	}
	public function setNominasjon( $nominasjon ) {
		$this->nominasjon = $nominasjon;
		return $this;
	}
	
	public function getNavn() {
		return $this->navn;
	}
	public function setNavn( $navn ) {
		$this->navn = $navn;
		return $this;
	}
	
	public function getMobil() {
		return $this->mobil;
	}
	public function setMobil( $mobil ) {
		$this->mobil = $mobil;
		return $this;
	}
	
	public function getRolle() {
		return $this->rolle;
	}
	public function setRolle( $rolle ) {
		$this->rolle = $rolle;
		return $this;
	}
}
