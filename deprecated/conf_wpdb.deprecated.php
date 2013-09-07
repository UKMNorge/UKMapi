<?php
define('DB_NAME', 'ukmno_wp2012');
/** MySQL-databasens brukernavn */
define('DB_USER', 'ukmno_wp2012');
/** MySQL-databasens passord */
define('DB_PASSWORD', 'd,2]xylwo4gH7010');
/** MySQL-tjener */
define('DB_HOST', 'localhost');

$marius_db = @mysql_connect(DB_HOST,DB_USER,DB_PASSWORD) or 
		mail('marius@ukm.no', 'API-feil!', 'Feil WPDB-config i http://api.ukm.no/');
@mysql_select_db(DB_NAME, $marius_db) or 
		mail('marius@ukm.no', 'API-feil!', 'Feil WPDB-config i  http://api.ukm.no/');
$db = $marius_db;
?>