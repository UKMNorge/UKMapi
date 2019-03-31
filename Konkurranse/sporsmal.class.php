<?php


require_once('UKM/_orm.class.php');
	
class Sporsmal extends ORM {
	const TABLE_NAME = 'konkurranse_sporsmal';
	
	var $type = null;
	var $answerType = null;
	var $name = null;
	var $alternatives = null;
	var $answers = null;
	
	public function populate( $row ) {
		$this->setType( $row['type'] );
		$this->setAnswerType( $row['answer_type'] );
		$this->setName( $row['name'] );
	}
	
	public function setType( $type ) {
		$this->type = $type;
		return $this;
	}
	public function getType() {
		return $this->type;
	}

	public function setAnswerType( $answerType ) {
		$this->answerType = $answerType;
		return $this;
	}
	public function getAnswerType() {
		return $this->answerType;
	}
		
	public function setName( $name ) {
		$this->name = $name;
		return $this;
	}
	public function getName() {
		return $this->name;
	}
	
	public function __toString() {
		return $this->getName();
	}
	
	public function getAlternatives() {
		if( null == $this->alternatives ) {
			require_once('UKM/Konkurranse/alternative.collection.php');
			$this->alternatives = new AlternativeColl( $this->getId(), $this->getAnswerType() );
		}
		return $this->alternatives;
	}

	public function getAnswers() {
		if( null == $this->answers ) {
			require_once('UKM/Konkurranse/answer.collection.php');
			$this->answers = new Answers( $this->getId() );
		}
		return $this->answers;
	}

	public function update() {
		self::_update( 
			[
				'name' => $this->getName(),
				'type' => $this->getType(),
				'answer_type' => $this->getAnswerType(),
			]
		);
	}

}