<?php

namespace UKMNorge\Geografi;
use Exception;

require_once('UKM/Autoloader.php');

class Fylker {
    static $fylker = null;
	static $logMethod = 'throw';
	
	// static classes in php does not use __construct (bah)
	 private static function initialize() {
		self::$fylker = array();
        
        // PRE 2020 Bye-bye world 😢
		self::$fylker[2]	= new Fylke(2, 'akershus', 'Akershus', false);
		self::$fylker[9]	= new Fylke(9, 'aust-agder', 'Aust-Agder', false);
		self::$fylker[6]	= new Fylke(6, 'buskerud', 'Buskerud', false);
		self::$fylker[20]	= new Fylke(20, 'finnmark', 'Finnmark', false);
		self::$fylker[4]	= new Fylke(4, 'hedmark', 'Hedmark', false);
		self::$fylker[12]	= new Fylke(12, 'hordaland', 'Hordaland', false);
		self::$fylker[14]	= new Fylke(14, 'sognogfjordane', 'Sogn og Fjordane', false);
		self::$fylker[16]	= new Fylke(16, 'sor-trondelag', 'Sør-Trøndelag', false);
		self::$fylker[8]	= new Fylke(8, 'telemark', 'Telemark', false);
		self::$fylker[19]	= new Fylke(19, 'troms', 'Troms', false);
		self::$fylker[10]	= new Fylke(10, 'vest-agder', 'Vest-Agder', false);
		self::$fylker[7]	= new Fylke(7, 'vestfold', 'Vestfold', false);
		self::$fylker[1]	= new Fylke(1, 'ostfold', 'Østfold', false);
		self::$fylker[17]	= new Fylke(17, 'nord-trondelag', 'Nord-Trøndelag', false);
		self::$fylker[5]	= new Fylke(5, 'oppland', 'Oppland', false);

        // PRE 2020 Still going strong
        self::$fylker[15]	= new Fylke(15, 'moreogromsdal', 'Møre og Romsdal', true);
        self::$fylker[18]	= new Fylke(18, 'nordland', 'Nordland', true);
		self::$fylker[3]	= new Fylke(3, 'oslo', 'Oslo', true);
        self::$fylker[11]	= new Fylke(11, 'rogaland', 'Rogaland', true);
        
        // UKM-fylker
        # Tilgang til Testfylke hvis kallet kommer fra UKM IT-TEAMET
        if(isset($_SERVER) && isset($_SERVER['HTTP_CF_CONNECTING_IP']) && $_SERVER['HTTP_CF_CONNECTING_IP'] == '81.0.146.162') {
            self::$fylker[21]	= new Fylke(21, 'testfylke', 'Testfylke', true, false);
        }
        else {
            self::$fylker[21]	= new Fylke(21, 'testfylke', 'Testfylke', true, true);
        }
		self::$fylker[90]	= new Fylke(90, 'internasjonalt', 'Internasjonalt', true, true); // tiligere 31
		self::$fylker[91]	= new Fylke(91, 'gjester', 'Gjester', true, true); // tidligere 32
		self::$fylker[92]	= new Fylke(92, 'digital', 'Digital', true, true); // tidligere 33

        
        /* 2024 OPPDATERING */
        // 2020-fylker continues 2024 🎉
		self::$fylker[34]	= new Fylke(34, 'innlandet', 'Innlandet', true);
        self::$fylker[42]	= new Fylke(42, 'agder', 'Agder', true);
		self::$fylker[46]	= new Fylke(46, 'vestland', 'Vestland', true);
		self::$fylker[50]	= new Fylke(50, 'trondelag', 'Trøndelag - Trööndelage', true);
        
        // Deactivated from 2024
		self::$fylker[54]	= new Fylke(54, 'tromsogfinnmark', 'Troms og Finnmark', false);
        self::$fylker[38]	= new Fylke(38, 'vestfoldogtelemark', 'Vestfold og Telemark', false);
		self::$fylker[30]	= new Fylke(30, 'viken', 'Viken', false);


        // Tidligere - Troms og Finnmark
		self::$fylker[55]	= new Fylke(55, 'troms', 'Troms - Romsa - Tromssa', true);
		self::$fylker[56]	= new Fylke(56, 'finnmark', 'Finnmark - Finnmárku - Finmarkku', true);

        // Tiligere - Vestfold og Telemark
		self::$fylker[39]	= new Fylke(39, 'vestfold', 'Vestfold', true);
		self::$fylker[40]	= new Fylke(40, 'telemark', 'Telemark', true);

        // Tiligere - Viken
		self::$fylker[31]	= new Fylke(31, 'ostfold', 'Østfold', true);
        self::$fylker[32]	= new Fylke(32, 'akershus', 'Akershus', true);
		self::$fylker[33]	= new Fylke(33, 'buskerud', 'Buskerud', true);



    }
    
