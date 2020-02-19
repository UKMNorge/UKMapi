<?php

namespace UKMNorge\Innslag\Nominasjon;

/**
 * Innslagets nominasjon behandles som et eget objekt
 * For å kunne kjøre getNominasjon()->har() på et innslag
 * som aldri vil ha nominasjon, eksisterer denne klassen
 * 
**/
class Placeholder {
	private $har_nominasjon = false;

	public function __construct( $skip1 ) {
		// Do nothing	
	}

    /**
     * Har innslaget nominasjon?
     *
     * @return Bool
     */
	public function har() {
		return $this->harNominasjon();
	}
    
    /**
     * Har innslaget nominasjon?
     *
     * @alias har()
     * 
     * @return Bool
     */
	public function harNominasjon() {
		return $this->har_nominasjon;
	}
    
    /**
     * Angi om innslaget har en nominasjon
     *
     * @param Bool $bool
     * @return self
     */
	public function setHarNominasjon( Bool $bool ) {
		$this->har_nominasjon = $bool;
		return $this;
	}
} 