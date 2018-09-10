<?php
/* 
Part of: UKM Norge core
Description: SQL-klasse for bruk av SQL-sp¿rringer opp mot UKM-databasen.
Author: UKM Norge / M Mandal
Maintainer: UKM Norge / A Hustad & M Mandal
Version: 4.0 
Comments: Now utilizes mysqli instead of mysql
*/

/**********************************************************************************************
 * DATABASE CONNECTION CLASS
 * Helper class for all SQL classes, managing the connection
**/
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
	 * Defaults to database name null, which means SS3
	 *
	 * @return void
	**/
	static function connect() {
		switch ( self::$database ) {
			case 'ukmdelta': 
				self::$connection = new mysqli(
					UKM_DELTA_DB_HOST, 
					UKM_DELTA_DB_USER, 
					UKM_DELTA_DB_PASSWORD, 
					UKM_DELTA_DB_NAME
				);
				break;

			default:
				self::$connection = new mysqli(
					UKM_DB_HOST, 
					UKM_DB_USER, 
					UKM_DB_PASSWORD,
					UKM_DB_NAME
				);
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

		if( !$result ) {
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
			throw new Exception('System-error: Insert ID == 0 (database-spørringen feilet)');
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
}


/**********************************************************************************************
 * DATABASE SQL COMMON FUNCTIONS CLASS
 * Helper class for all SQL classes, containing common functions across SQL classes
 * Is extended by SQL classes
**/
abstract class SQL_common {
	public $showError = false;
	public $query = null;
	public $real_query = null;

	/**
	 * Set charset of connection
	 *
	 * Run by $sql->charset('utf-8')
	 * Internal: will call DB::setCharset( charset ) after correcting
	 * charset string
	 *
	 * @return void
	**/
	public function charset( $charset='UTF-8' ) {
		switch( $charset ) {
			case 'UTF-8':
			case 'UTF8':
				$charset = 'utf8';
				break;
		}
		DB::setCharset( $charset );
	}

	/**
	 * Show errors
	 *
	 * @return void
	**/
	public function showError() {
		$this->showError = true;
	}

	/**
	 * Debug query
	 *
	 * Will return the query post _prepare $query
	 *
	 * @return string executable $query
	**/
	public function debug() {
		$this->_prepare();
		return $this->real_query . '<br />';
	}

	/**
	 * Return last insert ID
	 *
	 * @return int insert_id
	**/		 
	public function insid() {
		return DB::getInsertId();
	}

	/**
	 * Sanitize a string
	 * PROXY: DB::real_escape_string
	 *
	 * @return string sanitized value
	**/
	public function sanitize( $value ) {
		return DB::real_escape_string( trim( strip_tags( $value ) ) );
	}

}


/**********************************************************************************************
 * READONLY SQL CLASS
 * Used for all select queries, and does not have write permissions as an extra security measure
 *
**/
if(!class_exists('SQL')) {
	require_once('UKMconfig.inc.php');
	
	class SQL extends SQL_common {
		private $key_value_map = null;
		private $database = null;

		/**
		 * Create select query
		 *
		 * Will substitute all #key with mapped values from $key_val_map
		 * QUERY: SELECT * FROM `table` WHERE `col_name` = '#value'
		 * KEY_VAL_MAP: [value => real_value]
		 * REAL_QUERY = SELECT * FROM `table` WHERE `col_name` = 'real_value'
		 * 
		 * @param string query
		 * @param array map[ key => value ]
		 * @param string database name
		 *
		 * @return SQL object
		**/
		function __construct( $query, $key_val_map=array(), $db_name=null) {
			DB::setDatabase( $db_name );
			$this->query = $query;
			$this->key_value_map = $key_val_map;
		}

		/**
		 * DEPRECATED: use showError
		 * See SQL_common
		**/
		function error() {
			return $this->showError();
		}

		public static function fetch( $result ) {
			if( $result === false ) {
				return false;
			}
			if( self::_isMysqliResult($result) ) {
				return mysqli_fetch_assoc( $result );
			}
			return mysql_fetch_assoc( $result );
		}

		public static function numRows( $result ) {
			if( $result === false ) {
				return false;
			}
			if( self::_isMysqliResult($result) ) {
				return mysqli_num_rows( $result );
			}
			return SQL::numRows( $result );
		}

		private static function _isMysqliResult( $result ) {
			return is_object( $result );
		}

		/**
		 * Prepare and run query
		**/
		function run( $return_value='resource', $return_value_id='') {
			// Kjør spørring
			$result = DB::query( $this->_prepare() );

			if( $this->showError && DB::wasError() ) {
				echo DB::getError();
				die();
			}

			if( DB::wasError() ) {
				return false;
			}
			
			switch( $return_value ) {
				case 'field':
					$return = $result->fetch_array( MYSQLI_ASSOC )[ $return_value_id ];
					DB::free_result( $result );
					break;
				case 'array':
					$return = $result->fetch_array( MYSQLI_ASSOC );
					DB::free_result( $result );
					break;
				default:
					$return = $result;
					break;
			}
			return $return;
		}

		/**
		 * Create SQL query from parameters
		 * Stores query in $this->real_query
		 *
		 * Establishes DB connection to ensure correct
		 * handling of MySQLi charset when running real_escape_string
		 *
		 * @return string SQL query ($this->real_query)
		**/
		public function _prepare() {
			if( !DB::connected() ) {
				DB::connect();
			}

			$query = $this->query;
			foreach( $this->key_value_map as $key => $value ) {
				$query = str_replace(
					'#'.$key, 
					$this->sanitize( $value ),
					$query
				);
			}
			
			$this->real_query = $query;
			return $this->real_query;
		}
	}
}


/**********************************************************************************************
 * SQL DELETION CLASS
 * Used only to delete stuff.
**/
if(!class_exists('SQLdel')) {
	class SQLdel extends SQL_common {
		public $query = null;
		private $table = null;
		private $key_value_map = null;

		/**
		 * Create deletion query
		 *
		 * @param string table_name
		 * @param array map[ key => value ]
		 * @param string database name
		**/
		function __construct( $table, $where_key_val_map, $db_name=null ) {
			DB::setDatabase( $db_name );
			$this->table = $table;
			$this->key_value_map = $where_key_val_map;
		}

		/**
		 * Prepare and run query
		 *
		 * @return integer affected_rows
		**/
		function run() {
			$result = DB::query( $this->_prepare() );
			return DB::getAffectedRows();
		}

		/**
		 * Create SQL query from parameters
		 * Stores query in $this->real_query
		 *
		 * Establishes DB connection to ensure correct
		 * handling of MySQLi charset when running real_escape_string
		 *
		 * @return string SQL query ($this->real_query)
		**/
		public function _prepare() {
			if( !DB::connected() ) {
				DB::connect();
			}

			$wheres = '';

			$num_conditions = sizeof( $this->key_value_map );
			$loop_index = 0;

			foreach( $this->key_value_map as $column => $val ) {
				$loop_index++;

				// If is numeric, add without quotes, else quote it
				$wheres .= "`". $this->sanitize( $column ) ."` = ".
					(intval( $val ) > 0 ? $val : "'". $this->sanitize( $val ) ."'");

				if( $loop_index < $num_conditions ) {
					$wheres .= ' AND ';
				}
			}
			
			$this->real_query = 'DELETE FROM `'. $this->sanitize( $this->table ) .'` WHERE '. $wheres .';';
			return $this->real_query;
		}
	}
}


/**********************************************************************************************
 * SQL INSERTION AND UPDATE CLASS
 * Used only to insert stuff, never from a query.
**/
if(!class_exists('SQLins')) {
	class SQLins extends SQL_common {
		var $insert_keys = array();
		var $insert_values = array();
		var $_error_log = true;
		
		/**
		 * Has changes
		 * Are there added any key/value pairs. 
		 * Sometimes necessary when adding values programmatically, and 
		 * running query with no values will return fail (and may cause unwanted script exits)
		 *
		 * @return bool
		**/
		function hasChanges() {
			return sizeof( $this->keys ) > 0;
		}

		
		/**
		 * Create insert / update query
		 *
		 * If no where-parameter is empty array, it creates an insert query,
		 * else, update-query
		 *
		 * @param string table_name
		 * @param array map[ key => value ]
		 * @param string database name
		**/
		function __construct($table, $where_key_val_map=array(), $db_name = null) {
			DB::setDatabase( $db_name );
			
			$this->table = $table;
			$this->type = sizeof( $where_key_val_map ) > 0 ? 'update' : 'insert';
			$this->key_value_map = $where_key_val_map;
		}

		/**
		 * Add value to a column
		 *
		 * @param string $column
		 * @param string $value
		**/
		function add( $column, $value ) {
			$this->insert_keys[] = $column;
			$this->insert_values[] = $value;
		}
		
		/**
		 * Disable error logging if there is an error
		 *
		 * @return void
		**/
		function disableErrorLog() {
			$this->_error_log = false;
		}

		/**
		 * Prepare and run query
		 *
		 * @return integer affected_rows
		**/
		function run() {
			$result = DB::query( $this->_prepare() );
			
			if( DB::wasError() && $this->_error_log ) {
				error_log('SQL.class: '. DB::getError() );
			}
			if( $this->type == 'insert' ) {
				return DB::getInsertId();
			}
			return DB::getAffectedRows();
		}
		
		/**
		 * Prepare the query of given type
		 *
		 * @return string SQL query
		**/
		public function _prepare() {
			if( !DB::connected() ) {
				DB::connect();
			}

			if( $this->type == 'update' ) {
				return $this->_prepare_update();
			}
			// equals: if( $this->type == 'insert' ) {
			return $this->_prepare_insert();
		}

		/**
		 * Generate update query
		 * 
		 * @return string SQL query
		**/
		public function _prepare_update() {
			$this->real_query = 'UPDATE `'.$this->table.'` SET ';
			
			// Add the new values to be set
			for( $i=0; $i < sizeof( $this->insert_keys ); $i++) {
				$this->real_query .= 
					"`". $this->sanitize( $this->insert_keys[$i] ) .
					"` = '". $this->sanitize( $this->insert_values[$i] ) ."', ";
			}
			// Remove the last comma
			$this->real_query = substr(
				$this->real_query, 
				0, 
				(strlen($this->real_query)-2)
			);

			// Create the where-part of the query
			$this->real_query .= ' WHERE ';
			foreach( $this->key_value_map as $key => $val) {
				$this->real_query .= "`". $this->sanitize($key) ."`='". $this->sanitize( $val ) ."' AND ";
			}
			// Remove last 5 chars (' AND ')
			$this->real_query = substr(
				$this->real_query, 
				0, 
				(strlen($this->real_query)-5)
			);
			
			return $this->real_query;
		}

		/**
		 * Generate insert query
		 * 
		 * @return string SQL query
		**/
		public function _prepare_insert() {
			$keys = '';
			$values = '';
			
			// Temp-store keys and values in separate strings since
			// we cannot concatenate directly to query
			if ( sizeof( $this->insert_keys ) > 0 && sizeof( $this->insert_values ) > 0 ) {
				for( $i=0; $i < sizeof( $this->insert_keys ); $i++ ) {
					$keys .= '`'. $this->sanitize( $this->insert_keys[$i] ) .'`, ';
					$values .= "'". $this->sanitize( $this->insert_values[$i] ) ."', ";
				}
			}

			// Remove the last $key list comma
			$keys = substr(
				$keys, 
				0, 
				(strlen($keys)-2)
			);

			// Remove the last $value list comma
			$values = substr(
				$values,
				0,
				(strlen($values)-2)
			);
			
			$this->real_query = 'INSERT IGNORE INTO `'.$this->table.'` ('. $keys .') VALUES ('. $values .');';
			return $this->real_query;
		}

		/**
		 * DEPRECATED: use showError
		 * See SQL_common
		**/
		function error() {
			return $this->showError();
		}
	}
}
?>
