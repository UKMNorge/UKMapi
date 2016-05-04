<?php
	
class fylker {
	static $fylker;
	static $fylker_by_id;
	static $fylker_by_name;
	
	// static classes in php does not use __construct (bah)
	 private static function initialize() {
		
		$fylke = new stdClass();
		$fylke->id = 2;
		$fylke->link = 'akershus';
		$fylke->name = 'Akershus';
		self::$fylker[] = $fylke;		
		
		$fylke = new stdClass();
		$fylke->id = 9;
		$fylke->link = 'aust-agder';
		$fylke->name = 'Aust-Agder';
		self::$fylker[] = $fylke;		
		
		$fylke = new stdClass();
		$fylke->id = 6;
		$fylke->link = 'buskerud';
		$fylke->name = 'Buskerud';
		self::$fylker[] = $fylke;		
		
		$fylke = new stdClass();
		$fylke->id = 20;
		$fylke->link = 'finnmark';
		$fylke->name = 'Finnmark';
		self::$fylker[] = $fylke;		
		
		$fylke = new stdClass();
		$fylke->id = 4;
		$fylke->link = 'hedmark';
		$fylke->name = 'Hedmark';
		self::$fylker[] = $fylke;		
		
		$fylke = new stdClass();
		$fylke->id = 12;
		$fylke->link = 'hordaland';
		$fylke->name = 'Hordaland';
		self::$fylker[] = $fylke;		
		
		$fylke = new stdClass();
		$fylke->id = 15;
		$fylke->link = 'moreogromsdal';
		$fylke->name = 'Møre og Romsdal';
		self::$fylker[] = $fylke;		
		
		$fylke = new stdClass();
		$fylke->id = 17;
		$fylke->link = 'nord-trondelag';
		$fylke->name = 'Nord-Trøndelag';
		self::$fylker[] = $fylke;		
		
		$fylke = new stdClass();
		$fylke->id = 18;
		$fylke->link = 'nordland';
		$fylke->name = 'Nordland';
		self::$fylker[] = $fylke;		
		
		$fylke = new stdClass();
		$fylke->id = 5;
		$fylke->link = 'oppland';
		$fylke->name = 'Oppland';
		self::$fylker[] = $fylke;		
		
		$fylke = new stdClass();
		$fylke->id = 3;
		$fylke->link = 'oslo';
		$fylke->name = 'Oslo';
		self::$fylker[] = $fylke;		
		
		$fylke = new stdClass();
		$fylke->id = 11;
		$fylke->link = 'rogaland';
		$fylke->name = 'Rogaland';
		self::$fylker[] = $fylke;		
		
		$fylke = new stdClass();
		$fylke->id = 14;
		$fylke->link = 'sognogfjordane';
		$fylke->name = 'Sogn og Fjordane';
		self::$fylker[] = $fylke;		
		
		$fylke = new stdClass();
		$fylke->id = 16;
		$fylke->link = 'sor-trondelag';
		$fylke->name = 'Sør-Trøndelag';
		self::$fylker[] = $fylke;		
		
		$fylke = new stdClass();
		$fylke->id = 8;
		$fylke->link = 'telemark';
		$fylke->name = 'Telemark';
		self::$fylker[] = $fylke;		
				
		$fylke = new stdClass();
		$fylke->id = 19;
		$fylke->link = 'troms';
		$fylke->name = 'Troms';
		self::$fylker[] = $fylke;		

		$fylke = new stdClass();
		$fylke->id = 10;
		$fylke->link = 'vest-agder';
		$fylke->name = 'Vest-Agder';
		self::$fylker[] = $fylke;		

		$fylke = new stdClass();
		$fylke->id = 7;
		$fylke->link = 'vestfold';
		$fylke->name = 'Vestfold';
		self::$fylker[] = $fylke;		


		foreach( self::$fylker as $fylke ) {
			self::$fylker_by_id[ $fylke->id ] = $fylke;
			self::$fylker_by_name[ $fylke->link ] = $fylke;
		}		
	}
	
	public static function getById( $id ) {
		self::initialize();
		if( !isset( self::$fylker_by_id[ $id ] ) ) {
			throw new Exception('Prøvde å aksessere et fylke som ikke finnes (ID: '. $id .')');
		}
		return self::$fylker_by_id[ $id ];
	}
	
	public static function getByLink( $id ) {
		self::initialize();
		if( !isset( self::$fylker_by_id[ $id ] ) ) {
			throw new Exception('Prøvde å aksessere et fylke som ikke finnes (ID: '. $id .')');
		}
		return self::$fylker_by_name[ $id ];
	}
	
	public static function getAll() {
		self::initialize();
		ksort( self::$fylker_by_name );
		return self::$fylker_by_name;
	}
}