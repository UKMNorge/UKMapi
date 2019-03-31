<?php
	
class AlternativeInnslag {
	var $sporsmalId = null;
	var $name = null;
	var $count = null;
	var $id = null;
	var $innslag = null;
	
	public function __construct( $sporsmalId, $innslagId ) {
		$this->setSporsmalId( $sporsmalId );
		$this->setId( $innslagId );
		$this->setName( $this->getInnslag()->getNavn() );
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
	
	public function getInnslag() {
		if( null == $this->innslag ) {
			require_once('UKM/innslag.class.php');
			$this->innslag = new innslag_v2( $this->getId() );
		}
		return $this->innslag;
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
					'svar' => $this->getInnslag()->getId(),
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