<?php
require_once 'UKM/context.class.php';
require_once 'UKM/sql.class.php';
require_once 'UKM/statistikk.class.php';
require_once 'UKM/monstring_tidligere.class.php';
require_once 'UKM/innslag.collection.php';
require_once 'UKM/innslag_typer.class.php';

require_once('UKM/v1_monstring.class.php');

class monstring_v2 {
	var $id = null;
	var $type = null;
	var $navn = null;
	var $sted = null;
	var $start = null;
	var $start_datetime = null;
	var $stop = null;
	var $stop_datetime = null;
	var $frist_1 = null;
	var $frist_1_datetime = null;
	var $frist_2 = null;
	var $frist_2_datetime = null;
	var $program = null;
	var $kommuner_id = null;
	var $kommuner = null;
	var $fylke = null;
	var $fylke_id = null;
	var $sesong = null;
	var $innslag = null;
	var $path = null;
	var $skjema_id = null;
	var $skjema = null;
	var $kontaktpersoner = null;
	
	var $uregistrerte = null;
	var $publikum = null;
	
	var $attributes = null;
	var $fylkesmonstringer = null;
	
	var $dager = null;
	var $netter = null;
	/**
	 * getLoadQry
	 * Brukes for å få standardiserte databaserader inn for 
	 * generering via _load_by_row
	 *
	 * WHERE-selector og evt ekstra joins må legges på manuelt
	**/
	static function getLoadQry() {
		return "SELECT `place`.*,
					GROUP_CONCAT(`kommuner`.`k_id`) AS `k_ids`
				FROM `smartukm_place` AS `place`
				LEFT JOIN `smartukm_rel_pl_k` AS `kommuner`
					ON (`kommuner`.`pl_id` = `place`.`pl_id`)
				";
	}
	
	public function __construct( $id_or_row ) {

		if( is_numeric( $id_or_row ) ) {
			$this->_load_by_id( $id_or_row );
		} elseif( is_array( $id_or_row ) ) {
			$this->_load_by_row( $id_or_row );
		} else {
			throw new Exception('MONSTRING_V2: Oppretting av objekt krever numerisk id eller databaserad');
		}
		
		$this->attributes = array();	
	}		
	
	private function _load_by_id( $id ) {
		$qry = new SQL( self::getLoadQry() . "WHERE `place`.`pl_id` = '#plid'",
					array('plid' => $id)
					);
		$res = $qry->run('array');
		
		$this->_load_by_row( $res );
	}
	
	private function _load_by_row( $row ) {
		if( !is_array( $row ) ) {
			throw new Exception('MONSTRING_V2: _load_by_row krever dataarray!');
		}
		// Beregn type
		if( 0 == $row['pl_fylke'] ) {
			$this->setType('kommune');
		} elseif( 123456789 == $row['pl_fylke'] ) {
			$this->setType('land');
		} else {
			$this->setType('fylke');
		}
		
		
		// Sett opp fylkesmønstringen
		if( 'fylke' == $this->getType() ) {
			$this->setFylke( $row['pl_fylke'] );
		} elseif( 'land' == $this->getType() ) {
			
		} else {
			if( null == $row['k_ids'] ) {
				$this->setKommuner( array() );
			} else {
				$this->setKommuner( explode(',', $row['k_ids'] ) );
			}
		}
		$this->setId( $row['pl_id'] );
		$this->setNavn( utf8_encode($row['pl_name']) );
		$this->setStart( $row['pl_start'] );
		$this->setStop( $row['pl_stop'] );
		$this->setFrist1( $row['pl_deadline'] );
		$this->setFrist2( $row['pl_deadline2'] );
		$this->setSesong( $row['season'] );
		$this->setSted( utf8_encode( $row['pl_place'] ) );
		$this->_setSkjemaId( $row['pl_form'] );
		$this->setPublikum( $row['pl_public'] );
		$this->setUregistrerte( $row['pl_missing'] );

		// SET PATH TO BLOG
		if( isset( $row['pl_link'] ) || ( isset( $row['pl_link'] ) && empty( $row['pl_link'] ) ) ) {
			$this->setPath( $row['pl_link'] );
		} 
		// Backwards compat
		else {
			if( 'fylke' == $this->getType() ) {
				$this->setPath( $this->getFylke()->getLink() );
			} elseif( 'land' == $this->getType() ) {
				$this->setPath( 'festivalen' );
			} else {
				$this->setPath( 'pl'. $this->getId() );
			}
		}
	}
	
	
	/**
	 * Sett attributt
	 * Sett egenskaper som for enkelhets skyld kan følge mønstringen et lite stykke
	 * Vil aldri kunne lagres
	 *
	 * @param string $key
	 * @param $value
	 *
	 * @return innslag
	**/
	public function setAttr( $key, $value ) {
		$this->attributes[ $key ] = $value;
		return $this;
	}
	
