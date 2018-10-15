<?php
/**********************************************************************************************
 * DATABASE CONNECTION CLASS
 * Helper class for all SQL classes, managing the connection
**/

class DBread extends DB {
	const WRITE_ACCESS = false;
	protected static $connection = false;
	protected static $database = null;
	protected static $charset = 'utf8';
	protected static $hasError = false;
}
class DBwrite extends DB {
	const WRITE_ACCESS = true;
	protected static $connection = false;
	protected static $database = null;
	protected static $charset = 'utf8';
	protected static $hasError = false;
}

class DB {	
	/**
	 * Establish mysqli connection
	 *
	 * Connects using credentials for previously selected
	 * database (done by DB::setDatabase( ))
     * 
     * The _connectX-functions will self determine read / write access
     * based on current child instance (DBread / DBwrite)
	 * 
	 * Defaults to database name null, which means UKM (previously SS3)
	 *
	 * @return void
	**/
	static function connect() {
        switch ( static::$database ) {
            case 'ukmdelta': 
                self::_connectDelta();
				break;
			case 'wordpress':
				self::_connectWordpress();
				break;
			default:
                self::_connectUKM();
		}
		
		if ( static::$connection->connect_errno ) {
			echo 'Database connection failed.';
			die();
		}
		
		static::$connection->set_charset( static::$charset );
	}
	
	/**
	 * Is database connection set up?
	 *
	 * @return bool
	**/
	public static function connected() {
		return static::$connection !== false;
	}

	/**
	 * Set database name
	 *
	 * @return void
	**/
	public static function setDatabase( $database ) {
		if( $database !== static::$database ) {
			static::$connection = false;
		}
		static::$database = $database;
	}

	/**
	 * PROXY: Set connection charset
	 *
	 * @return MySQLi->set_charset() or bool false
	**/
	public static function setCharset( $charset ) {
		static::$charset = $charset;
		
		if( self::connected() ) {
			return static::$connection->set_charset( static::$charset );
		}
		return false;
	}

	/**
	 * PROXY: Free MySQLi result from memory
	 * Encouraged, but not required
	 *
	 * @return MySQLi_result->free()
	**/
	public static function free_result( $result ) {
		return $result->free();
	}

	/**
	 * PROXY: Real Escape String
	 * 
	 * Proxy function for MySQLi::real_escape_string to make sure it runs with
	 * correct connection charset settings
	**/
	public static function real_escape_string( $value ) {
		if( !self::connected() ) {
			die('Kan ikke kjøre real_escape_string uten databasetilkobling');
		}
		return static::$connection->real_escape_string( $value );
	}

	/**
	 * PROXY: Query
	 * Run MySQLi query
	**/
	public static function query( $query ) {
		static::$hasError = false;
		
		$result = static::$connection->query( $query );
		if( !empty( static::$connection->error ) ) {
			static::$hasError = true;
        }
		return $result;
	}

	/**
	 * Was query unsuccesful?
	 *
	 * @return bool query_encountered_error
	**/
	public static function wasError() {
		return static::$hasError;
	}

	/**
	 * Get query error
	 *
	 * @return string MySQLi->error
	**/
	public static function getError() {
		return static::$connection->error;
	}

	/**
	 * Get insert id
	 *
	 * @return int insert_id
	**/
	public static function getInsertId() {
		if( static::$connection->insert_id == 0 ) {
			throw new Exception('System-error: Insert ID == 0 (database-spørringen feilet) => ' . self::getError() );
		}
		return static::$connection->insert_id;
	}

	/**
	 * Get num affected rows
	 *
	 * @return int affected_rows
	**/
	public static function getAffectedRows() {
		return static::$connection->affected_rows;
    }
    
    /**
     * Connect to UKMdelta database
     * Selects read / write access by parent class constant
     */
    private static function _connectDelta() {
        // Initiate with write access (SQLins, SQLdel, SQLwrite)
        if( self::_hasWriteAccess() ) {
            return self::_init( UKM_DELTA_DB_HOST, UKM_DELTA_DB_NAME, UKM_DELTA_DB_WRITE_USER, UKM_DELTA_DB_WRITE_PASSWORD );
        }
        // Initiate with read access (SQL)
		return self::_init(UKM_DELTA_DB_HOST, UKM_DELTA_DB_NAME, UKM_DELTA_DB_USER, UKM_DELTA_DB_PASSWORD );
    }

    /**
     * Connect to UKMd database (old ss3)
     * Selects read / write access by parent class constant
     */
    private static function _connectUKM() {
        // Initiate with write access (SQLins, SQLdel, SQLwrite)
        if( self::_hasWriteAccess() ) {
            return self::_init(UKM_DB_HOST, UKM_DB_NAME, UKM_DB_WRITE_USER, UKM_DB_WRITE_PASSWORD );
        }
        // Initiate with read access (SQL)
		return self::_init( UKM_DB_HOST, UKM_DB_NAME, UKM_DB_USER, UKM_DB_PASSWORD );
	}
	
	private static function _connectWordpress() {
		// Initiate with write access (SQLins, SQLdel, SQLwrite)
		if( self::_hasWriteAccess() ) {
			return self::_init( UKM_WP_DB_HOST, UKM_WP_DB_NAME, UKM_WP_DB_WRITE_USER, UKM_WP_DB_WRITE_PASSWORD );
		}
        // Initiate with read access (SQL)
		return self::_init( UKM_WP_DB_HOST, UKM_WP_DB_NAME, UKM_WP_DB_USER, UKM_WP_DB_PASSWORD );
	}


	private static function _init( $host, $database, $user, $password ) {
		static::$connection = new mysqli(
			$host,
			$user,
			$password,
			$database
		);
		return true;
	}

    private static function _hasWriteAccess() {
        return get_called_class()::WRITE_ACCESS;
    }
}
