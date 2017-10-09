<?php
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
	var $sesong = null;
	var $innslag = null;
	var $path = null;
	var $kontaktpersoner = null;
	
	var $attributes = null;
	
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
		$this->path = $path;
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

		$this->program = new forestillinger( 'monstring', $this->getId() );

		return $this->program;
	}
	
	/**
	 * Hent innslag påmeldt mønstringen
	 *
	 * @return innslag collection
	**/
	public function getInnslag() {
		if( null == $this->innslag ) {
			$this->innslag = new innslag_collection( 'monstring', $this->getId() );
			if( 'land' == $this->getType() ) {
				$this->innslag->setContainerDataMonstring( $this->getId(), $this->getType(), $this->getSesong(), false, false );
			} else {
				$this->innslag->setContainerDataMonstring( $this->getId(), $this->getType(), $this->getSesong(), $this->getFylke()->getId(), $this->getKommuner()->getIdArray() );
			}
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
			while( $r = mysql_fetch_assoc( $res ) ) {
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
		$res = time() < $this->getFrist1()->getTimestamp() && time() < $this->getFrist2()->getTimestamp();
		return $res;
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
}
?>
