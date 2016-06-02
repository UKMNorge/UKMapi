<?php

class innslag_typer {
	
	static function getById( $id, $kategori=false ) {
		return self::_load( $id, $kategori );
	}
	
	static function getByName( $key ) {
		return self::_load( self::_translate_key_to_id( $key ) );	
	}
	
	static function _load( $id, $kategori=false ) {
		switch( $id ) {
			case 1:
				switch( $kategori ) {
					case 'musikk':
						$data = array('id' => 1,
									  'key' => 'musikk',
									  'name' => 'Musikk',
									  'icon' => 'http://ico.ukm.no/delta/delta-musikk-64.png',
									  'har_filmer' => true,
									  'har_titler' => true,
									  );
						break;
					case 'dans':
						$data = array('id' => 1,
									  'key' => 'dans',
									  'name' => 'Dans',
									  'icon' => 'http://ico.ukm.no/delta/delta-dans-64.png',
									  'har_filmer' => true,
									  'har_titler' => true,
									  );
						break;
					case 'teater':
						$data = array('id' => 1,
									  'key' => 'teater',
									  'name' => 'Teater',
									  'icon' => 'http://ico.ukm.no/delta/delta-teater-64.png',
									  'har_filmer' => true,
									  'har_titler' => true,
									  );
						break;						
					case 'litteratur':
						$data = array('id' => 1,
									  'key' => 'litteratur',
									  'name' => 'Litteratur',
									  'icon' => 'http://ico.ukm.no/delta/delta-litteratur-64.png',
									  'har_filmer' => true,
									  'har_titler' => true,
									  );
						break;
					default:
						$data = array('id' => 1,
									  'key' => 'scene',
									  'name' => 'Annet',
									  'icon' => 'http://ico.ukm.no/delta/delta-annet-64.png',
									  'har_filmer' => true,
									  'har_titler' => true,
									  );
				}
				break;
			case 2:
				$data = array('id' => 2,
							  'key' => 'video',
							  'name' => 'Film',
							  'icon' => 'http://ico.ukm.no/delta/delta-video-64.png',
							  'har_filmer' => true,
							  'har_titler' => true,
							  );
				break;
			case 3:
				$data = array('id' => 3,
							  'key' => 'utstilling',
							  'name' => 'Utstilling',
							  'icon' => 'http://ico.ukm.no/delta/delta-utstilling-64.png',
							  'har_filmer' => false,
							  'har_titler' => true,
							  );
				break;
			case 4:
				$data = array('id' => 4,
							  'key' => 'konferansier',
							  'name' => 'Konferansier',
							  'icon' => 'http://ico.ukm.no/delta/delta-konferansier-64.png',
							  'har_filmer' => false,
							  'har_titler' => false,
							  );
				break;
			case 5:
				$data = array('id' => 5,
							  'key' => 'nettredaksjon',
							  'name' => 'UKM Media',
							  'icon' => 'http://ico.ukm.no/delta/delta-nettredaksjon-64.png',
							  'har_filmer' => false,
							  'har_titler' => false,
							  );
				break;
			case 6:
				$data = array('id' => 6,
							  'key' => 'matkultur',
							  'name' => 'Matkultur',
							  'icon' => 'http://ico.ukm.no/delta/delta-matkultur-64.png',
							  'har_filmer' => true,
							  'har_titler' => false,
							  );
				break;
			case 8:
			case 9:
				$data = array('id' => 8,
							  'key' => 'arrangor',
							  'name' => 'Arrangør',
							  'icon' => 'http://ico.ukm.no/delta/delta-arrangor-64.png',
							  'har_filmer' => false,
							  'har_titler' => false,
							  );
				break;
			default:
				$data = array('id' => 'missing '. $id);
		}
		return new innslag_type( $data['id'], $data['key'], $data['name'], $data['icon'], $data['har_filmer'], $data['har_titler'] );
	}
	
	
	static function _translate_key_to_id( $key ) {
		switch( $key ) {
			case 'musikk':
			case 'dans':
			case 'teater':
			case 'litteratur':
			case 'scene': 			$bt_id = 1; break;
			case 'film':
			case 'video': 			$bt_id = 2; break;
			case 'utstilling': 		$bt_id = 3; break;
			case 'konferansier': 	$bt_id = 4; break;
			case 'nettredaksjon': 	$bt_id = 5; break;
			case 'matkultur':		$bt_id = 6; break;
			case 'arrangor': 		$bt_id = 8; break;
			case 'sceneteknikk': 	$bt_id = 9; break;
			case 'annet': 			$bt_id = 1; break;
			default:				$bt_id = false;
		}
		return $bt_id;
	}
}

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

/*
function getBandtypeID($type) {
	switch($type) {
		case 'musikk':
		case 'dans':
		case 'teater':
		case 'litteratur':
		case 'scene': 			$bt_id = 1; break;
		case 'film':
		case 'video': 			$bt_id = 2; break;
		case 'utstilling': 		$bt_id = 3; break;
		case 'konferansier': 	$bt_id = 4; break;
		case 'nettredaksjon': 	$bt_id = 5; break;
		case 'matkultur':		$bt_id = 6; break;
		case 'arrangor': 		$bt_id = 8; break;
		case 'sceneteknikk': 	$bt_id = 9; break;
		case 'annet': 			$bt_id = 1; break;
		default:				$bt_id = false;
	}

	return $bt_id;
}

function getBandTypeFromID($id) {
	switch($id) {
		case 1: 	$type = 'scene';				break;
		case 2: 	$type = 'video';				break;
		case 3: 	$type = 'utstilling';			break;
		case 4: 	$type = 'konferansier';			break;
		case 5: 	$type = 'nettredaksjon';		break;
		case 6: 	$type = 'matkultur';			break;
		case 8: 	$type = 'arrangor';				break;
		case 9: 	$type = 'sceneteknikk';			break;
		default: 	$type = 'annet';				break;
	}

	return $type;
}
*/