    /**
     * Hent fylke fra faktisk ID
     *
     * @param Int $id
     * @return Fylke
     */
	public static function getById( Int $id ) {
		if( null == self::$fylker ) {
			self::initialize();
		}
		
		if( is_numeric( $id ) && isset( self::$fylker[ $id ] ) ) {
			return self::$fylker[ (int) $id ];
		}
		
		if('throw' == self::$logMethod) {
			throw new Exception(
                'Fra metode getById(), prøvde å aksessere et fylke som ikke finnes (ID: '. $id .')',
                103001
            );
        }
	}
    
    /**
     * Hent fylke fra tekst-id
     *
     * @param String $id
     * @return Fylke
     */
	public static function getByLink( String $id ) {
		if( null == self::$fylker ) {
			self::initialize();
		}
		
		switch( $id ) {
			// case 'akershus':		return self::getById( 2 ); // Gammelt fylke
			case 'akershus':		return self::getById( 32 ); // Ny id fra 2024
			case 'aust-agder':		return self::getById( 9 );
			// case 'buskerud':		return self::getById( 6 ); // Gammelt fylke
			case 'buskerud':		return self::getById( 33 ); // Ny id fra 2024
			// case 'finnmark':		return self::getById( 20 ); // Gammelt fylke
			case 'finnmark':		return self::getById( 56 ); // Ny id fra 2024
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
			// case 'telemark':		return self::getById( 8 );  // Gammelt fylke
			case 'telemark':		return self::getById( 40 ); // Ny id fra 2024
			// case 'troms':		return self::getById( 19 ); // Gammelt fylke
			case 'troms':			return self::getById( 55 ); // Ny id fra 2024
			case 'vest-agder':		return self::getById( 10 );
			// case 'vestfold':		return self::getById( 7 );  // Gammelt fylke
			case 'vestfold':		return self::getById( 39 ); // Ny id fra 2024
			// case 'ostfold':		return self::getById( 1 );  // Gammelt fylke
			case 'ostfold':			return self::getById( 31 ); // Ny id fra 2024
			
			case 'testfylke':		return self::getById( 21 );
			case 'gjester':			return self::getById( 91 );
            case 'internasjonalt':	return self::getById( 90 );
            
            case 'viken':           return self::getById(30); // Deaktiveres fra 2024
            case 'innlandet':       return self::getById(34);
            case 'vestfoldogtelemark': return self::getById(38); // Deaktiveres fra 2024
            case 'agder':           return self::getById(42);
            case 'vestland':        return self::getById(46);
            case 'trondelag':       return self::getById(50);
            case 'tromsogfinnmark': return self::getById(54); // Deaktiveres fra 2024
		}

		throw new Exception(
            'Prøvde å aksessere et fylke som ikke finnes (ID: '. $id .')',
            103001
        );
	}
    
    /**
     * Hent alle aktive, reelle fylker
     * Dette er standard-utlisting, da det er sjeldnere vi trenger å hente ut
     * de falske, inaktive og gjeste-fylkene
     *
     * @return Array<Fylke>
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
     * @return Array<Fylke> $fylker
     */
    public static function getAbsoluteAll() {
        self::initialize();
        
        $sortert = [];
        foreach( self::$fylker as $fylke ) {
            $sortert[$fylke->getNavn()] = $fylke;
        }
        ksort($sortert);

        return $sortert;
    }
    
    /**
     * Filtrer ut kun de reelle fylkene
     *
     * @param Array<Fylke> $fylker
     * @return Array<Fylke> $aktive_fylker
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
     * @param Array<Fylke> $fylker
     * @return Array<Fylke> $aktive_fylker
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

    /**
     * Hent aktive og deaktiverte fylker
     * 
     * Men ikke falske
     *
     * @return Array<Fylke>
     */
    public static function getAllInkludertDeaktiverte() {
        return static::filterEkte( static::getAbsoluteAll() );
    }

