<?php
require_once('UKM/fylke.class.php');
	
class fylker {
	static $fylker = null;
	static $logMethod = 'throw';
	
	// static classes in php does not use __construct (bah)
	 private static function initialize() {
		self::$fylker = array();
        
        // PRE 2020 Bye-bye world 😢
		self::$fylker[2]	= new fylke(2, 'akershus', 'Akershus', false);
		self::$fylker[9]	= new fylke(9, 'aust-agder', 'Aust-Agder', false);
		self::$fylker[6]	= new fylke(6, 'buskerud', 'Buskerud', false);
		self::$fylker[20]	= new fylke(20, 'finnmark', 'Finnmark', false);
		self::$fylker[4]	= new fylke(4, 'hedmark', 'Hedmark', false);
		self::$fylker[12]	= new fylke(12, 'hordaland', 'Hordaland', false);
		self::$fylker[14]	= new fylke(14, 'sognogfjordane', 'Sogn og Fjordane', false);
		self::$fylker[16]	= new fylke(16, 'sor-trondelag', 'Sør-Trøndelag', false);
		self::$fylker[8]	= new fylke(8, 'telemark', 'Telemark', false);
		self::$fylker[19]	= new fylke(19, 'troms', 'Troms', false);
		self::$fylker[10]	= new fylke(10, 'vest-agder', 'Vest-Agder', false);
		self::$fylker[7]	= new fylke(7, 'vestfold', 'Vestfold', false);
		self::$fylker[1]	= new fylke(1, 'ostfold', 'Østfold', false);
		self::$fylker[17]	= new fylke(17, 'nord-trondelag', 'Nord-Trøndelag', false);
		self::$fylker[5]	= new fylke(5, 'oppland', 'Oppland', false);

        // PRE 2020 Still going strong
        self::$fylker[15]	= new fylke(15, 'moreogromsdal', 'Møre og Romsdal', true);
        self::$fylker[18]	= new fylke(18, 'nordland', 'Nordland', true);
		self::$fylker[3]	= new fylke(3, 'oslo', 'Oslo', true);
        self::$fylker[11]	= new fylke(11, 'rogaland', 'Rogaland', true);
        
        // UKM-fylker
		self::$fylker[21]	= new fylke(21, 'testfylke', 'Testfylke', true, true);
		self::$fylker[31]	= new fylke(31, 'internasjonalt', 'Internasjonalt', true, true);
		self::$fylker[32]	= new fylke(32, 'gjester', 'Gjester', true, true);

        // 2020-fylker 🎉
		self::$fylker[30]	= new fylke(30, 'viken', 'Viken', true);
		self::$fylker[34]	= new fylke(34, 'innlandet', 'Innlandet', true);
        self::$fylker[38]	= new fylke(38, 'vestfold-og-telemark', 'Vestfold og Telemark', true);
        self::$fylker[42]	= new fylke(42, 'agder', 'Agder', true);
		self::$fylker[46]	= new fylke(46, 'vestland', 'Vestland', true);
		self::$fylker[50]	= new fylke(50, 'trondelag', 'Trøndelag', true);
		self::$fylker[54]	= new fylke(54, 'troms-og-finnmark', 'Troms og Finnmark', true);
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
			case 'gjester':			return self::getById( 32 );
			case 'internasjonalt':	return self::getById( 31 );
		}

		throw new Exception('Prøvde å aksessere et fylke som ikke finnes (ID: '. $id .')');
	}
    
    /**
     * Hent alle aktive, reelle fylker
     * Dette er standard-utlisting, da det er sjeldnere vi trenger å hente ut
     * de falske, inaktive og gjeste-fylkene
     *
     * @return Array<fylke>
     */
	public static function getAll() {
		$sortert = array();
		foreach( self::filterEkte( self::filterAktiv( self::getAbsoluteAll() ) ) as $fylke ) {
			$sortert[ $fylke->getNavn() ] = $fylke;
		}
		ksort( $sortert );
		return $sortert;
    }

    /**
     * Hent absolutt alle fylker
     *
     * @return Array<fylke> $fylker
     */
    public static function getAbsoluteAll() {
        self::initialize();

        return self::$fylker;
    }
    
    /**
     * Filtrer ut kun de reelle fylkene
     *
     * @param Array<fylke> $fylker
     * @return Array<fylke> $aktive_fylker
     */
    public static function filterEkte( Array $fylker ) {
        $sortert = [];
        foreach( $fylker as $fylke ) {
            if( !$fylke->erFalskt() ) {
                $sortert[] = $fylke;
            }
        }
        return $sortert;
    }
    
    /**
     * Filtrer ut kun de aktive fylkene
     *
     * @param Array<fylke> $fylker
     * @return Array<fylke> $aktive_fylker
     */
    public static function filterAktiv( Array $fylker ) {
        $sortert = [];
        foreach( $fylker as $fylke ) {
            if( $fylke->erAktivt() ) {
                $sortert[] = $fylke;
            }
        }
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

	public static function getAllInkludertGjester() {
		self::initialize();

		$sortert = array();
		foreach( self::$fylker as $fylke ) {
			if( $fylke->getId() == 21 ) {
				continue;
			}
			$sortert[ $fylke->getNavn() ] = $fylke;
		}
		ksort( $sortert );
		return $sortert;
	}


	public static function setLogMethod($method) {
		self::$logMethod = $method;
	}
}