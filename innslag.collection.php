<?php
require_once('UKM/sql.class.php');
require_once('UKM/innslag.class.php');



// $this->getContext()->getMonstring()->getId()
class innslag_collection {
	var $context = null;
	
	var $quickcount = null;
	var $innslag = null;
	var $innslag_ufullstendige = null;
	var $containerType = null;
	var $containerId = null;

	var $monstring_type = null; // Brukes av container_type 'monstring'
	var $monstring_sesong = null; // Brukes av container_type 'monstring'
	var $monstring_kommuner = null; // Brukes av container_type 'monstring'
	var $monstring_fylke = null; // Brukes av container_type 'monstring'
	/**
	 * Class constructor
	 * OBS: monstring-collection krever kall til $this->setContainerDataMonstring()
	 *
	 * @param string $container_type
	 * @param integer $container_id
	**/
	public function __construct( $context ) {
		$this->setContext( $context );
		
		switch( $this->getContext()->getType() ) {
			case 'monstring': 
			break;
			case 'forestilling':
				#throw new Exception('INNSLAG_COLLECTION: ikke implementert støtte for forestilling');
			break;
		}
	}
	
	public function setContext( $context ) {
		$this->context = $context;
		return $this;
	}
	public function getContext() {
		return $this->context;
	}

	
	/**
	 * Hurtig-funksjon for å avgjøre om samlingen har innslag
	 *
	 * Kjører _load() i countOnly-modus, som returnerer mysql_num_rows
	 *
	 * Funksjonen henter alle rader fra databasen med joins
	 * så krever litt, men likevel mye mindre enn å loope 
	 * alle innslag og opprette innslags-objekter
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

	/**
	 * Hent ut innslag med gitt id
	 *
	 * Hvis $mulig_ufullstendig == true, vil den også sjekke
	 * listen over ufullstendige innslag
	 *
	 * @param int $id
	 * @param bool $mulig_ufullstendig=false
	 * @return bool 
	**/
	public function get( $id, $mulig_ufullstendig=false ) {
		foreach( $this->getAll() as $item ) {
			if( $id == $item->getId() ) {
				return $item;
			}
		}
		if( $mulig_ufullstendig ) {
			foreach( $this->getAllUfullstendige() as $item ) {
				if( $id == $item->getId() ) {
					return $item;
				}
			}
		}
		return false;
	}

	/**
	 * Hent alle fullstendig påmeldte innslag
	 *
	 * @return array $innslag
	**/
	public function getAll() {
		if( null == $this->innslag ) {
			$this->_load();
		}
		return $this->innslag;
	}
	
	/**
	 * Hent antall innslag i collection
	 *
	 * @return int sizeof( $this->innslag )
	**/
	public function getAntall() {
		return sizeof( $this->getAll() );
	}


	/********************************************************************************
	 *
	 *
	 * GET FILTERED SUBSETS FROM COLLECTION
	 *
	 *
	 ********************************************************************************/

	/**
	 * Hent alle ufullstendig påmeldte innslag
	 *
	 * @return array [innslag_v2]
	**/
	public function getAllUfullstendige() {
		if( null == $this->innslag_ufullstendige ) {
			$this->_load( false );
		}
		return $this->innslag_ufullstendige;
	}

	/**
	 * Hent alle innslag fra gitt kommune
	 *
	 * @param kommune $kommune
	 * @return array [innslag_v2]
	**/
	public function getAllByKommune( $kommune ) {
		return self::filterByGeografi( $kommune, 'kommune', $this->getAll() );
	}

	/**
	 * Hent alle innslag fra gitt fylke
	 *
	 * @param fylke $fylke
	 * @return array [innslag_v2]
	**/
	public function getAllByFylke( $fylke ) {
		return self::filterByGeografi( $fylke, 'fylke', $this->getAll() );
	}
	
	/**
	 * Hent alle innslag av gitt type
	 *
	 * @param innslag_type $innslag_type
	 * @return array [innslag_v2]
	**/
	public function getAllByType( $innslag_type ) {
		return self::filterByType( $innslag_type, $this->getAll() );
	}
	
	/**
	 * Hent alle innslag av gitt status
	 *
	 * @param array [status]
	 * @return array [innslag_v2]
	**/
	public function getAllByStatus( $status_array ) {
		return self::filterByStatus( $status_array, $this->getAll() );
	}




	/********************************************************************************
	 *
	 *
	 * STATIC FILTER FUNCTIONS
	 *
	 *
	 ********************************************************************************/
	
