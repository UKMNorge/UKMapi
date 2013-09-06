<?php
define('DB_NAME', 'ukmno_ss3');
/** MySQL-databasens brukernavn */
define('DB_USER', 'ukmno_ukmno');
/** MySQL-databasens passord */
define('DB_PASSWORD', '62aGrKeYeZT4');
/** MySQL-tjener */
define('DB_HOST', 'localhost');

$ukmdb = @mysql_connect(DB_HOST,DB_USER,DB_PASSWORD) or 
		mail('marius@ukm.no', 'API-feil!', 'Feil SS3-config i http://api.ukm.no/');
@mysql_select_db(DB_NAME, $ukmdb) or 
		mail('marius@ukm.no', 'API-feil!', 'Feil SS3-config i  http://api.ukm.no/');

?>