	/**
	 * Hent attributt
	 *
	 * @param string $key
	 *
	 * @return value
	**/
	public function getAttr( $key ) {
		return isset( $this->attributes[ $key ] ) ? $this->attributes[ $key ] : false;
	}
		
	/**
	 * Sett ID
	 *
	 * @param integer id 
	 *
	 * @return $this
	**/
	public function setId( $id ) {
		$this->id = $id;
		return $this;
	}
	/**
	 * hent ID
	 * @return integer $id
	**/
	public function getId() {
		return $this->id;
	}	
	
	/**
	 * Sett type
	 *
	 * @param integer $type
	 *
	 * @return $this;
	**/
	public function setType( $type ) {
		$this->type = $type;
		return $this;
	}
	/**
	 * Hent type
	 *
	 * @return innslag_type $type
	**/
	public function getType( ) {
		return $this->type;
	}	
	
	/**
	 * Sett path
	 *
	 * @param string $path
	 *
	 * @return $this;
	**/
	public function setPath( $path ) {
		$this->path = rtrim( trim($path, '/'), '/');
		return $this;
	}
	/**
	 * Hent relativ path for mønstringen
	 *
	 * @return string $path
	**/
	public function getPath( ) {
		return $this->path;
	}
	
	/**
	 * Sett navn
	 *
	 * @param string $navn
	 *
	 * @return $this
	**/
	public function setNavn( $navn ) {
		$this->navn = $navn;
		return $this;
	}
	/**
	 * hent navn
	 * @return string $navn
	**/
	public function getNavn() {
		return $this->navn;
	}
	
	/**
	 * Sett sted
	 *
	 * @param string $sted
	 *
	 * @return $this
	**/
	public function setSted( $sted ) {
		$this->sted = $sted;
		return $this;
	}
	/**
	 * hent sted
	 * @return string $sted
	**/
	public function getSted() {
		return $this->sted;
	}
	
	/**
	 * Hent antall uregistrerte deltakere
	 *
	 * @return int uregistrerte
	**/
	public function getUregistrerte() {
		return $this->uregistrerte;
	}


	/**
	 * Sett antall uregistrerte deltakere
	 *
	 * @param int antall uregistrerte deltakere
	 * @return $this
	**/
	public function setUregistrerte( $uregistrerte ) {
		$this->uregistrerte = $uregistrerte;
		return $this;
	}
	
	/**
	 * Hent antall publikummere
	 *
	 * @return int antall_publikum
	**/
	public function getPublikum() {
		return $this->publikum;
	}
	/**
	 * Sett antall publikummere
	 *
	 * @param int antall publikummere
	 * @return $this
	**/
	public function setPublikum( $publikum ) {
		$this->publikum = $publikum;
		return $this;
	}

	/**
	 * Sett start-tidspunkt
	 *
	 * @param unixtime $start
	 * @return $this
	**/
	public function setStart( $unixtime ) {
		$this->start = $unixtime;
		return $this;
	}
	/**
	 * Hent start-tidspunkt
	 *
	 * @return DateTime $start
	**/
	public function getStart() {
		if( null == $this->start_datetime ) {
			$this->start_datetime = new DateTime();
			$this->start_datetime->setTimestamp( $this->start );
		}
		return $this->start_datetime;
	}
	
	/**
	 * Sett stopp-tidspunkt
	 *
	 * @param unixtime $stop
	 * @return $this
	**/
	public function setStop( $unixtime ) {
		$this->stop = $unixtime;
		return $this;
	}
	/**
	 * Hent stopp-tidspunkt
	 *
	 * @return DateTime $stop
	**/
	public function getStop() {
		if( null == $this->stop_datetime ) {
			$this->stop_datetime = new DateTime();
			$this->stop_datetime->setTimestamp( $this->stop );
		}
		return $this->stop_datetime;
	}
	
