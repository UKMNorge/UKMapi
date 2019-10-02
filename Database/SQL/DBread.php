<?php

namespace UKMNorge\Database\SQL;

require_once('UKM/Autoloader.php');

/**********************************************************************************************
 * DATABASE CONNECTION CLASS
 * Helper class for all SQL classes, managing the connection
**/

class DBread extends DB {
	const WRITE_ACCESS = false;
	protected static $connection = false;
	protected static $database = null;
	protected static $charset = 'utf8mb4';
	protected static $hasError = false;
}