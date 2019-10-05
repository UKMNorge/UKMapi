<?php

namespace UKMNorge\Innslag\Advarsler;
require_once('UKM/Autoloader.php');

class Advarsel {
    var $kategori = null;
	var $melding = null;

	// Hvilke advarselsnivåer har vi?
	// Bruker bootstrap-default: warning og danger.
	var $level = null;
	
	static function create( $kategori, $melding, $level='warning' ) {
		return array('kategori' => $kategori, 'level' => $level, 'melding' => $melding );
	}

	static function ny( $kategori, $melding, $level='warning' ) {
		return new Advarsel(self::create($kategori, $melding, $level));
	}
	
	public function __construct( $row ) {
		$this->setKategori( $row['kategori'] );
		$this->setMelding( $row['melding'] );
		$this->setLevel( $row['level'] );
	}
	
	public function setKategori( $kategori ) {
		$this->kategori = $kategori;
		return $this;
	}
	public function getKategori() {
		return $this->kategori;
	}
	
	public function setMelding( $melding ) {
		$this->melding = $melding;
		return $this;
	}
	public function getMelding() {
		return $this->melding;
	}
	
	public function setLevel( $level ) {
		$this->level = $level;
		return $this;
	}
	public function getLevel() {
		return $this->level;
	}
	
	public function __toString() {
		return $this->getMelding();
	}
}