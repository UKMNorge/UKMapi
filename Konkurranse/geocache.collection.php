<?php


require_once('UKM/_orm.collection.php');
require_once('UKM/Konkurranse/geocache.class.php');
	
class GeocacheColl extends Coll {
	const TABLE_NAME = Geocache::TABLE_NAME;
	public static $models = null;

	public static function create( $name, $type ) {
		$object = self::_create( [
			'code' => substr( sha1( time() . $name ), 0, 8 ),
			'navn' => $name,
			'type' => $type,
			]
		);
		return $object;
	}
	
	public function delete( $geocache ) {
		self::_delete( [
			'id' => $geocache->getId()
			]
		);
	}
}