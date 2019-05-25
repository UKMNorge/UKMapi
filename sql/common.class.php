<?php
/**********************************************************************************************
 * DATABASE SQL COMMON FUNCTIONS CLASS
 * Helper class for all SQL classes, containing common functions across SQL classes
 * Is extended by SQL classes
**/
abstract class SQLcommon {
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
				$charset = 'utf8mb4';
				break;
		}
		
		if( static::WRITE_ACCESS_DATABASE ) {
			DBwrite::setCharset( $charset );
		} else {
			DBread::setCharset( $charset );
		}
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
	 * Set aktiv database
	 *
	 * @param string $database
	 * @return void
	 */
	public function setDatabase( $database ) {
		if( static::WRITE_ACCESS_DATABASE ) {
			return DBwrite::setDatabase( $database );
		}
		return DBread::setDatabase( $database );
	}


	/**
	 * Get current error
	 */
	public function getError() {
		if( static::WRITE_ACCESS_DATABASE ) {
			return DBwrite::getError();
		}
		return DBread::getError();
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
		return DBwrite::getInsertId();
	}

	/**
	 * Sanitize a string
	 * PROXY: DB::real_escape_string
	 *
	 * @return string sanitized value
	**/
	public function sanitize( $value ) {
		if( static::WRITE_ACCESS_DATABASE ) {
			return DBwrite::real_escape_string( trim( strip_tags( $value ) ) );
		}
		return DBread::real_escape_string( trim( strip_tags( $value ) ) );
	}

}