	/**
	 * Sett frist 1-tidspunkt
	 *
	 * @param unixtime $frist1
	 * @return $this
	**/
	public function setFrist1( $unixtime ) {
		$this->frist_1 = $unixtime;
		return $this;
	}
	/**
	 * Hent frist 1-tidspunkt
	 *
	 * @return DateTime $frist1
	**/
	public function getFrist1() {
		if( null == $this->frist_1_datetime ) {
			$this->frist_1_datetime = new DateTime();
			$this->frist_1_datetime->setTimestamp( $this->frist_1 );
		}
		return $this->frist_1_datetime;
	}	 
	/**
	 * Sett frist 2-tidspunkt
	 *
	 * @param unixtime $frist2
	 * @return $this
	**/
	public function setFrist2( $unixtime ) {
		$this->frist_2 = $unixtime;
		return $this;
	}
	/**
	 * Hent frist 2-tidspunkt
	 *
	 * @return DateTime $frist2
	**/
	public function getFrist2() {
		if( null == $this->frist_2_datetime ) {
			$this->frist_2_datetime = new DateTime();
			$this->frist_2_datetime->setTimestamp( $this->frist_2 );
		}
		return $this->frist_2_datetime;
	}
	
	
	/**
	 * Er dette en singelmønstring (altså ikke fellesmønstring
	 *
	 * return bool
	**/
	public function erSingelmonstring() {
		return 1 == sizeof( $this->kommuner_id );
	}
	/**
	 * Er dette en fellesmønstring 
	 *
	**/
	public function erFellesmonstring() {
		return 1 < sizeof( $this->kommuner_id );
	}

	/**
	 * getAntallKommuner
	 * Hent ut antall kommuner mønstringen har uten å laste inn objekter
	 * 
	 * @return integer
	**/
	public function getAntallKommuner() {
		if( $this->getType() !== 'kommune' ) {
			throw new Exception('MONSTRING_V2: getAntallKommuner kan kun kjøres på lokalmønstringer!');
		}
		return sizeof( $this->kommuner_id );
	}
	
	/**
	 * harKommune
	 * Sjekker om en mønstring har en gitt kommune uten å laste inn objekter
	 *
	 * @param integer / kommune-object
	 * @return bool
	**/
	public function harKommune( $kommune ) {
		if( is_numeric( $kommune ) ) {
			$kommuneId = $kommune;
		} else {
			$kommuneId = $kommune->getId();
		}
		return in_array($kommuneId, $this->kommuner_id );
	}
	/**
	 * Sett kommuner
	 *
	 * @param array $kommuner_id
	 * @return $this
	**/
	public function setKommuner( $kommuner_id ) {
		$this->kommuner_id = $kommuner_id;
		return $this;
	}
	
	/**
	 * Hent kommune
	 *
	 * @return object $kommune
	**/
	public function getKommune() {
		if( !$this->erSingelmonstring() ) {
			throw new Exception('MONSTRING_V2: Kan ikke bruke getKommune på mønstringer med flere kommuner');
		}
		// Quickfix 22.09.2016
		return $this->getKommuner()->first();
		
		if( null == $this->kommune ) {
			$this->kommune = new kommune( $this->kommune_id );
		}
		return $this->kommune;
	}
	
	/**
	 * Hent alle kommuner for en mønstring
	 *
	**/
	public function getKommuner() {
		require_once('UKM/kommuner.collection.php');
		
		if( null == $this->kommuner ) {
			if( 'kommune' == $this->getType() ) {
				$this->kommuner = new kommuner();
				foreach( $this->kommuner_id as $id ) {
					$this->kommuner->add( new kommune( $id ) );
				}
			} elseif( 'fylke' == $this->getType() ) {
				$this->kommuner = $this->getFylke()->getKommuner();
			}
		}
		return $this->kommuner;
	}

	/**
	 * Sett fylkeID
	 *
	 * @param int $fylke_id
	 * @return $this
	 * 
	**/
	public function setFylke( $fylke_id ) {
		$this->fylke_id = $fylke_id;
		return $this;
	}
	
	/**
	 * Har fylket et skjema?
	 *
	**/
	public function harSkjema() {
		try {
			$skjema = $this->getSkjema();
			return sizeof( $skjema->getQuestions() ) > 0;
		} catch( Exception $e ) {
			return false;
		}
	}
	/**
	 * Sett skjema
	 *
	 * @param skjema $skjema eller int $skjema_id
	 * @return $this
	**/
	public function setSkjema( $skjema ) {
		if( $this->getType() == 'kommune' ) {
			throw new Exception('Mønstring: lokalmønstringer kan ikke ha skjema');
		}
		$skjema_id = is_int( $skjema ) ? $skjema : $skjema->getId();

		$this->_setSkjemaId( $skjema_id );
		return $this;
	}
	
