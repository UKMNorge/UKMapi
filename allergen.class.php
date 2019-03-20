<?php

class Allergen {

	public $id = null;
	public $navn = null;
	public $beskrivelse = null;

	public function __construct( $data ) {
		$this->id = $data['id'];
		$this->navn = $data['navn'];
		$this->beskrivelse = $data['beskrivelse'];
		$this->kategori = $data['kategori'];
	}

	public function getId() {
		return $this->id;
	}
	public function getNavn() {
		return $this->navn;
	}
	public function getBeskrivelse() {
		return $this->beskrivelse;
	}
	public function getKategori() {
		return $this->kategori;
	}
}