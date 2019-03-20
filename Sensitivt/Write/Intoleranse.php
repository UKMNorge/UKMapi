<?php

namespace UKMNorge\Sensitivt\Write;

use UKMNorge\Sensitivt\Intoleranse as ReadIntoleranse;
use Exception;
require_once('UKM/Sensitivt/Intoleranse.php');

class Intoleranse extends ReadIntoleranse {
    
    public function saveTekst( $tekst ) {
		$this->setTekst( $tekst );
		$res = $this->update('tekst', $tekst);
		if( $res || $res == 0 ) {
			$this->tekst = null;
			$this->har = null;
		}
		return $res;
	}
	
	public function saveListe( $liste ) {
		if( is_array( $liste ) ) {
			if( sizeof( $liste ) == 0 ) {
				$string = '';
			} else {
				$string = implode('|', $liste);
			}
			$this->setListe( $liste );
			$res = $this->update('liste', $string);

			if( $res || $res == 0 ) {
				$this->_liste = null;
				$this->liste_human = null;
				$this->intoleranser = null;
				$this->saveListeHuman( $this->getListeHuman() );
			}
			return $res;
		}
		throw new Exception('SetListe krever array som input');
	}

	public function saveListeHuman( $string ) {
		return $this->update('liste_human', $string);
	}
}
