<?php

namespace UKMNorge\Allergener;

class Allergen {

	public $id = null;
	public $navn = null;
	public $beskrivelse = null;

    /**
     * Opprett allergen. Skal alltid skje fra Allergener-klassen
     *
     * @param Array $data
     */
	public function __construct( $data ) {
		$this->id = $data['id'];
		$this->navn = $data['navn'];
		$this->beskrivelse = $data['beskrivelse'];
		$this->kategori = $data['kategori'];
	}

    /**
     * Hent ID
     *
     * @return String
     */
	public function getId() {
		return $this->id;
    }
    
    /**
     * Hent navn
     *
     * @return String
     */
	public function getNavn() {
		return $this->navn;
    }
    
    /**
     * Hent beskrivelse
     *
     * @return String
     */
	public function getBeskrivelse() {
		return $this->beskrivelse;
    }
    
    /**
     * Hent kategori
     *
     * @return String kulturell|standard
     */
	public function getKategori() {
		return $this->kategori;
	}
}