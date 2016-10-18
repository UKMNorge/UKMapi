<?php
require_once('UKM/sql.class.php');
require_once('UKM/innslag.class.php');

class innslag_collection {
	var $innslag = null;
	var $innslag_ufullstendige = null;
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
	
	public function getAllUfullstendige() {
		if( null == $this->innslag_ufullstendige ) {
			$this->_load( false );
		}
		return $this->innslag_ufullstendige;
	}
	
	public function getAllByType( $innslag_type ) {
		return $this->filterByType( $innslag_type, $this->getAll() );
	}
	
	public function getAllByStatus( $status_array ) {
		return $this->filterByStatus( $status_array, $this->getAll() );
	}

	public function filterByStatus( $status_array, $innslag_array ) {
		if( !is_array( $status_array ) ) {
			throw new Exception('innslag_collection::filterByStatus() krever at parameter 1 er array. Gitt '. get_class( $status_array ) );
		}

		$selected_innslag = [];
		foreach( $innslag_array as $innslag ) {
			if( in_array( $innslag->getStatus(), $status_array ) ) {
				$selected_innslag[] = $innslag;
			}
		}
		return $selected_innslag;
	}
	
	public function filterByType( $innslag_type, $innslag_array ) {
		if( 'innslag_type' != get_class( $innslag_type ) ) {
			throw new Exception('innslag_collection::getAllByType() krever objekt av klassen innslag_type. Gitt '. get_class( $innslag_type ) );
		}
		
		$selected_innslag = [];
		foreach( $innslag_array as $innslag ) {
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
	
	public function _load( $pameldte=true ) {
		$internal_var = $pameldte ? 'innslag' : 'innslag_ufullstendige';
		$this->$internal_var = array();
		
		$SQL = $this->_getQuery( $pameldte );
		$res = $SQL->run();
		#echo $SQL->debug();
		if( !$res ) {
			return false;
		}
		while( $row = mysql_fetch_assoc( $res ) ) {
			$innslag = new innslag_v2( $row );
			array_push( $this->$internal_var, $innslag);
		}
		return true;
	}

	private function _getQuery( $pameldte ) {
		$operand = $pameldte ? '=' : '<';
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
										AND `b_status` ". $operand ." '8'
									GROUP BY `band`.`b_id`
									ORDER BY `bt_id` ASC,
											`band`.`b_name` ASC",
								array(	'season' => $this->getMonstringSesong(),
										'pl_id' => $this->getMonstringId(),
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
											AND `b_status` ". $operand ." '8'
											AND `fs`.`pl_id` = '#pl_id'
										GROUP BY `band`.`b_id`
										ORDER BY `bt_id` ASC,
												`band`.`b_name` ASC",
									array(	'season' => $this->getMonstringSesong(),
											'pl_id' => $this->getMonstringId(),
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
											AND `b_status` ". $operand ." '8'
											AND `k`.`idfylke` = '#fylke_id'
										GROUP BY `band`.`b_id`
										ORDER BY `bt_id` ASC,
												 `band`.`b_name` ASC",
									array(	'season' => $this->getMonstringSesong(),
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
											AND `b_status` ". $operand ." '8'
											AND `b_kommune` IN ('". implode("','", $this->getMonstringKommuner() ) ."')
										GROUP BY `band`.`b_id`
										ORDER BY `bt_id` ASC,
												 `band`.`b_name` ASC",
									array(	'season' => $this->getMonstringSesong(),
											# IDs inputted directly to avoid escaping
										)
									);
				}		
			default:
				throw new Exception('innslag: Har ikke støtte for '. $type .'-collection (#2)');
		}
	}


	/**
	 * meldAv - Melder av et innslag fra mønstringen.
	 * @param write_innslag $innslag
	 * @return bool
	 */
	public function meldAv($innslag) {
		### Gjør litt validering, da.
		if( 'write_innslag' != get_class($innslag) ) {
			throw new Exception("INNSLAG_COLLECTION: Krever skrivbart innslagsobjekt for å kunne melde av fra mønstringen.");
		}
		if( 'monstring' != $this->getContainerType() ) {
			throw new Exception("INNSLAG_COLLECTION: Kan ikke fjerne et innslag uten mønstring.");
		}
		if( !is_numeric( $innslag->getId() ) ) {
			throw new Exception("INNSLAG_COLLECTION: Avmelding av innslag krever et innslag med numerisk ID.");
		}
		
		if( !UKMlogger::ready() ) {
			throw new Exception("INNSLAG_COLLECTION: Kan ikke melde av innslaget når loggeren ikke er klar.");
		}

		$monstring = new monstring_v2( $this->getContainerObjectId() );
		if( $monstring->getType() !== 'kommune' ) {
			throw new Exception("INNSLAG_COLLECTION: Avmelding av innslag er kun implementert for lokalmønstringer!");
		}
		if( !is_numeric( $monstring->getId() ) ) {
			throw new Exception("INNSLAG_COLLECTION: Avmelding av innslag krever en mønstring med numerisk ID.");	
		}
		

		$qry = new SQLdel("smartukm_rel_pl_b", array("b_id" => $innslag->getId(), 'pl_id' => $monstring->getId(), 'season' => $this->getMonstringSesong()) );
		// TODO: Sjekk at den faktisk logger!
		/**
		INSERT INTO `log_actions` 
			(`log_action_id`, `log_action_verb`, `log_action_element`, `log_action_datatype`, `log_action_identifier`, `log_action_printobject`)
		VALUES
			(601, 'meldte av', 'innslaget', 'int', 'smartukm_rel_pl_b|delete', 0);

		*/
		UKMlogger::log( 601, $innslag->getId(), $innslag->getId() );
		$res = $qry->run();
		if(1 == $res) {
			return true;
		}
		throw new Exception("INNSLAG_COLLECTION: Klarte ikke å melde av innslaget.");
	}

	/**
	 * legg til innslag
	 *
	 * @param $innslag
	 * @return $this
	**/
/*	public function addInnslag( $innslag ) {
		if( null == $this->innslag ) {
			$this->innslag = array();
		}
		
		$this->innslag[] = $innslag;
		
		return $this;
	}*/
}