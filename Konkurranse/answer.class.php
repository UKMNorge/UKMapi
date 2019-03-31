<?php


require_once('UKM/_orm.class.php');
require_once('UKM/Konkurranse/sporsmal.collection.php');

class Answer extends ORM {
	const TABLE_NAME = 'konkurranse_svar';
	
	var $sporsmalId = null;
	var $alternativeId = null;
	var $answer = null;
	var $mobil = null;
	var $alternative = null;
	
	public function populate( $row ) {
		$this->setSporsmalId( $row['sporsmal_id'] );
		$this->setAlternativeId( $row['alternativ_id'] );
		$this->setAnswerText( $row['svar'] );
		$this->setMobil( $row['mobil'] );
	}
	
	public function setSporsmalId( $id ) {
		$this->sporsmalId = $id;
		return $this;
	}
	public function getSporsmalId() {
		return $this->sporsmalId;
	}
	public function setAlternativeId( $id ) {
		$this->alternativeId = $id;
		return $this;
	}
	public function getAlternativeId() {
		return $this->alternativeId;
	}
	
	public function setAnswerText( $text ) {
		$this->answer_text = $text;
		return $this;
	}
	public function getAnswerText() {
		return $this->answer_text;
	}
	
	public function setMobil( $mobil ) {
		$this->mobil = $mobil;
		return $this;
	}
	public function getMobil() {
		return $this->mobil;
	}
	public function getSporsmal() {
		if( null == $this->sporsmal ) {
			$this->sporsmal = SporsmalColl::getById( $this->getSporsmalId() );
		}
		return $this->sporsmal;
	}
	
	public function getAlternative() {
		if( $this->alternative == null ) {
			require_once('UKM/Konkurranse/alternative.collection.php');
			$sporsmal = SporsmalColl::getById( $this->getSporsmalId() );
			
			if( $this->getSporsmal()->getAnswerType() == 'fylke' ) {
				$this->alternative = new AlternativeFylke(
					$this->getSporsmal()->getId(),
					fylker::getByLink( $this->getAnswerText() )
				);
			}
			elseif( $this->getSporsmal()->getAnswerType() == 'innslag' ) {
				$this->alternative = new AlternativeInnslag(
					$this->getSporsmal()->getId(),
					new innslag_v2( $this->getAnswerText() )
				);
			}
			else {
				$this->alternative = $sporsmal->getAlternatives()->getById( $this->getAlternativeId() );
			}
		}
		return $this->alternative;
	}

	public function getAnswer() {
		require_once('UKM/Konkurranse/alternative.collection.php');

		if( $this->getAlternative() != null ) {
			return $this->getAlternative()->getName();
		}
		return $this->getAnswerText();
	}
	
	// Used by parent collection
	public function getName() {
		return $this->getAnswer();
	}
	
	public function __toString() {
		return $this->getAnswer();
	}
}