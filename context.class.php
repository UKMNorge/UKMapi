<?php
class context {
	var $type = null;
	
	var $monstring = null;
	var $innslag = null;
	var $forestilling = null;
	
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
	
	public static function createForestilling( $id ) {
		$context = new context( 'forestilling' );
		$context->forestilling = new context_forestilling( $id );
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
}