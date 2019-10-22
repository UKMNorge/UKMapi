<?php

namespace UKMNorge\Database\SQL;

require_once('UKMconfig.inc.php');
require_once('UKM/Autoloader.php');

/**********************************************************************************************
 * DATABASE SQL COMMON FUNCTIONS CLASS
 * Helper class for all SQL classes, containing common functions across SQL classes
 * Is extended by SQL classes
**/
abstract class Common {
	public $showError = false;
	public $query = null;
    public $real_query = null;
    public $html_allowed = [];

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
	public static function setDatabase( $database ) {
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
	 * sanitize a string
	 * PROXY: DB::real_escape_string
	 *
     * @param String $value
     * @param Bool $strip_tags
	 * @return string sanitized value
	**/
	public function sanitize( $value, $strip_tags=true ) {
        // Standard-format for DateTime i databasen
        // Skal det lagres i annet format, send inn data som String
        if( is_object( $value ) && get_class( $value ) == 'DateTime' ) {
            $value = $value->format('Y-m-d H:i:s'); // Fordi databasen lagrer datoer som int
        }
        
        // Standard-format for DateTime i databasen
        // Skal det lagres i annet format, send inn data som String        
        if( is_bool( $value ) ) {
            $value = $value ? 'true' : 'false';
        }

        if( $strip_tags ) {
            $value = strip_tags( $value );
        }

        if( static::WRITE_ACCESS_DATABASE ) {
			return DBwrite::real_escape_string( trim( $value ) );
		}
		return DBread::real_escape_string( trim( $value ) );
	}

    /**
     * Sanitize a value, but possibly allow HTML, based on key
     *
     * @param String $key
     * @param String $value
     * @return void
     */
    public function sanitizeValue( $key, $value ) {
        return $this->sanitize(
            $value,
            !$this->canContainHtml( $key )
        );
    }

    public function allowHtmlFor( $key ) {
        $this->html_allowed[] = $key;
    }
    
    public function canContainHtml( $key ) {
        return in_array( $key, $this->html_allowed );
    }
}