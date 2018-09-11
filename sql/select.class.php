<?php
/**********************************************************************************************
 * READONLY SQL CLASS
 * Used for all select queries, and does not have write permissions as an extra security measure
 *
**/
class SQL extends SQLcommon {
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
        DBread::setDatabase( $db_name );
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
        $result = DBread::query( $this->_prepare() );

        if( $this->showError && DBread::wasError() ) {
            echo DBread::getError();
            die();
        }

        if( DBread::wasError() ) {
            return false;
        }
        
        switch( $return_value ) {
            case 'field':
                $return = $result->fetch_array( MYSQLI_ASSOC )[ $return_value_id ];
                DBread::free_result( $result );
                break;
            case 'array':
                $return = $result->fetch_array( MYSQLI_ASSOC );
                DBread::free_result( $result );
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
        if( !DBread::connected() ) {
            DBread::connect();
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