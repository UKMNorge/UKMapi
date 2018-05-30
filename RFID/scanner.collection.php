<?php

namespace UKMNorge\RFID;

require_once(UKMRFID .'/models/orm.collection.php');
require_once(UKMRFID .'/models/scanner.class.php');
	
class ScannerColl extends RFIDColl {
	const TABLE_NAME = Scanner::TABLE_NAME;
	public static $models = null;
}