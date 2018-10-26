<?php
class context {
	var $type = null;
	
	var $monstring = null;
	var $innslag = null;
	var $forestilling = null;
	var $videresend_til = false;
	
	public static function createMonstring( $id, $type, $sesong, $fylke, $kommuner ) {
		$context = new context( 'monstring' );
		$context->monstring = new context_monstring( $id, $type, $sesong, $fylke, $kommuner );
		return $context;
	}
	
	public static function createInnslag( $id, $type, $monstring_id, $monstring_type, $monstring_sesong) {
		$context = new context( 'innslag' );
		$context->monstring = new context_monstring( $monstring_id, $monstring_type, $monstring_sesong, false, false );
		$context->innslag = new context_innslag( $id, $type );
		return $context;
	}
	
	public static function createForestilling( $id, $context_monstring=false ) {
		$context = new context( 'forestilling' );
		$context->forestilling = new context_forestilling( $id );
		if( $context_monstring !== false && get_class( $context_monstring ) == 'context_monstring' ) {
			$context->monstring = $context_monstring;
		}
		return $context;
	}
	
	public function __construct( $type ) {
		$this->type = $type;
	}
	
	public function getType() {
		return $this->type;
	}
	
	public function getMonstring() {
		return $this->monstring;
	}
	public function getInnslag() {
		return $this->innslag;
	}
	public function getForestilling() {
		return $this->forestilling;
	}
	
	/**
	 * Hvis innslaget er hentet ut som en del av en innslag-collection,
	 * og funksjonen getVideresendte() er kjørt, settes dette på innslagets
	 * kontekst, slik at det kan brukes på hentPersoner
	**/
	public function getVideresendTil() {
		return $this->videresend_til;
	}
	public function setVideresendTil( $monstring ) {
		if( is_object( $monstring ) && get_class( $monstring ) == 'monstring_v2' ) {
			$monstring = $monstring->getId();
		}
		$this->videresend_til = $monstring;
	}
}

class context_forestilling {
	var $id = null;
	
	public function __construct( $id ) {
		$this->id = $id;
	}

	public function setId( $id ) {
		$this->id = $id;
		return $this;
	}
	public function getId() {
		return $this->id;
	}
}

class context_innslag {
	var $id = null;
	var $type = null;
	
	public function __construct( $id, $type ) {
		$this->id = $id;
		$this->type = $type;
	}

	public function setId( $id ) {
		$this->id = $id;
		return $this;
	}
	public function getId() {
		return $this->id;
	}
	
	public function setType( $type ) {
		$this->type = $type;
		return $this;
	}
	public function getType() {
		return $this->type;
	}
}

class context_monstring {
	var $id;
	var $type;
	var $sesong;
	var $kommuner;
	var $fylke;

	public function __construct( $id, $type, $sesong, $fylke, $kommuner ) {
		$this->id = $id;
		$this->type = $type;
		$this->sesong = $sesong;
		$this->kommuner = $kommuner;
		$this->fylke = $fylke;
	}
	
	public function setId( $id ) {
		$this->id = $id;
		return $this;
	}
	public function getId() {
		return $this->id;
	}
	
	public function setType( $type ) {
		$this->type = $type;
		return $this;
	}
	public function getType() {
		return $this->type;
	}
	
	public function setSesong( $sesong ) {
		$this->sesong = $sesong;
		return $this;
	}
	public function getSesong() {
		return $this->sesong;
	}
	
	public function setKommuner( $kommuner ) {
		$this->kommuner = $kommuner;
		return $this;
	}
	public function getKommuner() {
		return $this->kommuner;
	}
	
	public function setFylke( $fylke ) {
		if( !is_numeric( $fylke ) ) {
			throw new Exception('CONTEXT_MONSTRING: setFylke krever numerisk fylke-id');	
		}
		$this->fylke = $fylke;
		return $this;
	}
	public function getFylke() {
		return $this->fylke;
	}
	
	public function getVideresendTil() {
		if( null == $this->videresend_til ) {
			require_once('UKM/monstringer.class.php');
			switch( $this->getType() ) {
				case 'kommune': 
					$videresendTil = [];
					foreach( $this->getKommuner() as $kommune_id ) {
						$kommune = new kommune( $kommune_id );
						if( !isset( $videresendTil[ $kommune->getFylke()->getId() ] ) ) {
							$fylke = monstringer_v2::fylke( $kommune->getFylke(), $this->getSesong() );
							$videresendTil[ $kommune->getFylke()->getId() ] = $fylke->getId();
						}
					}
					$this->videresend_til = $videresendTil;
				break;
				case 'fylke':
					$this->videresend_til = monstringer_v2::land( $this->getSesong() );
				break;
				default:
					throw new Exception(
						'CONTEXT_MONSTRING: Kan ikke videresende fra landsnivå'
					);
			}
		}
		return $this->videresend_til;
	}
}