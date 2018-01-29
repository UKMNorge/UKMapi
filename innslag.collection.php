<?php
require_once('UKM/sql.class.php');
require_once('UKM/innslag.class.php');

class innslag_collection {
	var $quickcount = null;
	var $innslag = null;
	var $innslag_ufullstendige = null;
	var $containerType = null;
	var $containerId = null;

	var $monstring_id = null; // Brukes av container_type 'monstring'
	var $monstring_type = null; // Brukes av container_type 'monstring'
	var $monstring_sesong = null; // Brukes av container_type 'monstring'
	
	public function __construct($container_type, $containerId) {
		$this->setContainerType( $container_type );
		$this->setContainerId( $containerId );
	}
	
	/**
	 * Hurtig-funksjon for å avgjøre om samlingen har innslag
	 *
	 * Kjører _load() i countOnly-modus, som returnerer
	 * mysql_num_rows
	 *
	 * Funksjonen henter alle rader fra databasen med joins
	 * så krever litt, men likevel mye mindre enn å loope 
	 * alle innslag og opprette innslags-objekter
	 *
	 *
	 * Skal du bruke både harInnslag og loope innslagene
	 * bør du sette $forceLoad = true
	**/
	public function harInnslag( $forceLoad=false ) {
		// Hvis vi ikke har info, last inn quickCount såfremt $forceLoad ikke er true
		if( $this->innslag === null && $this->quickCount === null && $forceLoad === false) {
			$this->quickCount = $this->_load( true, true );
		}
		// Hvis innslag ikke er lastet og $forceLoad er true
		elseif( $this->innslag === null && $forceLoad ) {
			$this->_load();
		}
		
		// Hvis vi har quickCount, bruk denne
		if( $this->quickCount !== null ) {
			return $this->quickCount > 0;
		}
		// Hvis vi ikke har quickCount skal vi ha innslag/bruke denne
		return $this->getAntall() > 0;
	}

	/**
	 * Sjekker om collectionen har et innslag med en gitt ID. Fint for å verifisere forespørsler.
	 *
	 */
	public function harInnslagMedId($id) {
		if ( null == $this->innslag ) {
			$this->getAll();
		}
		//var_dump($this->innslag);
		foreach($this->innslag as $innslag) {
			if($id == $innslag->getId()){
				return true;
			}
		}
		return false;
	}

