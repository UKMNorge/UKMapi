<?php
require_once('UKM/_collection.class.php');
require_once('UKM/innslag_type.class.php');

class innslag_typer extends Collection {

	static $all = null;
	static $allScene = null;

	public function addById( $id ) {
		return $this->add( self::getById( $id ) );
	}

	static function getById( $id, $kategori=false ) {
		return self::_load( $id, $kategori );
	}
	
	static function getByName( $key ) {
		return self::_load( self::_translate_key_to_id( $key ) );	
	}
	
	static function getAllTyper() {
		if( null == self::$all ) {
			foreach( array(1,2,3,4,5,6,8) as $id ) {
				self::$all[] = self::getById( $id );
			}
		}
		return self::$all;
	}
	
	static function getAllScene() {
		if( null == self::$allScene ) {
			foreach( array('musikk','dans','teater','litteratur','annet') as $kategori ) {
				self::$allScene[] = self::getById( 1, $kategori );
			}
		}
		return self::$allScene;
	}
	
	static function _load( $id, $kategori=false ) {
		switch( $id ) {
			case 1:
				switch( $kategori ) {
					case 'scene':
					case 'musikk':
						$data = array('id' => 1,
									  'key' => 'musikk',
									  'name' => 'Musikk',
									  'icon' => 'http://ico.ukm.no/delta/delta-musikk-64.png',
									  'har_filmer' => true,
									  'har_titler' => true,
									  'database_table' => 'smartukm_titles_scene',
									  );
						break;
					case 'dans':
						$data = array('id' => 1,
									  'key' => 'dans',
									  'name' => 'Dans',
									  'icon' => 'http://ico.ukm.no/delta/delta-dans-64.png',
									  'har_filmer' => true,
									  'har_titler' => true,
									  'database_table' => 'smartukm_titles_scene',
									  );
						break;
					case 'teater':
						$data = array('id' => 1,
									  'key' => 'teater',
									  'name' => 'Teater',
									  'icon' => 'http://ico.ukm.no/delta/delta-teater-64.png',
									  'har_filmer' => true,
									  'har_titler' => true,
									  'database_table' => 'smartukm_titles_scene',
									  );
						break;						
					case 'litteratur':
						$data = array('id' => 1,
									  'key' => 'litteratur',
									  'name' => 'Litteratur',
									  'icon' => 'http://ico.ukm.no/delta/delta-litteratur-64.png',
									  'har_filmer' => true,
									  'har_titler' => true,
									  'database_table' => 'smartukm_titles_scene',
									  );
						break;
					default:
						$data = array('id' => 1,
									  'key' => 'scene',
									  'name' => ($kategori == false ? 'Scene' : 'Annet'),
									  'icon' => 'http://ico.ukm.no/delta/delta-annet-64.png',
									  'har_filmer' => true,
									  'har_titler' => true,
									  'database_table' => 'smartukm_titles_scene',
									  );
				}
				break;
			case 2:
				$data = array('id' => 2,
							  'key' => 'video',
							  'name' => 'Film',
							  'icon' => 'http://ico.ukm.no/delta/delta-film-64.png',
							  'har_filmer' => true,
							  'har_titler' => true,
							  'database_table' => 'smartukm_titles_video',
							  );
				break;
			case 3:
				$data = array('id' => 3,
							  'key' => 'utstilling',
							  'name' => 'Utstilling',
							  'icon' => 'http://ico.ukm.no/delta/delta-utstilling-64.png',
							  'har_filmer' => false,
							  'har_titler' => true,
							  'database_table' => 'smartukm_titles_exhibition',
							  );
				break;
			case 4:
				$data = array('id' => 4,
							  'key' => 'konferansier',
							  'name' => 'Konferansier',
							  'icon' => 'http://ico.ukm.no/delta/delta-konferansier-64.png',
							  'har_filmer' => false,
							  'har_titler' => false,
							  'database_table' => false,

							  );
				break;
			case 5:
				$data = array('id' => 5,
							  'key' => 'nettredaksjon',
							  'name' => 'UKM Media',
							  'icon' => 'http://ico.ukm.no/delta/delta-nettredaksjon-64.png',
							  'har_filmer' => false,
							  'har_titler' => false,
							  'database_table' => false,
							  );
				break;
			case 6:
				$data = array('id' => 6,
							  'key' => 'matkultur',
							  'name' => 'Matkultur',
							  'icon' => 'http://ico.ukm.no/delta/delta-matkultur-64.png',
							  'har_filmer' => true,
							  'har_titler' => true,
							  'database_table' => 'smartukm_titles_other',
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
							  'database_table' => false,
							  );
				break;
			default:
				$data = array('id' => 'missing '. $id);
		}
		return new innslag_type( $data['id'], $data['key'], $data['name'], $data['icon'], $data['har_filmer'], $data['har_titler'], $data['database_table'] );
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