	/**
	 * Hent skjema
	 *
	 * @return skjema $skjema
	**/
	public function getSkjema( $fylke=null ) {
		require_once('UKM/monstring_skjema.class.php');
		if( $this->getType() == 'land' ) {
			throw new Exception('Videresendingsskjema ikke støttet for UKM-festivalen');
		}
		if( $this->skjema == null ) {
			if( $this->getType() == 'fylke' ) {
				$this->skjema = new monstring_skjema( $this->getFylke()->getId() );
			} else {
				$this->skjema = new monstring_skjema( $fylke==null ? $this->getFylke()->getId() : $fylke, $this->getId() );
			}
		}
		return $this->skjema;
	}

	/**
	 * Hent ut fylkesmønstringene lokalmønstringen kan sende videre til
	**/
	public function getFylkesmonstringer() {
		if( $this->getType() !== 'kommune' ) {
			throw new Exception('MONSTRING_V2: Fylkesmønstringer kan ikke videresende til fylkesmønstringer');
		}
		require_once('UKM/monstringer.collection.php');
		if( null === $this->fylkesmonstringer ) {
			$this->fylkesmonstringer = [];
			foreach( $this->getKommuner() as $kommune ) {
				if( !isset( $this->fylkesmonstringer[ $kommune->getFylke()->getId() ] ) ) {
					$this->fylkesmonstringer[ $kommune->getFylke()->getId() ] = monstringer_v2::fylke( $kommune->getFylke(), $this->getSesong() );
				}
			}
		}
		return $this->fylkesmonstringer;
	}
	
	/**
	 * Sett skjemaId
	 *
	 * @param int $skjema_id
	 * @return $this
	**/
	private function _setSkjemaId( $skjema_id ) {
		$this->skjema_id = $skjema_id;
	}
	
	
	
	/**
	 * Sett sesong
	 *
	 * @param int $seson
	 * @return $this
	**/
	public function setSesong( $sesong ) {
		$this->sesong = $sesong;
		return $this;
	}
	/**
	 * Hent sesong
	 *
	 * @return int $sesong
	**/
	public function getSesong() {
		return $this->sesong;
	}
	
	/**
	 * Hent fylke
	 *
	 * @return fylke
	**/
	public function getFylke() {
		if( null == $this->fylke ) {
			if( null == $this->fylke_id && 'kommune' == $this->getType() ) {
				$first_kommune = $this->getKommuner()->first();
				if( null == $first_kommune || !is_object( $first_kommune ) ) {
					throw new Exception('Beklager, klarte ikke å finne en kommune som tilhører denne mønstringen');
				}
				$this->setFylke( $first_kommune->getFylke()->getId() );
			}
			$this->fylke = fylker::getById( $this->fylke_id );
		}
		return $this->fylke;
	}
	
	/**
	 * Hent program for gitt mønstring
	 *
	 * @return array forestilling.class
	 *
	**/
	public function getProgram() {
		if( null !== $this->program ) {
			return $this->program;
		}
		require_once('UKM/forestillinger.collection.php');
		$this->program = new forestillinger( $this->getContext() );
		return $this->program;
	}
	
	/**
	 * Hent innslag påmeldt mønstringen
	 *
	 * @return innslag collection
	**/
	public function getInnslag() {
		if( null == $this->innslag ) {
			$this->innslag = new innslag_collection( $this->getContext() );
		}
		return $this->innslag;
	}
	
	/**
	 * Hent lenke for mønstringen
	 *
	 * @return string url
	**/
	public function getLink() {
		return '//'. UKM_HOSTNAME .'/'. $this->getPath() .'/';
	}
		
