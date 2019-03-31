<?php


require_once('UKM/_orm.class.php');
	
class Geocache extends ORM {
	const TABLE_NAME = 'konkurranse_geocache';
	
	var $type = null;
	var $name = null;
	var $code = null;
	var $count = null;
	
	public function populate( $row ) {
		$this->setType( $row['type'] );
		$this->setCode( $row['code'] );
		$this->setName( $row['navn'] );
	}
	
	public function setType( $type ) {
		$this->type = $type;
		return $this;
	}
	public function getType() {
		return $this->type;
	}

	public function setCode( $code ) {
		$this->code = $code;
		return $this;
	}
	public function getCode() {
		return $this->code;
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
	
	public function getCount() {
		if( null == $this->count ) {
			$sql = new SQL("
				SELECT COUNT(`id`) AS `count`
				FROM `konkurranse_geocache_checkin`
				WHERE `cache` = '#code'
				",
				['code' => $this->getCode()]
			);
			$this->count = $sql->run('field', 'count');
		}
		return $this->count;
	}
	
	public function getUrl() {
		return 'https://festivalen.ukm.no/geocache/?code='. $this->getCode();
	}

	public function update() {
		self::_update( 
			[
				'name' => $this->getName(),
				'type' => $this->getType(),
			]
		);
	}

}