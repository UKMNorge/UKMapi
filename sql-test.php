<?php

ini_set('display_errors', true);
error_reporting(E_ALL);

require_once('UKM/sql.class.php');

// HELPERS
$count = 0;
function title( $title ) {
	global $count;
	$count++;
	echo '<h3>TEST '. $count .': '. $title .'</h3>';
}

function value( $sql, $res ) {
	echo $sql->debug();
	echo '<br />';
	var_dump( $res );
}

echo '<h1>Hent fra smartukm_place</h1>';
// TEST 1
$sql = new SQL("SELECT * FROM `smartukm_place` ORDER BY `pl_id` DESC");
$res = $sql->run('field', 'pl_name');

title('SQL::run(\'field\', \'pl_name\')');
value( $sql, $res );

// TEST 2
$sql = new SQL("SELECT * FROM `smartukm_place` ORDER BY `pl_id` DESC");
$res = $sql->run('array');

title('SQL::run(\'array\')');
value( $sql, $res );

// TEST 3
$sql = new SQL("SELECT * FROM `smartukm_place` ORDER BY `pl_id` DESC");
$res = $sql->run();

title('SQL::run()');
$limiter = 0;
while( $row = mysqli_fetch_assoc( $res ) ) {
	$limiter++;
	if( $limiter > 3 ) {
		break;
	}
	value( $sql, $row );
}

echo '<h1>Sett inn i test</h1>';
// TEST 4
$sql = new SQLins('test');
$res = $sql->run();

title('SQLins(test)');
value( $sql, $res );

// TEST 5
$sql = new SQLins('ikke-eksisterende-tabell');
$res = $sql->run();

title('SQLins(ikke-eksisterende-tabell)');
value( $sql, $res );

// TEST 6
$sql = new SQLins('test');
$sql->add('felt', 'verdi');
$res = $sql->run();

title('SQLins(test)');
value( $sql, $res );

// TEST 7
$sql = new SQLins('test');
$sql->add('felt', "verdi' to");
$res = $sql->run();

title('SQLins(test)');
value( $sql, $res );


// SETT INN OG VELG OG SLETT OG SÅNN
echo '<h1>Sett inn, og slett</h1>';
// TEST 8
$sql = new SQLins('test');
$sql->add('felt', "slettmeg");
$res = $sql->run();

title('SQLins(test)');
value( $sql, $res );

// TEST 9
$sql = new SQL("SELECT * FROM `test` WHERE `felt` = '#verdi'", ['verdi'=>'slettmeg']);
$res = $sql->run('array');

title('SQL->run(array)');
value( $sql, $res );

// TEST 10
$sql = new SQLdel('test', ['felt'=>'slettmeg']);
$res = $sql->run();

title('SQL->run()');
value( $sql, $res );

// TEST 11
$sql = new SQL("SELECT * FROM `test` WHERE `felt` = '#verdi'", ['verdi'=>'slettmeg']);
$res = $sql->run('array');

title('SQL->run(array)');
value( $sql, $res );


// SETT INN OG OPPDATER
echo '<h1>Sett inn, og oppdater verdi</h1>';
// TEST 12
$sql = new SQLins('test');
$sql->add('felt', "oppdatermeg");
$inserted_id = $sql->run();

title('SQLins(test)');
value( $sql, $inserted_id );

// TEST 13
$sql = new SQL("SELECT * FROM `test` WHERE `felt` = '#verdi'", ['verdi'=>'oppdatermeg']);
$res = $sql->run('array');

title('SQL->run(array)');
value( $sql, $res );

// TEST 14
$sql = new SQLins('test', ['felt'=>'oppdatermeg']);
$sql->add('felt', "partymøude - i'm upgraded!");
$res = $sql->run();

title('SQL->run()');
value( $sql, $res );

// TEST 15
$sql = new SQL("SELECT * FROM `test` WHERE `id` = '#verdi'", ['verdi'=>$inserted_id]);
$res = $sql->run('array');

title('SQL->run(array)');
value( $sql, $res );

// TEST 16
$sql = new SQL("SELECT * FROM `test` WHERE `felt` = '#verdi'", ['verdi'=>'oppdatermeg']);
$res = $sql->run('array');

title('SQL->run(array)');
value( $sql, $res );
