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
	
	/**
	 * Class constructor
	 * OBS: monstring-collection krever kall til $this->setContainerDataMonstring()
	 *
	 * @param string $container_type
	 * @param integer $container_id
	**/
	public function __construct($container_type, $container_id) {
		$this->setContainerType( $container_type );
		$this->setContainerId( $container_id );
	}
		
	/**
	 * Sett monstringsdata
	 * Benyttes når collection er for en mønstring
	 * Setter nødvendig hjelpedata fra moder-objektet
	 *
	 * @param integer $pl_id
	 * @param string $pl_type
	 * @param integer $sesong
	 * @param integer $fylke
	 * @param array $kommune_id
	 * @return $this
	**/
	public function setContainerDataMonstring( $pl_id, $pl_type, $sesong, $fylke, $kommuner ) {
		$this->setMonstringId( $pl_id );
		$this->setMonstringType( $pl_type );
		$this->setMonstringSesong( $sesong );
		$this->setMonstringFylke( $fylke );
		$this->setMonstringKommuner( $kommuner );
		return $this;
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
	 * @param int $id
	 * @return bool 
	**/
	public function get( $id ) {
		foreach( $this->getAll() as $item ) {
			if( $id == $item->getId() ) {
				return $item;
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

		switch( $this->getContainerType() ) {
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

		switch( $this->getContainerType() ) {
			case 'forestilling':
				$this->_fjernFraForestilling( $innslag );
				break;
			case 'monstring':
				if( $this->getMonstringType() == 'kommune' ) {
					$this->_fjernFraLokalMonstring( $innslag );
				} else {
					$this->_fjernVideresending( $innslag );
				}
				break;
			default: 
				throw new Exception("INNSLAG_COLLECTION: Kan kun fjerne innslag fra en forestilling enda, ikke " . $this->getContainerType() );
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
	 * SETTERS AND GETTERS
	 *
	 *
	 ********************************************************************************/
	
	/**
	 * Sett container id
	 * Setter id av mønstring eller forestilling samlingen gjelder
	 *
	 * @param integer $id
	 * @return $this
	**/
	public function setContainerId( $id ) {
		$this->containerId = $id;

		return $this;
	}
	
	/**
	 * Hent container id (mønstring|forestilling)Id
	 *
	 * @return integer $id
	**/
	public function getContainerId() {
		return $this->containerId;
	}
	
	/**
	 * Sett type container dette er
	 * 
	 * @param string $type (monstring|forestilling)
	 * @return $this
	**/
	public function setContainerType( $type ) {
		if( !in_array( $type, array('monstring', 'forestilling' ) ) ) {
			throw new Exception('INNSLAG_COLLECTION: Har ikke støtte for '. $type .'-collection');
		}
		$this->containerType = $type;
		return $this;
	}
	
	/**
	 * Hent container type
	 * 
	 * @return string $type (monstring|forestilling)
	**/
	public function getContainerType() {
		return $this->containerType;
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
		if( !is_numeric( $fylke ) ) {
			throw new Exception('INNSLAG_COLLECTION: setMonstringFylke krever numerisk fylke-id');	
		}
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
		UKMlogger::log( 219, $this->getContainerId(), $innslag->getId() );

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

	private function _leggTilMonstring( $innslag ) {
		// TODO: Støtt lokalmønstring
		if( $this->getMonstringType() == 'kommune' ) {
			throw new Exception('INNSLAG_COLLECTION: Støtter ikke å legge til innslag på lokalnivå. Må gjøres via create inntil videre');
		}

		$test_relasjon = new SQL(
			"SELECT `id` FROM `smartukm_fylkestep`
				WHERE `pl_id` = '#pl_id'
				AND `b_id` = '#b_id'",
			[
				'pl_id'		=> $this->getMonstringId(),
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
			$videresend->add('pl_id', $this->getMonstringId() );
			$videresend->add('b_id', $innslag->getId() );

			UKMlogger::log( 318, $innslag->getId(), $this->getMonstringId() );
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
		UKMlogger::log( 220, $this->getContainerId(), $innslag->getId() );
		$qry = new SQLdel(	'smartukm_rel_b_c', 
							array(	'c_id' => $this->getContainerId(),
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
				'pl_id' => $this->getContainerId(),
				'season' => $this->getMonstringSesong()
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
	private function _fjernVideresending( $innslag ) {
		throw new Exception('TODO: Personer og titler krever vel egentlig mønstrings-objektet som param 2..?');
		// Meld av alle personer
		foreach( $innslag->getPersoner()->getAllVideresendt( $this->getContainerId() ) as $person ) {
			$innslag->getPersoner()->fjern( $person, $this->getContainerId() );
		}

		// Meld av alle titler
		if( $innslag->harTitler() ) {
			foreach( $innslag->getTitler()->getAllVideresendt( $this->getContainerId() ) as $tittel ) {
				$innslag->getTitler()->fjern( $tittel, $this->getContainerId() );
			}
		}
		
		// Fjern videresendingen av innslaget
		$SQLdel = new SQLdel(
			'smartukm_fylkestep',
			[
				'pl_id' => $this->getContainerId(),
				'b_id'	=> $innslag->getId(),
				'season' => $this->getMonstringSesong()
			]
		);
		UKMlogger::log( 319, $innslag->getId(), $this->getContainerId() );

		$res = $SQLdel->run();
	
		if( 'monstring' == $this->getContainerType() ) {
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
								WHERE `rel`.`c_id` = '#c_id'",
								array( 'c_id' => $this->getContainerId() ) );
				return $sql; 
				break;
			default:
				throw new Exception('innslag: Har ikke støtte for '. $this->getContainerType() .'-collection (#2)');
		}
	}
}