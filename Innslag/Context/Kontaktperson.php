<?php

namespace UKMNorge\Innslag\Context;
require_once('UKM/Autoloader.php');

class Kontaktperson {
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