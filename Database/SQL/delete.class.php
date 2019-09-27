<?php

namespace UKMNorge\Database\SQL;

require_once('UKM/Database/SQL/common.class.php');

/**********************************************************************************************
* SQL DELETION CLASS
* Used only to delete stuff.
**/
class Delete extends SQLcommon {
    const WRITE_ACCESS_DATABASE = true;
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
        DBwrite::setDatabase( $db_name );
        $this->table = $table;
        $this->key_value_map = $where_key_val_map;
    }

    /**
        * Prepare and run query
        *
        * @return integer affected_rows
    **/
    function run() {
        $result = DBwrite::query( $this->_prepare() );
        return DBwrite::getAffectedRows();
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
        if( !DBwrite::connected() ) {
            DBwrite::connect();
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