	/**
	 * Hent hvilke innslagstyper som kan være påmeldt denne mønstringen
	 *
	 * @return Collection innslagstyper 
	**/
	public function getInnslagTyper() {
		if( null == $this->innslagTyper ) {
			$this->innslagTyper = new innslag_typer();
			$sql = new SQL("SELECT `bt_id`
							FROM `smartukm_rel_pl_bt`
							WHERE `pl_id` = '#pl_id'
							ORDER BY `bt_id` ASC",
						   array('pl_id'=> $this->getId() )
						  );
			$res = $sql->run();
			while( $r = SQL::fetch( $res ) ) {
				if( 1 == $r['bt_id'] ) {
					foreach( innslag_typer::getAllScene() as $type ) {
						$this->innslagTyper->add( $type );
					}
				} else {					
					if(9 == $r['bt_id']) {
						$r['bt_id'] = 8;
					}
					if(!$this->innslagTyper->find($r['bt_id'] )) {
						$this->innslagTyper->addById( $r['bt_id'] );
					}
				}
			}
			if( $this->innslagTyper->getAntall() == 0 ) {
				foreach( innslag_typer::getAllScene() as $type ) {
					$this->innslagTyper->add( $type );
				}
				$this->innslagTyper->add( innslag_typer::getByName('video') );
				$this->innslagTyper->add( innslag_typer::getByName('utstilling') );
			}
			if( $this->innslagTyper->getAntall() == 0 ) {
				foreach( innslag_typer::getAllScene() as $type ) {
					$this->innslagTyper->add( $type );
				}
				$this->innslagTyper->add( innslag_typer::getByName('video') );
				$this->innslagTyper->add( innslag_typer::getByName('utstilling') );
			}
			if( $this->innslagTyper->getAntall() == 0 ) {
				foreach( innslag_typer::getAllScene() as $type ) {
					$this->innslagTyper->add( $type );
				}
				$this->innslagTyper->add( innslag_typer::getByName('video') );
				$this->innslagTyper->add( innslag_typer::getByName('utstilling') );
			}
		}
		return $this->innslagTyper;
	}
	
	/**
	 * getKontaktpersoner
	 * Henter alle kontaktpersoner som collection
	 *
	 * @return collection $kontaktpersoner
	**/
	public function getKontaktpersoner() {
		if( null == $this->kontaktpersoner ) {
			$this->_loadKontaktpersoner();
		}
		return $this->kontaktpersoner;
	}
	
	private function _loadKontaktpersoner() {
		require_once('UKM/kontaktpersoner.collection.php');
		$this->kontaktpersoner = new kontaktpersoner( $this->getId() );
		return $this;
	}
	
	/**
	 * getStatistikk
	 * Hent et statistikkobjekt relatert til denne mønstringen
	 *
	 * @return statistikk
	**/
	public function getStatistikk() {
		$this->statistikk = new statistikk();
		
		if('kommune' == $this->getType()) {
			$this->statistikk->setKommune( $this->getKommuner()->getIdArray() );
		} elseif('fylke' == $this->getType() ) {
			$this->statistikk->setFylke( $this->getFylke()->getId() );
		} else {
			$this->statistikk->setLand();
		}
		return $this->statistikk;
	}
	
	public function erRegistrert() {
		return $this->start > 0;
	}
	
	public function erStartet() {
		return time() > $this->getStart()->getTimestamp();
	}
	
	public function erAktiv() {
		return $this->erStartet() && !$this->erFerdig();
	}
	public function erFerdig() {
		return time() > $this->getStop()->getTimestamp();
	}
	
	public function erPameldingApen( $frist = 'begge' ) {
		if( $frist == 1 || $frist == 'frist_1' ) {
			return time() < $this->getFrist1()->getTimestamp();
		}
		if( $frist == 2 || $frist == 'frist_2' ) {
			return time() < $this->getFrist2()->getTimestamp();
		}
		$res = time() < $this->getFrist1()->getTimestamp() || time() < $this->getFrist2()->getTimestamp();
		return $res;
	}
	
	public function erVideresendingApen() {
		return time() < $this->getFrist1()->getTimestamp() && time() > $this->getFrist2()->getTimestamp();
	}
	
	/**
	 * erOslo
	 * Returnerer om fylket er Oslo.
	 * Brukes i hovedsak til å velge mellom kommune eller bydel i GUI
	 *
	 * @return bool
	**/
	public function erOslo() {
		return $this->getFylke()->getId() == 3;
	}
	
	/**
	 * Hvor mange dager varer mønstringen?
	 *
	 * @return int $dager
	**/
	public function getAntallDager() {
		$start = explode('.', date('d.m.Y.H.i', $this->getStart()->getTimestamp()));
		$stop = explode('.', date('d.m.Y.H.i', $this->getStop()->getTimestamp()));
		
		$maned = $start[1];
		$ar	   = $start[2];
		
		if($stop[0] >= $start[0]) {
			for($i=$start[0]-1; $i<$stop[0]; $i++)
				$days[($i+1).'.'.$maned] = date('N', mktime(0,0,0,$maned, $i+1, $ar));
				
			$this->dager = sizeof($days);
		## Sluttdato er mindre enn startdato, ergo er sluttdato neste måned!
		} else {
			for($i=$start[0]-1; $i<cal_days_in_month(CAL_GREGORIAN, $maned, $ar); $i++)
				$days[($i+1).'.'.$maned] = date('N', mktime(0,0,0,$maned, $i+1, $ar));
			for($i=0; $i<$stop[0]; $i++) 
				$days[($i+1).'.'.$maned+1] = date('N', mktime(0,0,0,$maned+1, $i+1, $ar));
			
			$this->dager = sizeof($days);
		}
		
		return $this->dager;
	}
	
