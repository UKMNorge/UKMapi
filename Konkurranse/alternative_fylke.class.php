<?php
	
class AlternativeFylke {
	
	var $sporsmalId = null;
	var $name = null;
	var $count = null;
	var $id = null;
	var $key = null;
	
	public function __construct( $sporsmalId, $fylke ) {
		$this->setSporsmalId( $sporsmalId );
		$this->setName( $fylke->getNavn() );
		$this->setId( $fylke->getId() );
		$this->setKey( $fylke->getLink() );
	}
	
	public function setSporsmalId( $id ) {
		$this->sporsmalId = $id;
		return $this;
	}
	public function getSporsmalId() {
		return $this->sporsmalId;
	}

	public function setName( $name ) {
		$this->name = $name;
		return $this;
	}
	public function getName() {
		return $this->name;
	}
	
	public function setId( $id ) {
		$this->id = $id;
		return $this;
	}
	public function getId() {
		return $this->id;
	}
	
	public function setKey( $key ) {
		$this->key = $key;
		return $this;
	}
	public function getKey() {
		return $this->key;
	}
	
	
	public function getCount() {
		if( null == $this->count ) {
			require_once('UKM/Konkurranse/answer.collection.php');
			$sql = new SQL("
				SELECT COUNT(`id`) AS `count`
				FROM `#table`
				WHERE `sporsmal_id` = '#sporsmal_id'
				AND `svar` = '#svar'",
				[
					'table' => Answer::TABLE_NAME,
					'sporsmal_id' => $this->getSporsmalId(),
					'svar' => $this->getKey(),
				]
			);
			$this->count = $sql->run('field','count');
		}
		return $this->count;
	}

	public function __toString() {
		return $this->getName();
	}
}