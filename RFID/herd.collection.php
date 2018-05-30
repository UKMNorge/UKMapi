<?php

namespace UKMNorge\RFID;

require_once('orm.collection.php');
require_once('herd.class.php');
	
class HerdColl extends RFIDColl {
	const TABLE_NAME = Herd::TABLE_NAME;
	public static $models = null;
}