<?php
require_once('UKM/sql.class.php');
require_once('UKM/innslag.class.php');

class innslag_collection {
	var $innslag = null;
	var $containerType = null;
	var $containerObjectId = null;

	var $monstring_id = null; // Brukes av container_type 'monstring'
	var $monstring_type = null; // Brukes av container_type 'monstring'
	var $monstring_sesong = null; // Brukes av container_type 'monstring'
	
	public function __construct($container_type, $container_object_id) {
		$this->setContainerType( $container_type );
		$this->setContainerObjectId( $container_object_id );
	}

	public function getAntall() {
		return sizeof( $this->getAll() );
	}
	
	public function getAll() {
		if( null == $this->innslag ) {
			$this->_load();
		}
		return $this->innslag;
	}

	
	public function setContainerObjectId( $id ) {
		$this->containerObjectId = $id;
		return $this;
	}
	public function getContainerObjectId() {
		return $this->containerObjectId;
	}
	
	public function setContainerType( $type ) {
		if( !in_array( $type, array('monstring' ) ) ) {
			throw new Exception('INNSLAG_COLLECTION: Har ikke støtte for '. $type .'-collection');
		}
		$this->containerType = $type;
		return $this;
	}
	public function getContainerType() {
		return $this->containerType;
	}
	
	public function setContainerDataMonstring( $pl_id, $pl_type, $sesong ) {
		$this->setMonstringId( $pl_id );
		$this->setMonstringType( $pl_type );
		$this->setMonstringSesong( $sesong );
		return $this;
	}

	/**
	 * Sett mønstringsid (PLID)
	 *
	 * @param string $type
	 * @return $this
	**/
	public function setMonstringId( $pl_id ) {
		$this->monstring_id = $pl_id;
		return $this;
	}
	/**
	 * Hent mønstringsid (PLID)
	 *
	 * @param string $type
	 * @return $this
	**/
	public function getMonstringId() {
		return $this->monstring_id;
	}
	
		
	/**
	 * Sett mønstringstype
	 *
	 * @param string $type
	 * @return $this
	**/
	public function setMonstringType( $type ) {
		$this->monstring_type = $type;
		return $this;
	}
	/**
	 * Hent mønstringstype
	 *
	 * @return string $type
	**/
	public function getMonstringType() {
		return $this->monstring_type;
	}
	
		
	/**
	 * Sett sesong
	 *
	 * @param int $seson
	 * @return $this
	**/
	public function setMonstringSesong( $sesong ) {
		$this->monstring_sesong = $sesong;
		return $this;
	}
	/**
	 * Hent sesong
	 *
	 * @return int $sesong
	**/
	public function getMonstringSesong() {
		return $this->monstring_sesong;
	}
	
	public function _load() {
		$this->innslag = array();
		
		$SQL = $this->_getQuery();
		$res = $SQL->run();
		if( !$res ) {
			return array();
		}
		while( $row = mysql_fetch_assoc( $res ) ) {
			$innslag = new innslag_v2( $row );
			$this->addInnslag( $innslag );
			
			// GJØR NOE MED ORDER-FELTET!!
		}
	}

	private function _getQuery() {
		switch( $this->getContainerType() ) {
			case 'monstring':
				if( null == $this->getMonstringId() ) {
					throw new Exception('innslag: Krever MønstringID for å hente mønstringens innslag');
				}
				switch( $this->getMonstringType() ) {
					case 'land':
						return new SQL("SELECT `band`.*, 
											   `td`.`td_demand`,
											   `td`.`td_konferansier`
										FROM `smartukm_fylkestep` AS `fs` 
										JOIN `smartukm_band` AS `band` ON (`band`.`b_id` = `fs`.`b_id`)
										LEFT JOIN `smartukm_technical` AS `td` ON (`td`.`b_id` = `band`.`b_id`) 
										WHERE `band`.`b_season` = '#season'
											AND `b_status` = '8'
											AND `fs`.`pl_id` = '#pl_id'
										GROUP BY `band`.`b_id`
										ORDER BY `bt_id` ASC,
												`band`.`b_name` ASC",
									array(	'season' => $this->getMonstringSesong(),
											'pl_id' => $this->getMonstringId()
										)
									);
					break;
					default:
						throw new Exception('INNSLAG_COLLECTION: Funksjonalitet for å hente innslag på '
											.'fylkes- og lokalmønstringer ikke implementert');
				}		
			default:
				throw new Exception('innslag: Har ikke støtte for '. $type .'-collection (#2)');
		}
	}
	/**
	 * legg til innslag
	 *
	 * @param $innslag
	 * @return $this
	**/
	public function addInnslag( $innslag ) {
		if( null == $this->innslag ) {
			$this->innslag = array();
		}
		
		$this->innslag[] = $innslag;
		
		return $this;
	}
}