    /**
     * Hent alle aktive fylker, inkludert de som kun finnes i vårt system
     *
     * @return Array<Fylke>
     */
	public static function getAllInkludertFalske() {
		self::initialize();

		$sortert = array();
		foreach( self::$fylker as $fylke ) {
			$sortert[ $fylke->getNavn() ] = $fylke;
		}
		ksort( $sortert );
		return $sortert;
	}

    /**
     * Hent alle aktive fylker, inkludert gjestefylket
     *
     * @return Array<Fylke>
     */
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
    
    /**
     * Hent hvilket fylke gitt fylke er overtatt av
     *
     * @param Int $fylke_id
     * @throws Exception
     * @return Fylke
     */
    public static function getOvertattAv(Int $fylke_id) {
        switch ($fylke_id) {
            // Agder
            case 9:
            case 10:
                return Fylker::getById(42);
            // Østfold (nytt fylke 2024)
            case 1:
                return Fylker::getById(31);
            // Akershus (nytt fylke 2024)
            case 2:
                return Fylker::getById(32);
            // Buskerud (nytt fylke 2024)
            case 6:
                return Fylker::getById(33);    
            // Viken overas av Akershus 2024 (ikke helt riktig)
            case 30:
                return Fylker::getById(32);
            // Troms (nytt fylke 2024 - Troms - Romsa - Tromssa)
            case 19:
                return Fylker::getById(55);
            // Finnmark (nytt fylke 2024 - Finnmark - Finnmárku - Finmarkku)
            case 20:
                return Fylker::getById(56);
            // Troms og Finnmark overas av Troms 2024 (ikke helt riktig)
            case 54:
                return Fylker::getById(55);
            // Innlandet
            case 4:
            case 5:
                return Fylker::getById(34);
            // Vestland
            case 12:
            case 14:
                return Fylker::getById(46);
            // Trøndelag
            case 16:
            case 17:
                return Fylker::getById(50);
            // Vestfold (nytt fylke 2024)
            case 7:
                return Fylker::getById(39);
            // Telemark (nytt fylke 2024)
            case 8:
                return Fylker::getById(40);
            // Vestfold og Telemark overas av Vestfold 2024 (ikke helt riktig)
            case 38:
                return Fylker::getById(39);
        }
        throw new Exception(
            'Dette fylket har ikke blitt overtatt av et annet',
            103004
        );
    }

    /**
     * Hvilket fylke har overtatt for gitt fylke?
     *
     * @param Int $fylke_id
     * @throws Exception
     * @return Array<Fylke>
     */
    public static function getOvertattFor( Int $fylke_id ) {
        switch( $fylke_id ) {
            case 31: // Østfold overtar Viken
                return [
                    30 => Fylker::getById(30)
                ];
            case 32: // Akershus overtar Viken
                return [
                    30 => Fylker::getById(30)
                ];
            case 33: // Buskerud overtar Viken
                return [
                    30 => Fylker::getById(30)
                ];
                
            case 55: // Troms - Romsa - Tromssa overtar Troms og Finnmark
                return [
                    54 => Fylker::getById(54)
                ];
            case 56: // Finnmark - Finnmárku - Finmarkku overtar Troms og Finnmark
                return [
                    54 => Fylker::getById(54)
                ];
            
            case 39: // Vestfold overtar Vestfold og Telemark
                return [
                    38 => Fylker::getById(38)
                ];
            case 40: // Telemark overtar Troms og Vestfold og Telemark
                return [
                    38 => Fylker::getById(38)
                ];

            case 30:
                return [
                    1 => Fylker::getById(1),
                    2 => Fylker::getById(2),
                    6 => Fylker::getById(6)
                ];
            case 34:
                return [
                    4 => Fylker::getById(4),
                    5 => Fylker::getById(5)
                ];
            case 38:
                return [
                    7 => Fylker::getById(7),
                    8 => Fylker::getById(8)
                ];
            case 42:
                return [
                    9 => Fylker::getById(9),
                    10 => Fylker::getById(10)
                ];
            case 46:
                return [
                    12 => Fylker::getById(12),
                    14 => Fylker::getById(14)
                ];
            case 50:
                return [
                    16 => Fylker::getById(16),
                    17 => Fylker::getById(17)
                ];
            case 54:
                return [
                    19 => Fylker::getById(19),
                    20 => Fylker::getById(20)
                ];
            }
        throw new Exception(
            'Dette fylket har ikke overtatt for andre fylker.',
            103005
        );
    }
}