<?php
namespace UKMNorge\RFID;

require_once('orm.collection.php');
require_once('area.class.php');
	
class AreaColl extends RFIDColl {
	const TABLE_NAME = Area::TABLE_NAME;
	public static $models = null;
}