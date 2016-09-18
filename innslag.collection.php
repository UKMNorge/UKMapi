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
	
	public function getAllByType( $innslag_type ) {
		if( 'innslag_type' != get_class( $innslag_type ) ) {
			throw new Exception('innslag_collection::getAllByType() krever objekt av klassen innslag_type. Gitt '. get_class( $innslag_type ) );
		}
		
		$selected_innslag = [];
		foreach( $this->getAll() as $innslag ) {
			if( $innslag->getType()->getId() == $innslag_type->getId() 
				&& $innslag->getType()->getKey() == $innslag_type->getKey() )
			{
				$selected_innslag[] = $innslag;
			}
		}
		return $selected_innslag;
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
	
	public function setContainerDataMonstring( $pl_id, $pl_type, $sesong, $fylke, $kommuner ) {
		$this->setMonstringId( $pl_id );
		$this->setMonstringType( $pl_type );
		$this->setMonstringSesong( $sesong );
		$this->setMonstringFylke( $fylke );
		$this->setMonstringKommuner( $kommuner );
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
	 * @return $this
	**/
	public function getMonstringId() {
		return $this->monstring_id;
	}
	
	/**
	 * Sett mønstringens kommuner
	 *
	 * @param array $kommuner
	 * @return $this
	**/
	public function setMonstringKommuner( $kommuner ) {
		$this->monstring_kommuner = $kommuner;
		return $this;
	}
	/**
	 * Hent mønstringens kommuner
	 *
	 * @return array $kommuner
	**/
	public function getMonstringKommuner() {
		return $this->monstring_kommuner;
	}
	
	/**
	 * Sett hvilket fylke mønstringen tilhører
	 *
	 * @param integer $fylke
	 * @return $this
	**/
	public function setMonstringFylke( $fylke ) {
		$this->monstring_fylke = $fylke;
		return $this;
	}
	/**
	 * Hent hvilket fylke mønstringen tilhører
	 *
	 * @return integer $fylke
	**/
	public function getMonstringFylke() {
		return $this->monstring_fylke;
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
#		echo $SQL->debug();
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

	private function _getQuery( $status=8 ) {
		switch( $this->getContainerType() ) {
			case 'monstring':
				if( null == $this->getMonstringId() ) {
					throw new Exception('innslag: Krever MønstringID for å hente mønstringens innslag');
				}

				// PRE 2011 DID NOT USE BAND SEASON FIELD
				if( 2011 >= $this->getMonstringSesong() ) {
					return new SQL("SELECT `band`.*, 
										   `td`.`td_demand`,
										   `td`.`td_konferansier`
									FROM `smartukm_band` AS `band`
									JOIN `smartukm_rel_pl_b` AS `pl_b` ON (`pl_b`.`b_id` = `band`.`b_id`)
									LEFT JOIN `smartukm_technical` AS `td` ON (`td`.`b_id` = `band`.`b_id`) 
									WHERE `pl_b`.`pl_id` = '#pl_id'
										AND `b_status` = '#status'
									GROUP BY `band`.`b_id`
									ORDER BY `bt_id` ASC,
											`band`.`b_name` ASC",
								array(	'season' => $this->getMonstringSesong(),
										'pl_id' => $this->getMonstringId(),
										'status' => $status,
									)
								);
				}
				
				// POST 2011
				switch( $this->getMonstringType() ) {
					case 'land':
						return new SQL("SELECT `band`.*, 
											   `td`.`td_demand`,
											   `td`.`td_konferansier`
										FROM `smartukm_fylkestep` AS `fs` 
										JOIN `smartukm_band` AS `band` ON (`band`.`b_id` = `fs`.`b_id`)
										LEFT JOIN `smartukm_technical` AS `td` ON (`td`.`b_id` = `band`.`b_id`) 
										WHERE   `band`.`b_season` = '#season'
											AND `b_status` = '8'
											AND `fs`.`pl_id` = '#pl_id'
										GROUP BY `band`.`b_id`
										ORDER BY `bt_id` ASC,
												`band`.`b_name` ASC",
									array(	'season' => $this->getMonstringSesong(),
											'pl_id' => $this->getMonstringId(),
											'status' => $status,
										)
									);
					break;
					case 'fylke':
						return new SQL("SELECT `band`.*, 
											   `td`.`td_demand`,
											   `td`.`td_konferansier`
										FROM `smartukm_fylkestep` AS `fs` 
										JOIN `smartukm_band` AS `band` ON (`band`.`b_id` = `fs`.`b_id`)
										LEFT JOIN `smartukm_technical` AS `td` ON (`td`.`b_id` = `band`.`b_id`) 
										JOIN `smartukm_kommune` AS `k` ON (`k`.`id`=`band`.`b_kommune`)
										WHERE   `b_season` = '#season'
											AND `b_status` = '#status'
											AND `k`.`idfylke` = '#fylke_id'
										GROUP BY `band`.`b_id`
										ORDER BY `bt_id` ASC,
												 `band`.`b_name` ASC",
									array(	'season' => $this->getMonstringSesong(),
											'status' => $status,
											'fylke_id' => $this->getMonstringFylke(),
										)
									);
					break;	
					default:			
						return new SQL("SELECT `band`.*, 
											   `td`.`td_demand`,
											   `td`.`td_konferansier`
										FROM `smartukm_band` AS `band`
										JOIN `smartukm_rel_pl_b` AS `pl_b` ON (`pl_b`.`b_id` = `band`.`b_id`)
										LEFT JOIN `smartukm_technical` AS `td` ON (`td`.`b_id` = `band`.`b_id`) 
										WHERE   `b_season` = '#season'
											AND `b_status` = '#status'
											AND `b_kommune` IN ('". implode("','", $this->getMonstringKommuner() ) ."')
										GROUP BY `band`.`b_id`
										ORDER BY `bt_id` ASC,
												 `band`.`b_name` ASC",
									array(	'season' => $this->getMonstringSesong(),
											'status' => $status
											# IDs inputted directly to avoid escaping
										)
									);
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