<?php

namespace UKMNorge\Database\SQL;
use Exception;

require_once('UKM/Database/SQL/common.class.php');
require_once('UKM/Database/SQL/insert.class.php');


class Update extends Insert {
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
    function __construct( String $table,  Array $where_key_val_map, $db_name = null) {
        DBwrite::setDatabase( $db_name );
        
        $this->table = $table;
        $this->type = 'update';
        $this->key_value_map = $where_key_val_map;
    }
}