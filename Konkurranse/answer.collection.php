<?php

use UKMNorge\Database\SQL\Query;

require_once('UKM/_orm.collection.php');
require_once('UKM/Konkurranse/answer.class.php');
	
class AnswerColl extends Coll {
	const TABLE_NAME = Answer::TABLE_NAME;
	public static $models = null;

	public static function create( $name, $sporsmalId ) {
		$object = self::_create( [
			'name' => $name,
			'sporsmal_id' => $sporsmalId,
			]
		);
		return $object;
	}
	
	public static function getByQuestionAndUser( $question_id, $mobile ) {
		$child = get_called_class();
		$child::$models = [];

		if( is_object( $question_id ) ) {
			$question_id = $question_id->getId();
		}

		$sql = new Query(
			"SELECT * FROM `". self::TABLE_NAME ."`
			WHERE `sporsmal_id` = '#question_id'
			AND `mobil` = '#mobil'",
			[
				'question_id' => $question_id,
				'mobil' => $mobile
			]
		);
		$row = $sql->run('array');
		if( !$row ) {
			return false;
		}

		$object_class = str_replace('Coll', '', $child);
		return new $object_class( $row );
	}
	
	public static function getByQuestion( $question_id ) {
		$child = get_called_class();
		$child::$models = [];

		if( is_object( $question_id ) ) {
			$question_id = $question_id->getId();
		}

		$sql = new Query(
			"SELECT * FROM `". self::TABLE_NAME ."`
			WHERE `sporsmal_id` = '#question_id'",
			[
				'question_id' => $question_id,
			]
		);
		$row = $sql->run('array');
		if( !$row ) {
			return false;
		}

		$object_class = str_replace('Coll', '', $child);
		return new $object_class( $row );
	}

	public static function getAllUniqueByQuestion( $question_id ) {
		$child = get_called_class();

		$sql = new Query(
			"SELECT * FROM `". self::TABLE_NAME ."`
			WHERE `sporsmal_id` = '#question_id'
			GROUP BY `svar`",
			[
				'question_id' => $question_id,
			]
		);
		$res = $sql->run();
		
		$models = [];
		while( $row = Query::fetch( $res ) ) {
			$object_class = str_replace('Coll', '', $child);
			$models[] = new $object_class( $row );
		}
		return $models;
	}
}