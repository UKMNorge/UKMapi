<?php

namespace UKMNorge\RFID;

require_once('UKM/Autoloader.php');

class AreaColl extends ORMCollection {
	const TABLE_NAME = Area::TABLE_NAME;
	public static $models = null;
}