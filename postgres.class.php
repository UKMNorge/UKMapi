<?php

namespace UKMNorge\RFID;
use Exception;
	
class POSTGRES {
	private static $connection = null;
	
	public static function connect( $username, $password, $database) {
		self::$connection = pg_connect("host=localhost dbname=$database user=$username password=$password");
	}
	
	public static function getRow( $query, $parameters=false ) {
		$result = self::getResults( $query, $parameters );
		if( isset( $result[0] ) ) {
			return $result[0];
		}
		throw new Exception('POSTGRES did not return any rows');
	}
	
	public static function getValue( $query, $parameters=false ) {
		$result = self::getRow( $query, $parameters );
		
		if( is_array( $result ) ) {
			return reset( $result );
		}
		
		throw new Exception('POSTGRES did not return any values');
	}
	
	public static function getResults( $query, $parameters=false ) {
		if( null == self::$connection ) {
			throw new Exception('POSTGRES not connected. Please run connect');
		}

		if( $parameters !== false ) {
			$result = pg_query_params( self::$connection, $query, $parameters );
		} else {
			$result = pg_query( self::$connection, $query );
		}
		return pg_fetch_all( $result );
	}
		
	public static function insert( $query, $values ) {
		$result = self::_query( $query, $values );
		return pg_last_oid( $result );
	}
	
	public static function update( $query, $values ) {
		$result = self::_query( $query, $values );
		return $result;
	}
	
	private static function _query( $query, $parameters ) {
		if( null == self::$connection ) {
			throw new Exception('POSTGRES not connected. Please run connect');
		}
		$result = @pg_query_params( self::$connection, $query, $parameters );
		
		if( !$result ) {
			$error = pg_last_error( self::$connection );
			
			if( strpos( $error, 'ERROR: duplicate key' ) === 0 ) {
				throw new Exception( $error, 1 );
			}
			throw new Exception( $error, 0 );
		}
		return $result;
	}

}

/**
	$conn = pg_connect("host=localhost dbname=ukmrfid_db port=5432 user=ukmrfid_user password=39p81AolxYjL");
	$result = pg_query($conn, "select * from areas");
	var_dump(pg_fetch_all($result));
**/