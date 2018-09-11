<?php
/**********************************************************************************************
 * DATABASE CONNECTION CLASS
 * Helper class for all SQL classes, managing the connection
**/

class DBread extends DB {
    const WRITE_ACCESS = false;
}
class DBwrite extends DB {
    const WRITE_ACCESS = true;
}

class DB {
	private static $connection = false;
	private static $database = null;
	private static $charset = 'utf8';
	private static $hasError = false;
	
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
        switch ( self::$database ) {
            case 'ukmdelta': 
                self::_connectDelta();
				break;
            default:
                self::_connectUKM();
		}
		
		if ( self::$connection->connect_errno ) {
			echo 'Database connection failed.';
			die();
		}
		
		self::$connection->set_charset( self::$charset );
	}
	
	/**
	 * Is database connection set up?
	 *
	 * @return bool
	**/
	public static function connected() {
		return self::$connection !== false;
	}

	/**
	 * Set database name
	 *
	 * @return void
	**/
	public static function setDatabase( $database ) {
		self::$database = $database;
	}

	/**
	 * PROXY: Set connection charset
	 *
	 * @return MySQLi->set_charset() or bool false
	**/
	public static function setCharset( $charset ) {
		self::$charset = $charset;
		
		if( self::connected() ) {
			return self::$connection->set_charset( self::$charset );
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
		return self::$connection->real_escape_string( $value );
	}

	/**
	 * PROXY: Query
	 * Run MySQLi query
	**/
	public static function query( $query ) {
		self::$hasError = false;
		
		$result = self::$connection->query( $query );
		if( !empty( self::$connection->error ) ) {
			self::$hasError = true;
        }

		return $result;
	}

	/**
	 * Was query unsuccesful?
	 *
	 * @return bool query_encountered_error
	**/
	public static function wasError() {
		return self::$hasError;
	}

	/**
	 * Get query error
	 *
	 * @return string MySQLi->error
	**/
	public static function getError() {
		return self::$connection->error;
	}

	/**
	 * Get insert id
	 *
	 * @return int insert_id
	**/
	public static function getInsertId() {
		if( self::$connection->insert_id == 0 ) {
			throw new Exception('System-error: Insert ID == 0 (database-spørringen feilet) => ' . self::getError() );
		}
		return self::$connection->insert_id;
	}

	/**
	 * Get num affected rows
	 *
	 * @return int affected_rows
	**/
	public static function getAffectedRows() {
		return self::$connection->affected_rows;
    }
    
    /**
     * Connect to UKMdelta database
     * Selects read / write access by parent class constant
     */
    private static function _connectDelta() {
        // Initiate with write access (SQLins, SQLdel, SQLwrite)
        if( self::_hasWriteAccess() ) {
            self::$connection = new mysqli(
                UKM_DELTA_DB_HOST, 
                UKM_DELTA_DB_WRITE_USER, 
                UKM_DELTA_DB_WRITE_PASSWORD, 
                UKM_DELTA_DB_NAME
            );
        }
        // Initiate with read access (SQL)
        else {
            self::$connection = new mysqli(
                UKM_DELTA_DB_HOST, 
                UKM_DELTA_DB_USER, 
                UKM_DELTA_DB_PASSWORD, 
                UKM_DELTA_DB_NAME
            );
        }
    }

    /**
     * Connect to UKMd database (old ss3)
     * Selects read / write access by parent class constant
     */
    private static function _connectUKM() {
        // Initiate with write access (SQLins, SQLdel, SQLwrite)
        if( self::_hasWriteAccess() ) {
            self::$connection = new mysqli(
                UKM_DB_HOST, 
                UKM_DB_WRITE_USER, 
                UKM_DB_WRITE_PASSWORD,
                UKM_DB_NAME
            );
        }
        // Initiate with read access (SQL)
        else {
            self::$connection = new mysqli(
                UKM_DB_HOST, 
                UKM_DB_USER, 
                UKM_DB_PASSWORD,
                UKM_DB_NAME
            );
        }
    }

    private static function _hasWriteAccess() {
        return get_called_class()::WRITE_ACCESS;
    }
}