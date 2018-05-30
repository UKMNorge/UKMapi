<?php
namespace UKMNorge\RFID;

require_once(UKMRFID .'/models/orm.collection.php');
require_once(UKMRFID .'/models/area.class.php');
	
class AreaColl extends RFIDColl {
	const TABLE_NAME = Area::TABLE_NAME;
	public static $models = null;
}