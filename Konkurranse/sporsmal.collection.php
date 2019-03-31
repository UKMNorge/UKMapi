<?php


require_once('UKM/_orm.collection.php');
require_once('UKM/Konkurranse/sporsmal.class.php');
	
class SporsmalColl extends Coll {
	const TABLE_NAME = Sporsmal::TABLE_NAME;
	public static $models = null;

	public static function create( $name, $type, $answerType='tekst' ) {
		$object = self::_create( [
			'name' => $name,
			'type' => $type,
			'answer_type' => $answerType,
			]
		);
		return $object;
	}
	
	public function delete( $sporsmal ) {
		self::_delete( [
			'id' => $sporsmal->getId()
			]
		);
	}
}