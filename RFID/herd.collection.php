<?php

namespace UKMNorge\RFID;

require_once(UKMRFID .'/models/orm.collection.php');
require_once(UKMRFID .'/models/herd.class.php');
	
class HerdColl extends RFIDColl {
	const TABLE_NAME = Herd::TABLE_NAME;
	public static $models = null;
}