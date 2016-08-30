<?php
	
class innslag_type {
	var $id = null;
	var $key = null;
	var $name = null;
	var $icon = null;
	var $har_filmer = false; # Kan det finnes noe i UKM-TV?
	var $har_titler = false;
	
	public function __construct($id, $key, $name, $icon, $har_filmer, $har_titler) {
		$this->setId( $id );
		$this->setKey( $key );
		$this->setNavn( $name );
		$this->setIcon( $icon );
		$this->setHarFilmer( $har_filmer );
		$this->setHarTitler( $har_titler );
	}
	
	public function setId( $id ) {
		$this->id = $id;
		return $this;
	}
	public function getId() {
		return $this->id;
	}
	
	public function setKey( $key ) {
		$this->key = $key;
		return $this;
	}
	public function getKey() {
		return $this->key;
	}
	
	public function setNavn( $name ) {
		$this->name = $name;
		return $this;
	}
	public function getNavn() {
		return $this->name;
	}
	
	public function setIcon( $icon ) {
		$this->icon = $icon;
		return $this;
	}
	public function getIcon() {
		return $this->icon;
	}
	
	public function setHarFilmer( $har_filmer ) {
		$this->har_filmer = $har_filmer;
		return $this;
	}
	public function harFilmer() {
		return $this->har_filmer;
	}

	
	public function setHarTitler( $har_titler ) {
		$this->har_titler = $har_titler;
		return $this;
	}
	public function harTitler() {
		return $this->har_titler;
	}
}