	/**
	 * Hvor mange netter varer mønstringen?
	 *
	 * @return int $dager
	**/
	public function getNetter() {
		if( isset( $this->netter ) ) {
			return $this->netter;
		}
		$netter = array();
		
		if( $this->getStart()->format('m') < $this->getStop()->format('m') ) {
			$dager_ny_mnd 		= $this->getStart()->format('d');
			$dager_forrige_mnd = cal_days_in_month( 
				CAL_GREGORIAN, 
				$this->getStart()->format('m'),
				$this->getStart()->format('Y')
			)
			- $this->getStart()->format('d');
			$num_dager = $dager_ny_mnd + $dager_forrige_mnd;
		} else {
			$num_dager = $this->getStop()->format('d') - $this->getStart()->format('d');
		}
		
		$crnt = new stdClass();
		$crnt->dag = (int)date('d', $this->getStart()->getTimestamp());
		$crnt->mnd= (int)date('m', $this->getStart()->getTimestamp());
		$crnt->ar  = (int)date('Y', $this->getStart()->getTimestamp());
		
		for( $i=0; $i < $num_dager; $i++ ) {	
			if( $crnt->dag > cal_days_in_month( CAL_GREGORIAN, $crnt->mnd, $crnt->ar ) ) {
				$crnt->dag = 1;
				$crnt->mnd++;
			}
			$crnt->timestamp = strtotime( $crnt->dag.'-'.$crnt->mnd.'-'.$crnt->ar );
			$netter[] = clone $crnt;
		
			$crnt->dag++;
		}
		$this->netter = $netter;
		return $this->netter;
	}


	/**
	 * eksisterer
	 * 
	 * @return bool
	**/
	public function eksisterer() {
		return !is_null( $this->id );
	}	
	
	protected function _resetKommuner() {
		$this->kommuner = null;
	}
	
	public function getContext() {
		if( 'land' == $this->getType() ) {
			$context = context::createMonstring(
				$this->getId(),			// Mønstring id
				$this->getType(),		// Møntring type
				$this->getSesong(),		// Mønstring sesong
				false,					// Mønstring fylke ID
				false					// Mønstring kommune ID array
			);
		} else {
			$context = context::createMonstring(
				$this->getId(),						// Mønstring id
				$this->getType(),					// Møntring type
				$this->getSesong(),					// Mønstring sesong
				$this->getFylke()->getId(),			// Mønstring fylke ID
				$this->getKommuner()->getIdArray()	// Mønstring kommune ID array
			);
		}
		return $context;
	}

	/**
	 * Reset personer collection (kun på objektbasis)
	 *
	**/
	public function resetInnslagCollection() {
		$this->innslag = null;
		return $this;
	}

/* UTGÅR	
	public function getNominerteFraMeg() {
		require_once('UKM/nominasjoner.collection.php');
		if( null == $this->nominert_fra_meg ) {
			if( $this->getType() == 'kommune' ) {
				$geografi = $this->getKommune();
			} elseif( $this->getType() == 'fylke' ) {
				$geografi = $this->getFylke();
			} else {
				$geografi = null;
			}
			$this->nominert_fra_meg = new nominert_fra(
				$this->getId(), 
				$this->getType(), 
				$geografi,
				$this->getSesong()
			);
		}
		return $this->nominert_fra_meg;
	}
	
	public function getNominerteTilMeg() {
		require_once('UKM/nominasjoner.collection.php');
		if( null == $this->nominert_til_meg ) {
			if( $this->getType() == 'kommune' ) {
				$geografi = $this->getKommune();
			} elseif( $this->getType() == 'fylke' ) {
				$geografi = $this->getFylke();
			} else {
				$geografi = null;
			}
			$this->nominert_fra_meg = new nominert_til(
				$this->getId(), 
				$this->getType(), 
				$geografi,
				$this->getSesong()
			);
		}
		return $this->nominert_til_meg;
	}
*/
}
?>
