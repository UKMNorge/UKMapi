<?php

namespace UKMNorge\Sensitivt\Write;

use UKMNorge\Sensitivt\Intoleranse as ReadIntoleranse;
use Exception;
require_once('UKM/Sensitivt/Intoleranse.php');

class Intoleranse extends ReadIntoleranse {
    
    public function saveTekst( $tekst ) {
        return $this->update('tekst', $tekst);
	}
	
	public function saveListe( $liste ) {
		if( is_array( $liste ) ) {
			$string = implode('|', $liste);
			return $this->update('liste', $string);
		}
		throw new Exception('SetListe krever array som input');
	}

	public function saveListeHuman( $string ) {
		return $this->update('liste_human', $string);
	}
}