	public function get( $id ) {
	    foreach( $this->getAll() as $item ) {
		    if( $id == $item->getId() ) {
			    return $item;
		    }
	    }
	    return false;
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

	public function getAllByKommune( $kommune ) {
		return $this->filterByGeografi( $kommune, 'kommune', $this->getAll() );
	}

	public function getAllByFylke( $fylke ) {
		return $this->filterByGeografi( $fylke, 'fylke', $this->getAll() );
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

	
	public function filterByGeografi( $geografi, $type, $innslag_array ) {
		if( $type != 'kommune' && $type != 'fylke' ) {
			throw new Exception(
				'innslag_collection::filterByGeografi: '.
				'Type (param 2) må være kommune eller fylke'
			);
		}
		if( $type != get_class( $geografi ) ) {
			throw new Exception(
				'innslag_collection::filterByGeografi() geografi-objekt må matche gitt type. '. 
				'Gitt '. get_class($geografi) .', ikke "'. $type .'"'
			);
		}
		
		$selected_innslag = [];
		foreach( $innslag_array as $innslag ) {
			if( get_class( $geografi ) == 'kommune' ) {
				if( $innslag->getKommune()->getId() == $geografi->getId() ) {
					$selected_innslag[] = $innslag;
				}
			} elseif( get_class( $geografi ) == 'fylke' ) {
				if( $innslag->getFylke()->getId() == $geografi->getId() ) {
					$selected_innslag[] = $innslag;
				}
			}
		}
		return $selected_innslag;
	}
	
	public function setContainerId( $id ) {
		$this->containerId = $id;

		return $this;
	}
	public function getContainerId() {
		return $this->containerId;
	}
	
	public function setContainerType( $type ) {
		if( !in_array( $type, array('monstring', 'forestilling' ) ) ) {
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
	 *
	 *
	 *
	 *
	**/
	public function setContainerDataForestilling( $forestilling ) {
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
	
	public function _load( $pameldte=true, $countOnly=false ) {
		$internal_var = $pameldte ? 'innslag' : 'innslag_ufullstendige';
		$this->$internal_var = array();
		
		$SQL = $this->_getQuery( $pameldte );
		$res = $SQL->run();
		#echo $SQL->debug();
		if( !$res ) {
			return false;
		}
		if( $countOnly ) {
			return mysql_num_rows( $res );
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
										LEFT JOIN `smartukm_rel_pl_b` AS `pl_b` ON (`pl_b`.`b_id` = `band`.`b_id`)
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
					break;
				}
				break;
			case 'forestilling':
				if( null == $this->getContainerId() ) {
					throw new Exception('INNSLAG_COLLECTION: Krever container-ID for å hente forestillingens innslag', 2);
				}
				$sql = new SQL(innslag_v2::getLoadQuery()."
								JOIN `smartukm_rel_b_c` AS `rel`
									ON `rel`.`b_id` = `smartukm_band`.`b_id`
								WHERE `rel`.`c_id` = '#c_id'
								ORDER BY `order` ASC",
								array( 'c_id' => $this->getContainerId() ) );
				return $sql; 
				break;
			default:
				throw new Exception('innslag: Har ikke støtte for '. $this->getContainerType() .'-collection (#2)');
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

		$monstring = new monstring_v2( $this->getContainerId() );
		if( $monstring->getType() !== 'kommune' ) {
			throw new Exception("INNSLAG_COLLECTION: Avmelding av innslag er kun implementert for lokalmønstringer!");
		}
		if( !is_numeric( $monstring->getId() ) ) {
			throw new Exception("INNSLAG_COLLECTION: Avmelding av innslag krever en mønstring med numerisk ID.");	
		}

		if( $innslag->erVideresendt() ) {
			throw new Exception("INNSLAG_COLLECTION: Du kan ikke melde av et innslag som er videresendt før du har fjernet videresendingen.");
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
		$innslag->setStatus(77);
		$innslag->save();
		$res = $qry->run();

		// TODO: Sørg for å fjerne fra eventuelle forestillinger også!
		if( 'monstring' == $this->getContainerType() ) {
			$this->_fjernInnslagFraAlleForestillingerIMonstring( $innslag );
		}
		if(1 == $res) {
			return true;
		}
		throw new Exception("INNSLAG_COLLECTION: Klarte ikke å melde av innslaget.");
	}

	/**
	 * Legger til et innslag i collectionen og container.
	 *
	 * @param write_innslag $innslag
	 * @return $this
	 *
	 */
	public function leggTil( $innslag ) {
		if( 'write_innslag' != get_class($innslag) ) {
			throw new Exception("INNSLAG_COLLECTION: Krever skrivbart innslagsobjekt for å kunne legge til i forestillingen.");
		}
		if( 'forestilling' != $this->getContainerType() ) {
			throw new Exception("INNSLAG_COLLECTION: Kan kun legge innslag til en forestilling enda, ikke " . $this->getContainerType() );
		}
		
		if( !UKMlogger::ready() ) {
			throw new Exception("INNSLAG_COLLECTION: Kan ikke legge til innslag når loggeren ikke er klar.");
		}

		if( false != $this->get( $innslag->getId() ) ) {
			throw new Exception("INNSLAG_COLLECTION: Innslaget er allerede lagt til i denne hendelsen.", 1);
		}

		UKMlogger::log( 518, $this->getContainerId(), $innslag->getId() );

		$lastorder = new SQL("SELECT `order`
							  FROM `smartukm_rel_b_c`
							  WHERE `c_id` = '#cid'
							  ORDER BY `order` DESC
							  LIMIT 1",
							  array('cid' => $this->getContainerId() ) );
		$lastorder = $lastorder->run('field','order');
		$order = (int)$lastorder+1;
		
		$qry = new SQLins('smartukm_rel_b_c');
		$qry->add('b_id', $innslag->getId() );
		$qry->add('c_id', $this->getContainerId() );
		$qry->add('order', $order);
		$res = $qry->run();
		
		if( 1 != $res ) {
			throw new Exception("INNSLAG_COLLECTION: Klarte ikke å legge til innslaget i forestilling.");
		}
		return $this;
	}

	/**
	 * Fjerner et innslag fra denne forestillingen.
	 *
	 * @param write_innslag $innslag
	 * @return $this
	 */
	public function fjern( $innslag ) {
		if( 'write_innslag' != get_class($innslag) ) {
			throw new Exception("INNSLAG_COLLECTION: Krever skrivbart innslagsobjekt for å kunne fjerne fra forestillinger.");
		}
		if( 'forestilling' != $this->getContainerType() ) {
			throw new Exception("INNSLAG_COLLECTION: Kan kun fjerne innslag fra en forestilling enda, ikke " . $this->getContainerType() );
		}
		if( !UKMlogger::ready() ) {
			throw new Exception("INNSLAG_COLLECTION: Kan ikke fjerne innslag når loggeren ikke er klar.");
		}

		if( !is_numeric( $this->getContainerId() ) ||
			!is_numeric( $innslag->getId() ) ||
			0 == $innslag->getId() || 
			0 == $this->getContainerId() )
		{
			throw new Exception("FORESTILLING_V2: Krever forestillings-ID og innslags-ID som tall.");
		}

		UKMlogger::log( 519, $this->getContainerId(), $innslag->getId() );

		$qry = new SQLdel(	'smartukm_rel_b_c', 
							array(	'c_id' => $this->getContainerId(),
									'b_id' => $innslag->getId() ) );
		$res = $qry->run();

		if( 1 != $res ) {
			throw new Exception("INNSLAG_COLLECTION: Klarte ikke å fjerne innslaget fra forestillingen.");
		}
		return $this;
	}

	private function _fjernInnslagFraAlleForestillingerIMonstring( $innslag ) {
		if( 'write_innslag' != get_class($innslag) ) {
			throw new Exception("INNSLAG_COLLECTION: Krever skrivbart innslagsobjekt.");
		}
		if( 'monstring' != $this->getContainerType() ) {
			throw new Exception("INNSLAG_COLLECTION: _fjernInnslagFraAlleForestillingerIMonstring kan kun kjøres med mønstring som container, ikke " . $this->getContainerType() );
		}
		if( !UKMlogger::ready() ) {
			throw new Exception("INNSLAG_COLLECTION: Kan ikke fjerne innslag når loggeren ikke er klar.");
		}

		// Finn alle forestillinger i mønstringen
		$fQry = new SQL("SELECT `c_id` FROM `smartukm_concert` WHERE `pl_id` = '#pl_id' ", array('pl_id' => $this->getContainerId() ));
		$fRes = $fQry->run();
		$forestillinger = array();
		while( $row = mysql_fetch_array($fRes) ) {
			$forestillinger[] = $row['c_id'];
		}

		// Fjern innslaget fra alle hendelser i mønstringen
		foreach($forestillinger as $forestilling) {
			if( null != $forestilling || null != $innslag->getId() ) {
				$qry = new SQLdel("smartukm_rel_b_c", array( 'c_id' => $forestilling, 'b_id' => $innslag->getId() ));
				UKMlogger::log( 701, $this->getContainerId(), $innslag->getId() );
				$res = $qry->run();
			}
		}

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
