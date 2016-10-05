<?php
require_once('UKM/advarsel.class.php');

class advarsler extends Collection {
	
	public function har( $kategori ) {
		foreach( $this as $advarsel ) {
			if( $advarsel->getKategori() == $kategori ) {
				return $advarsel;
			}
		}	
	}
}