<?php
/**********************************************************************************************
 * SQL CLASS WITH WRITE PRIVILEGES
 * Used for database-altering queries. Will automatically notify support upon run.
 * 
**/
class SQLwrite extends SQLcommon {
    private $key_value_map = null;
    private $database = null;

    /**
     * Create query
     *
     * Will substitute all #key with mapped values from $key_val_map
     * QUERY: ALTER TABLE `table` ADD `#col_name` INTEGER NOT NULL
     * KEY_VAL_MAP: [col_name => real_column_name]
     * REAL_QUERY = ALTER TABLE `table` ADD `real_column_name` INTEGER NOT NULL
     * 
     * @param string query
     * @param array map[ key => value ]
     * @param string database name
     *
     * @return SQL object
    **/
    function __construct( $query, $key_val_map=array(), $db_name=null) {
        DBwrite::setDatabase( $db_name );
        $this->query = $query;
        $this->key_value_map = $key_val_map;
    }

    /**
     * Prepare and run query
    **/
    function run() {
        require_once('UKM/mail.class.php');
        $query = $this->_prepare();

        $melding = '<p><b>Dette er et automatisk varsel fra SQLwrite.class:</b></p>'.
            '<p>Følgende potensielt farlige spørring har blitt utført med skriverettigheter på databasen.</p>'.
            '<p><b>'. $query .'</b></p>'.
            '<p>Mvh, UKMapi</p>';

        $mail = new UKMMail();
        $res = $mail->to('support@ukm.no')
            ->subject('Potensielt farlig SQL-spørring utført.')
            ->message($melding)
			->setFrom('arrangorsystemet@ukm.no', 'Arrangørsystemet')
            ->ok();

        if( strpos( $_SERVER['HTTP_HOST'], 'ukm.dev' ) !== false ) {
            echo '<h3>DEV ALERT: SQLwrite auto-varsler support@ukm.no. </h3>'.
                '<p>Hadde dette vært i produksjon, ville følgende e-post blitt sendt:'.
                '<pre>'. $melding .'</pre>'.
                '</p>'; 
        }
    
        $result = DBwrite::query( $query );

        if( DBread::wasError() ) {
            return false;
        }
        
        return $result;
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