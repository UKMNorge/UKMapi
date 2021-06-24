<?php

namespace UKMNorge\RFID;

require_once('UKM/Autoloader.php');

class HerdColl extends RFIDColl {
	const TABLE_NAME = Herd::TABLE_NAME;
	public static $models = null;
}