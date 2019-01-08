<?php
/**********************************************************************************************
 * SQL INSERTION AND UPDATE CLASS
 * Used only to insert stuff, never from a query.
**/
class SQLins extends SQLcommon {
    const WRITE_ACCESS_DATABASE = true;
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
        return is_array( $this->insert_keys ) && sizeof( $this->insert_keys ) > 0;
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
        DBwrite::setDatabase( $db_name );
        
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
        $result = DBwrite::query( $this->_prepare() );
        
        if( DBwrite::wasError() && $this->_error_log ) {
            $exception = new Exception();
            error_log('SQL.insert '. DBwrite::getError() );
            error_log('  - QUERY: '. $this->real_query );
            error_log('  - TRACE: '.  $exception->getTraceAsString() );
        }
        
        if( DBwrite::wasError() ) {
            return false;
        }

        if( $this->type == 'insert' ) {
            return DBwrite::getInsertId();
        }
        return DBwrite::getAffectedRows();
    }
    
    /**
     * Prepare the query of given type
     *
     * @return string SQL query
    **/
    public function _prepare() {
        if( !DBwrite::connected() ) {
            DBwrite::connect();
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