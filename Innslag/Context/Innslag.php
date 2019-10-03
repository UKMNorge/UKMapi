<?php

namespace UKMNorge\Innslag\Context;
require_once('UKM/Autoloader.php');

class Innslag {
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