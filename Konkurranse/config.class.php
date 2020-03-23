<?php

use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Update;

require_once('UKM/_orm.collection.php');
	
class Config extends Coll {
	const TABLE_NAME = 'konkurranse_config';
	const PARENT_FIELD = 'name';

	public static function getTableName() {
		return self::TABLE_NAME;
	}

	public static function set( $name, $value ) {
		try {
			self::_create( [
				'name' => $name,
				'value' => $value,
				]
			);
		} catch( Exception $e ) {
			if( $e->getCode() == 901001 ) {
				return self::update( $name, $value );
			}
			throw $e;
		}
		return $value;
	}
	
	public static function update( $name, $value ) {
		$sqlIns = new Update(
			self::getTableName(),
			[
				'name' => $name
			]
		);
		$sqlIns->add('value', $value );
		$sqlIns->run();
		return $value;
	}

	public static function get( $name ) {
		$sql = new Query(
			"SELECT * FROM `". self::TABLE_NAME ."` WHERE `name` = '#id' ",
			['id' => $name]
		);
		return $sql->run('field', 'value');
	}
	
}