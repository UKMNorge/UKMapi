<?php
require_once('UKM/fylke.class.php');
	
class fylker {
	static $fylker = null;
	static $logMethod = 'throw';
	
	// static classes in php does not use __construct (bah)
	 private static function initialize() {
		self::$fylker = array();
		
		self::$fylker[2]	= new fylke(2, 'akershus', 'Akershus');
		self::$fylker[9]	= new fylke(9, 'aust-agder', 'Aust-Agder');
		self::$fylker[6]	= new fylke(6, 'buskerud', 'Buskerud');
		self::$fylker[20]	= new fylke(20, 'finnmark', 'Finnmark');
		self::$fylker[4]	= new fylke(4, 'hedmark', 'Hedmark');
		self::$fylker[12]	= new fylke(12, 'hordaland', 'Hordaland');
		self::$fylker[15]	= new fylke(15, 'moreogromsdal', 'Møre og Romsdal');
		self::$fylker[17]	= new fylke(17, 'nord-trondelag', 'Nord-Trøndelag');
		self::$fylker[18]	= new fylke(18, 'nordland', 'Nordland');
		self::$fylker[5]	= new fylke(5, 'oppland', 'Oppland');
		self::$fylker[3]	= new fylke(3, 'oslo', 'Oslo');
		self::$fylker[11]	= new fylke(11, 'rogaland', 'Rogaland');
		self::$fylker[14]	= new fylke(14, 'sognogfjordane', 'Sogn og Fjordane');
		self::$fylker[16]	= new fylke(16, 'sor-trondelag', 'Sør-Trøndelag');
		self::$fylker[8]	= new fylke(8, 'telemark', 'Telemark');
		self::$fylker[19]	= new fylke(19, 'troms', 'Troms');
		self::$fylker[10]	= new fylke(10, 'vest-agder', 'Vest-Agder');
		self::$fylker[7]	= new fylke(7, 'vestfold', 'Vestfold');
		self::$fylker[1]	= new fylke(1, 'ostfold', 'Østfold');

		self::$fylker[21]	= new fylke(21, 'testfylke', 'Testfylke');
		self::$fylker[31]	= new fylke(31, 'internasjonalt', 'Internasjonalt');
		self::$fylker[32]	= new fylke(32, 'gjester', 'Gjester');
	}
	
	public static function getById( $id ) {
		if( null == self::$fylker ) {
			self::initialize();
		}
		
		if( is_numeric( $id ) && isset( self::$fylker[ $id ] ) ) {
			return self::$fylker[ (int) $id ];
		}
		
		if('throw' == self::$logMethod)
			throw new Exception('Prøvde å aksessere et fylke som ikke finnes (ID: '. $id .')');
	}
	
	public static function getByLink( $id ) {
		if( null == self::$fylker ) {
			self::initialize();
		}
		
		switch( $id ) {
			case 'akershus':		return self::getById( 2 );
			case 'aust-agder':		return self::getById( 9 );
			case 'buskerud':		return self::getById( 6 );
			case 'finnmark':		return self::getById( 20 );
			case 'hedmark':			return self::getById( 4 );
			case 'hordaland':		return self::getById( 12 );
			case 'moreogromsdal':	return self::getById( 15 );
			case 'nord-trondelag':	return self::getById( 17 );
			case 'nordland':		return self::getById( 18 );
			case 'oppland':			return self::getById( 5 );
			case 'oslo':			return self::getById( 3 );
			case 'rogaland':		return self::getById( 11 );
			case 'sognogfjordane':	return self::getById( 14 );
			case 'sor-trondelag':	return self::getById( 16 );
			case 'telemark':		return self::getById( 8 );
			case 'troms':			return self::getById( 19 );
			case 'vest-agder':		return self::getById( 10 );
			case 'vestfold':		return self::getById( 7 );
			case 'ostfold':			return self::getById( 1 );
			
			case 'testfylke':		return self::getById( 21 );
		}

		throw new Exception('Prøvde å aksessere et fylke som ikke finnes (ID: '. $id .')');
	}
	
	public static function getAll() {
		self::initialize();

		$sortert = array();
		foreach( self::$fylker as $fylke ) {
			// Hopp over de falske fylkene på getAll
			if( $fylke->getId() > 20 ) {
				continue;
			}
			$sortert[ $fylke->getNavn() ] = $fylke;
		}
		ksort( $sortert );
		return $sortert;
	}
	
	public static function getAllInkludertFalske() {
		self::initialize();

		$sortert = array();
		foreach( self::$fylker as $fylke ) {
			$sortert[ $fylke->getNavn() ] = $fylke;
		}
		ksort( $sortert );
		return $sortert;
	}

	public static function setLogMethod($method) {
		self::$logMethod = $method;
	}
}