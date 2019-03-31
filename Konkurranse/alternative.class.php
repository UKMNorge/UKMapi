<?php


require_once('UKM/_orm.class.php');
require_once('UKM/Konkurranse/alternative.class.php');
	
class Alternative extends ORM {
	const TABLE_NAME = 'konkurranse_alternativ';
	const PARENT_FIELD = 'sporsmal_id';
	
	var $sporsmalId = null;
	var $name = null;
	var $count = null;
	var $answers = null;
	
	public static function getTableName() {
		return self::TABLE_NAME;
	}
	public static function getParentField() {
		return self::PARENT_FIELD;
	}
	
	public function populate( $row ) {
		$this->setSporsmalId( $row['sporsmal_id'] );
		$this->setName( $row['name'] );
	}
	
	public function setSporsmalId( $id ) {
		$this->sporsmalId = $id;
		return $this;
	}
	public function getSporsmalID() {
		return $this->sporsmalId;
	}

	public function setName( $name ) {
		$this->name = $name;
		return $this;
	}
	public function getName() {
		return $this->name;
	}
	
	public function getCount() {
		if( null == $this->count ) {
			require_once('UKM/Konkurranse/answer.collection.php');
			$sql = new SQL("
				SELECT COUNT(`id`) AS `count`
				FROM `#table`
				WHERE `sporsmal_id` = '#sporsmal_id'
				AND `alternativ_id` = '#alternativ_id'",
				[
					'table' => Answer::TABLE_NAME,
					'sporsmal_id' => $this->getSporsmalId(),
					'alternativ_id' => $this->getId(),
				]
			);
			$this->count = $sql->run('field','count');
		}
		return $this->count;
	}
	
	public function getAnswers() {
		if( null == $this->answers ) {
			require_once('UKM/Konkurranse/answer.collection.php');
			$sql = new SQL("
				SELECT *
				FROM `#table`
				WHERE `sporsmal_id` = '#sporsmal_id'
				AND `alternativ_id` = '#alternativ_id'",
				[ 
					'table' => Answer::TABLE_NAME,
					'sporsmal_id' => $this->getSporsmalID(),
					'alternativ_id' => $this->getId(),
				]
			);
			$answers = $sql->run();
			
			$this->answers = [];
			while( $row = SQL::fetch( $answers ) ) {
				$this->answers[] = new Answer( $row );
			}
			return $this->answers;
		}
	}

	public function __toString() {
		return $this->getName();
	}
}