	/**
	 * Filtrer gitte innslag for gitt status
	 *
	 * @param array [status]
	 * @param array [innslag_v2]
	 * @return array [innslag_v2]
	**/
	public static function filterByStatus( $status_array, $innslag_array ) {
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
	
	/**
	 * Filtrer gitte innslag for gitt type
	 *
	 * @param array [type]
	 * @param array [innslag_v2]
	 * @return array [innslag_v2]
	**/
	public static function filterByType( $innslag_type, $innslag_array ) {
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

	/**
	 * Filtrer gitte innslag for gitt geografi
	 *
	 * @param (kommune|fylke) $geografi
	 * @param string $type
	 * @param array [innslag_v2]
	 * @return array [innslag_v2]
	**/
	public static function filterByGeografi( $geografi, $innslag_array ) {
		if( get_class( $geografi ) != 'kommune' && get_class( $geografi ) != 'fylke' ) {
			throw new Exception(
				'innslag_collection::filterByGeografi: '.
				'Type (param 2) må være kommune eller fylke'
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



	/********************************************************************************
	 *
	 *
	 * DATABASE MODIFYING FUNCTIONS (WRITE)
	 *
	 *
	 ********************************************************************************/

	/**
	 * Legger til et innslag i collection og database
	 *
	 * @param write_innslag $innslag
	 * @return $this
	 */
	public function leggTil( $innslag ) {
		if( 'write_innslag' != get_class($innslag) ) {
			throw new Exception("INNSLAG_COLLECTION: Krever skrivbart innslagsobjekt for å kunne legge til i forestillingen.");
		}
		
		if( !UKMlogger::ready() ) {
			throw new Exception("INNSLAG_COLLECTION: Kan ikke legge til innslag når loggeren ikke er klar.");
		}

		if( false != $this->get( $innslag->getId() ) ) {
			throw new Exception("INNSLAG_COLLECTION: Innslaget er allerede lagt til.", 1);
		}

		switch( $this->getContext()->getType() ) {
			case 'forestilling':
				$this->_leggTilForestilling( $innslag );
				break;
			case 'monstring':
				$this->_leggTilMonstring( $innslag );
				break;
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
		throw new Exception('TODO: fjern støtter ikke context');
		if( 'write_innslag' != get_class($innslag) ) {
			throw new Exception('INNSLAG_COLLECTION: Fjerning krever skrivbart innslagsobjekt');
		}
		if( !is_numeric( $innslag->getId() ) ) {
			throw new Exception("INNSLAG_COLLECTION: Fjerning krever et innslag med numerisk ID.");
		}
		if( !is_numeric( $this->getContainerId() ) ) {
			throw new Exception("INNSLAG_COLLECTION: Fjerning krever container med numerisk ID.");
		}

		if( !UKMlogger::ready() ) {
			throw new Exception('INNSLAG_COLLECTION: Kan ikke fjerne innslag når loggeren ikke er klar.');
		}

		switch( $this->getContext()->getType() ) {
			case 'forestilling':
				$this->_fjernFraForestilling( $innslag );
				break;
			case 'monstring':
				if( $this->getContext()->getMonstring()->getType() == 'kommune' ) {
					$this->_fjernFraLokalMonstring( $innslag );
				} else {
					$this->_fjernVideresending( $innslag );
				}
				break;
			default: 
				throw new Exception("INNSLAG_COLLECTION: Kan kun fjerne innslag fra en forestilling enda, ikke " . $this->getContext()->getType() );
		}
		
		// FJERN FRA COLLECTION
		foreach( ['innslag', 'innslag_ufullstendige'] as $container ) {
			if( is_array( $this->$container ) ) {
				foreach( $this->$container as $pos => $search_innslag ) {
					if( $search_innslag->getId() == $innslag->getId() ) {
						unset( $this->{$container}[ $pos ] );
					}
				}
			}
		}
		return $this;
	}	


	/********************************************************************************
	 *
	 *
	 * PRIVATE HELPER FUNCTIONS
	 *
	 *
	 ********************************************************************************/
	
	/**
	 * Legg til et innslag i en forestilling
	 *
	 * @param write_innslag $innslag
	**/
	private function _leggTilForestilling( $innslag ) {
		UKMlogger::log( 219, $this->getContext()->getForestilling()->getId(), $innslag->getId() );

		$lastorder = new SQL("SELECT `order`
							  FROM `smartukm_rel_b_c`
							  WHERE `c_id` = '#cid'
							  ORDER BY `order` DESC
							  LIMIT 1",
							  array('cid' => $this->getContext()->getForestilling()->getId() ) );
		$lastorder = $lastorder->run('field','order');
		$order = (int)$lastorder+1;
		
		$qry = new SQLins('smartukm_rel_b_c');
		$qry->add('b_id', $innslag->getId() );
		$qry->add('c_id', $this->getContext()->getForestilling()->getId() );
		$qry->add('order', $order);
		$res = $qry->run();
		
		if( 1 != $res ) {
			throw new Exception("INNSLAG_COLLECTION: Klarte ikke å legge til innslaget i forestilling.");
		}
		return $this;
	}

	private function _leggTilMonstring( $innslag ) {
		// TODO: Støtt lokalmønstring
		if( $this->getContext()->getMonstring()->getType() == 'kommune' ) {
			throw new Exception('INNSLAG_COLLECTION: Støtter ikke å legge til innslag på lokalnivå. Må gjøres via create inntil videre');
		}

		$test_relasjon = new SQL(
			"SELECT `id` FROM `smartukm_fylkestep`
				WHERE `pl_id` = '#pl_id'
				AND `b_id` = '#b_id'",
			[
				'pl_id'		=> $this->$this->getContext()->getMonstring()->getId(),
		  		'b_id'		=> $innslag->getId(), 
			]
		);
		$test_relasjon = $test_relasjon->run();
		
		// Hvis allerede videresendt, alt ok
		if( mysql_num_rows($test_relasjon) > 0 ) {
			return true;
		}
		// Videresend personen
		else {
			$videresend = new SQLins('smartukm_fylkestep');
			$videresend->add('pl_id', $this->getContext()->getMonstring()->getId() );
			$videresend->add('b_id', $innslag->getId() );

			UKMlogger::log( 318, $innslag->getId(), $this->getContext()->getMonstring()->getId() );
			$res = $videresend->run();
		
			if( $res ) {
				return true;
			}
		}

		throw new Exception('INNSLAG_COLLECTION: Kunne ikke videresende '. $innslag->getNavn() .' til mønstringen' );
	}

	/**
	 * Fjern et innslag fra en forestilling
	 *
	 * @param write_innslag $innslag
	 * @return $this
	**/
	private function _fjernFraForestilling( $innslag ) {
		UKMlogger::log( 220, $this->getContext()->getForestilling()->getId(), $innslag->getId() );
		$qry = new SQLdel(	'smartukm_rel_b_c', 
							array(	'c_id' => $this->getContext()->getForestilling()->getId(),
									'b_id' => $innslag->getId() ) );
		$res = $qry->run();

		if( 1 != $res ) {
			throw new Exception("INNSLAG_COLLECTION: Klarte ikke å fjerne innslaget fra forestillingen.");
		}
		return $this;
	}
	
	/**
	 * Fjern et innslag fra en lokalmønstring
	 * Dette vil endre innslaget status, og effektivt melde det av
	 * 
	 * @param write_innslag $innslag
	 * @return $this
	**/
	private function _fjernFraLokalMonstring($innslag) {
		/*
		 * Fjern fra lokalmønstring
		*/
		if( $innslag->erVideresendt() ) {
			throw new Exception("INNSLAG_COLLECTION: Du kan ikke melde av et innslag som er videresendt før du har fjernet videresendingen.");
		}
	
		$SQLdel = new SQLdel(
			'smartukm_rel_pl_b',
			[
				'b_id' => $innslag->getId(),
				'pl_id' => $this->getContext()->getMonstring()->getId(),
				'season' => $this->getContext()->getMonstring()->getSesong()
			]
		);
		UKMlogger::log( 311, $innslag->getId(), $innslag->getId() );
		$res = $SQLdel->run();
		$innslag->setStatus(77);
		$innslag->save();
		
		return $this;
	}
	
	/**
	 * Fjern et innslag fra en (fylke|land)mønstring
	 * Vil fjerne videresendingen av innslaget
	 *
	 * @param $innslag
	 * @return $this
	**/
	private function _fjernVideresending( $innslag_object ) {
		require_once('UKM/write_person.class.php');
		require_once('UKM/write_tittel.class.php');
		
		$innslag = $this->get( $innslag->getId() );
		
		throw new Exception('TODO FJERN VIDERESENDING STØTTER IKKE CONTEXT');
		// Meld av alle personer
		foreach( $innslag->getPersoner()->getAllVideresendt( $this->getContainerId() ) as $person ) {
			$write_person = new write_person( $person->getId() );
			$innslag->getPersoner()->fjern( $write_person, $this->getContainerId() );
		}

		// Meld av alle titler
		if( $innslag->harTitler() ) {
			foreach( $innslag->getTitler( $this->getContainerId() )->getAll( ) as $tittel ) {
				$write_tittel = new write_tittel( $tittel->getId() );
				$innslag->getTitler()->fjern( $write_tittel, $this->getContainerId() );
			}
		}
		
		// Fjern videresendingen av innslaget
		$SQLdel = new SQLdel(
			'smartukm_fylkestep',
			[
				'pl_id' => $this->getContainerId(),
				'b_id'	=> $innslag->getId(),
				'season' => $this->getContext()->getMonstring()->getSesong()
			]
		);
		UKMlogger::log( 319, $innslag->getId(), $this->getContainerId() );

		$res = $SQLdel->run();
	
		if( 'monstring' == $this->getContext()->getType() ) {
			$this->_fjernInnslagFraAlleForestillingerIMonstring( $innslag );
		}
		
		if(1 == $res) {
			return true;
		}
		
		return $this;
	}

	/**
	 * Fjern et innslag fra alle forestillinger på en mønstring
	 * Gjøres når et innslag er avmeldt en mønstring
	 *
	 * @param write_innslag $innslag
	 * @return $this
	**/
	private function _fjernInnslagFraAlleForestillingerIMonstring( $innslag ) {
		throw new Exception('TODO FJERN INNSLAG FRA ALLE FORESTILLINGER STØTTER IKKE CONTEXT');
		if( 'write_innslag' != get_class($innslag) ) {
			throw new Exception("INNSLAG_COLLECTION: Krever skrivbart innslagsobjekt.");
		}
		if( 'monstring' != $this->getContext()->getType() ) {
			throw new Exception("INNSLAG_COLLECTION: _fjernInnslagFraAlleForestillingerIMonstring kan kun kjøres med mønstring som container, ikke " . $this->getContext()->getType() );
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
				UKMlogger::log( 220, $forestilling, $innslag->getId() );
				$res = $qry->run();
			}
		}
	}
	
	/**
	 * Last inn innslag til collection
	 *
	 * $pameldte avgjør om innslagene som lastes inn skal sorteres
	 *   inn er påmeldt eller delvis påmeldt
	 * $countOnly benyttes hvis du kun har bruk for å telle opp antall
	 *   innslag. 
	 *   OBS: skal samme script laste inn hele collection på et senere
	 *        tidspunkt bruker du countOnly=false (så sparer du en spørring)
	 *
	 * @param bool $pameldte (true)
	 * @param bool $countOnly (false)
	 * @return bool
	**/
	private function _load( $pameldte=true, $countOnly=false ) {
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
			$innslag->setContext( $this->getContext() );
			array_push( $this->$internal_var, $innslag);
		}
		return true;
	}

	/**
	 * Beregn hvilken SQL-spørring som kreves for å laste inn samlingen
	 *
	**/
	private function _getQuery( $pameldte ) {
		$operand = $pameldte ? '=' : '<';
		switch( $this->getContext()->getType() ) {
			case 'monstring':
				if( null == $this->getContext()->getMonstring()->getId() ) {
					throw new Exception('innslag: Krever MønstringID for å hente mønstringens innslag');
				}

				// PRE 2011 DID NOT USE BAND SEASON FIELD
				if( 2011 >= $this->getContext()->getMonstring()->getSesong() ) {
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
								array(	'season' => $this->getContext()->getMonstring()->getSesong(),
										'pl_id' => $this->getContext()->getMonstring()->getId(),
									)
								);
				}
				
				// POST 2011
				switch( $this->getContext()->getMonstring()->getType() ) {
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
									array(	'season' => $this->getContext()->getMonstring()->getSesong(),
											'pl_id' => $this->getContext()->getMonstring()->getId(),
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
									array(	'season' => $this->getContext()->getMonstring()->getSesong(),
											'fylke_id' => $this->getContext()->getMonstring()->getFylke(),
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
											AND `b_kommune` IN ('". implode("','", $this->getContext()->getMonstring()->getKommuner() ) ."')
										GROUP BY `band`.`b_id`
										ORDER BY `bt_id` ASC,
												 `band`.`b_name` ASC",
									array(	'season' => $this->getContext()->getMonstring()->getSesong(),
											# IDs inputted directly to avoid escaping
										)
									);
					break;
				}
				break;
			case 'forestilling':
				if( null == $this->getContext()->getForestilling()->getId() ) {
					throw new Exception('INNSLAG_COLLECTION: Krever forestilling-ID for å hente forestillingens innslag', 2);
				}
				$sql = new SQL(innslag_v2::getLoadQuery()."
								JOIN `smartukm_rel_b_c` AS `rel`
									ON `rel`.`b_id` = `smartukm_band`.`b_id`
								WHERE `rel`.`c_id` = '#c_id'
								ORDER BY `order` ASC",
								array( 'c_id' => $this->getContext()->getForestilling()->getId() ) );
				return $sql; 
				break;
			default:
				throw new Exception('innslag: Har ikke støtte for '. $this->getContext()->getType() .'-collection (#2)');
		}
	}
}
