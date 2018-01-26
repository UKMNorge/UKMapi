<?php
require_once('UKM/sql.class.php');
require_once('UKM/person.class.php');
require_once('UKM/write_person.class.php');
require_once('UKM/personer.collection.php');
require_once('UKM/inc/ukmlog.inc.php');
require_once('UKM/monstring.class.php');
require_once('UKM/kommune.class.php');
require_once('UKM/fylker.class.php');
require_once('UKM/titler.collection.php');
require_once('UKM/tittel.class.php');
require_once('UKM/artikler.collection.php');

require_once('UKM/v1_innslag.class.php');

class innslag_v2 {
	var $id = null;
	var $navn = null;
	var $type = null;
	var $beskrivelse = null;
	var $kommune_id = null;
	var $kommune = null;
	var $fylke = null;
	var $filmer = false;
	var $program = null;
	var $kategori = null;
	var $sjanger = null;
	var $playback = null;
	var $personer_collection = null;
	var $artikler_collection = null;
	var $attributes = null;
	var $sesong = null;
	var $avmeldbar = false;
	var $advarsler = null;

	var $erVideresendt = null;
	
	var $kontaktperson_id = null;
	var $kontaktperson = null;

	public function __construct( $bid_or_row, $select_also_if_not_completed=false ) {
		$this->attributes = array();
		if( null == $bid_or_row || empty( $bid_or_row ) ) {
			throw new Exception('INNSLAG_V2: Konstruktør krever b_id som numerisk verdi eller array med innslag-data. Gitt '. var_export( $bid_or_row, true ) );
		}
		if( is_numeric( $bid_or_row ) ) {
			$this->_loadByBID( $bid_or_row, $select_also_if_not_completed );
		} else {
			$this->_loadByRow( $bid_or_row );
		}
	}

	public static function getLoadQuery() {
		return "SELECT `smartukm_band`.*, 
					   `td`.`td_demand`,
					   `td`.`td_konferansier`
				FROM `smartukm_band`
				LEFT JOIN `smartukm_technical` AS `td` ON (`td`.`b_id` = `smartukm_band`.`b_id`)";
	}

	/**
	 * Last inn objekt fra innslagsID
	 *
	 * @param integer b_id 
	 * @return this;
	 *
	**/
	/* OBS OBS OBS: DENNE SKAL VEL IKKE BRUKES ?!?! */
	static function getLoadQry() {
		return "SELECT `smartukm_band`.*, 
					   `td`.`td_demand`,
					   `td`.`td_konferansier`
				";
	}

	private function _loadByBID( $b_id, $select_also_if_not_completed ) {
		$SQL = new SQL(self::getLoadQuery()."
						WHERE `smartukm_band`.`b_id` = '#bid' 
						#select_also_if_not_completed",
					array('bid' => $b_id, 
						 'select_also_if_not_completed' => ($select_also_if_not_completed ? '' : "AND `smartukm_band`.`b_status` = 8" )
						 )
					);
		$row = $SQL->run('array');

		$this->_loadByRow( $row );
		return $this;
	}
	/**
	 * Last inn objekt fra databaserad
	 *
	 * @param database_row $row
	 * @return $this;
	**/
	private function _loadByRow( $row ) {
		$this->setId( $row['b_id'] );
		if( null == $this->getId() ) {
			throw new Exception("INNSLAG_V2: Klarte ikke å laste inn innslagsdata");
		}
		$this->setNavn( utf8_encode( $row['b_name'] ) );
		$this->setType( $row['bt_id'], $row['b_kategori'] );
		$this->setBeskrivelse( stripslashes( utf8_encode($row['b_description']) ) );
		$this->setKommune( $row['b_kommune'] );
		$this->setKategori( utf8_decode( $row['b_kategori'] ) );
		$this->setSjanger( (string) utf8_encode($row['b_sjanger'] ));
		$this->setKontaktpersonId( $row['b_contact'] );
		$this->_setSubscriptionTime( $row['b_subscr_time'] );
		$this->setStatus( $row['b_status'] );

		if( isset( $row['order'] ) ) {
			$this->setAttr('order', $row['order'] );
		} else {
			$this->setAttr('order', null );
		}

		$this->setSesong( $row['b_season'] );

		return $this;
	}
	
	/**
	 * Hent personer i innslaget
	 *
	 * @return array $personer
	**/
	public function getPersoner() {
		if( null == $this->personer_collection ) {
			$this->personer_collection = new personer( $this->getId(), $this->getType() );
		}
		return $this->personer_collection;	
	}
	
		
	/**
	 * Hent alle bilder tilknyttet innslaget
	 *
	 * @return array $bilder
	**/
	public function getBilder() {
		require_once('UKM/bilder.class.php');
		$this->bilder = new bilder( $this->getId() );
		
		return $this->bilder;
	}
	
	/**
	 * Hent alle filmer fra UKM-TV (tilknyttet innslaget)
	 *
	 * @return array UKM-TV
	**/
	public function getFilmer() {
		if( !is_array( $this->filmer ) ) {
			require_once('UKM/tv.class.php');
			require_once('UKM/tv_files.class.php');
			
			$tv_files = new tv_files( 'band', $this->getId() );
			while($tv = $tv_files->fetch()) {
				$this->filmer[$tv->id] = $tv;
			}
		}
		return $this->filmer;
	}

	/**
	 * Hent relaterte artikler
	 *
	 * @return artikkel_collection
	**/
	public function getArtikler() {
		if( null == $this->artikler_collection ) {
			$this->artikler_collection = new artikler( $this->getId() );
		}
		return $this->artikler_collection;
	}		
	private function _getNewOrOld($new, $old) {
		return null == $this->$new ? $this->info[$old] : $this->$new;
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
	 * Hent ID
	 * @return integer $id
	**/
	public function getId() {
		return $this->id;
	}
	
	/**
	 * Sett status
	 *
	 * @param integer status 
	 *
	 * @return $this
	**/
	public function setStatus( $status ) {
		$this->status = $status;
		return $this;
	}
	/**
	 * Hent status
	 * @return integer $status
	**/
	public function getStatus() {
		return $this->status;
	}
	
	
	/**
	 * Sett navn på innslag
	 *
	 * @param string $navn
	 * @return $this
	**/
	public function setNavn( $navn ) {
		$this->navn = $navn;
		return $this;
	}
	/**
	 * Hent navn på innslag
	 *
	 * @return string $navn
	**/
	public function getNavn() {
		if( empty( $this->navn ) ) {
			return 'Innslag uten navn';
		}
		return $this->navn;
	}
	
	/**
	 * Sett type
	 * Hvilken kategori faller innslaget inn under?
	 *
	 * @param integer $type
	 * @param string $kategori
	 *
	 * @return $this;
	**/
	public function setType( $type, $kategori=false ) {
		require_once('UKM/innslag_typer.class.php');
		$this->type = innslag_typer::getById( $type, $kategori );
		return $this;
	}
	/**
	 * Hent type
	 * Hvilken kategori innslaget faller inn under
	 *
	 * @return innslag_type $type
	**/
	public function getType( ) {
		return $this->type;
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
	 * Sett beskrivelse av innslag
	 *
	 * @param beskrivelse
	 * @return $this
	**/
	public function setBeskrivelse( $beskrivelse ) {
		$this->beskrivelse = $beskrivelse;
		return $this;
	}
	/**
	 * Hent beskrivelse
	 *
	 * @return string $beskrivelse
	**/
	public function getBeskrivelse() {
		return $this->beskrivelse;
	}

	
	/**
	 * Sett kommune
	 *
	 * @param kommune_id
	 * @return $this
	**/
	public function setKommune( $kommune_id ) {
		$this->kommune_id = $kommune_id;
		return $this;
	}
	/**
	 * Hent kommune
	 *
	 * @return object $kommune
	**/
	public function getKommune() {
		if( null == $this->kommune ) {
			$this->kommune = new kommune( $this->kommune_id );
		}
		return $this->kommune;
	}
	
	/**
	 * Sett fylke
	 * Skal ikke skje - sett alltid kommune!
	 * 
	**/
	public function setFylke( $fylke_id ) {
		throw new Exception('INNSLAG V2: setFylke() er ikke mulig. Bruk setKommune( $kommune_id )');
	}
	
	/**
	 * Hent fylke
	 *
	 * @return fylke
	**/
	public function getFylke() {
		if( null == $this->fylke ) {
			$this->fylke = $this->getKommune()->getFylke();
		}
		return $this->fylke;
	}
		
	
	/**
	 * Set subscriptionTime
	 *
	 * @param unixtimestamp subscriptiontime
	 * @return $this;
	**/
	public function _setSubscriptionTime( $unixtime ) {
		$this->subscriptionTime = $unixtime;
		$this->_calcAvmeldbar();
		return $this;
	}
	
	/**
	 * avmeldbar Periode - hvor lang tid har innslaget?
	 * Hvor mange dager skal innslaget få for å fullføre sin påmelding?
	 *
	 * @return int dager
	**/
	public static function avmeldbarPeriode() {
		return 5;
	}
	
	/**
	 * Skal innslaget være mulig å melde av?
	 * De 5 første dagene bør innslaget få lov til å fullføre sin påmelding
	 * uten at arrangører avmelder
	 *
	 * @param integer subscriptiontime as unixtime
	 */
	private function _calcAvmeldbar( ) {
		if( time() > $this->getAvmeldbar() ) {
			$this->avmeldbar = true;
		} else {
			$this->avmeldbar = false;
		}
		return $this;
	}
	
	/**
	 * Er innslaget være mulig å melde av?
	 * De 5 første dagene bør innslaget få lov til å fullføre sin påmelding
	 * uten at arrangører avmelder
	 *
	 * @return bool
	 */
	public function erAvmeldbar() {
		return $this->avmeldbar;
	}

	/**
	 * Er innslaget være mulig å melde av?
	 * De 5 første dagene bør innslaget få lov til å fullføre sin påmelding
	 * uten at arrangører avmelder
	 *
	 * @return bool
	 */
	public function getAvmeldbar() {
		$subscriptiontime = $this->getSubscriptionTime();
		if( is_object( $subscriptiontime ) ) {
			return $subscriptiontime->getTimestamp() + (self::avmeldbarPeriode() * 24 * 60 * 60 );
		}
		return false;
	}
	
	/**
	 * Sett innslagets kategori
	 *
	 * @param string $kategori
	 * @return $this;
	**/
	public function setKategori( $kategori ) {
		// Hvis scene-innslag, bruk detaljert info
		if( 1 == $this->getType()->getId() ) {
			$this->kategori = $this->getType()->getNavn();
		}
		$this->kategori = $kategori;
		return $this;
	}
	/**
	 * Hent innslagets kategori
	 *
	 * @return string $kategori
	**/
	public function getKategori() {
		return $this->kategori;
	}
	
	/** 
	 * Sett innslagets sjanger
	 * 
	 * @param string $sjanger
	 * @return $this
	**/
	public function setSjanger( $sjanger ) {
		$this->sjanger = $sjanger;
		return $this;
	}
	/**
	 * Hent innslagets sjanger
	 *
	 * @return string $sjanger
	**/
	public function getSjanger() {
		return $this->sjanger;
	}
	
	/**
	 * Hent innslagets kategori og sjanger som én streng
	 * Hvis ett av feltene er tomme returneres kun det andre
	 *
	 * @return string $kategori ( - ) $sjanger
	 *
	**/
	public function getKategoriOgSjanger() {
		if( !empty( $this->getKategori() ) && !empty( $this->getSjanger() ) ) {
			return $this->getKategori() .' - '. $this->getSjanger();	
		}
		
		// En av de er tomme, returner "kun" den andre :)
		return $this->getKategori() . $this->getSjanger();
	}
	
	
	/**
	 * Sett kontaktperson ID
	 *
	 * @param object person
	 * @return $this
	**/
	public function setKontaktpersonId( $person_id ) {
		$this->kontaktperson_id = $person_id;
		return $this;
	}
	/**
	 * Hent kontaktpersonId
	 *
	 * @return int $kontaktpersonid
	 *
	**/
	public function getKontaktpersonId() {
		return (int) $this->kontaktperson_id;
	}
	/**
	 * Sett kontaktperson 
	 *
	 * @param $kontaktperson
	 * @return $this
	**/
	public function setKontaktperson( $person ) {
		$this->kontaktperson = $person;
		return $this;
	}
	
	/**
	 * Hent kontaktperson
	 *
	 * @return object person $kontaktperson
	**/
	public function getKontaktperson() {
		if( null == $this->kontaktperson ) {
			if( 'write_innslag' == get_class($this) ) {
				$person = new write_person( $this->getKontaktpersonId() );
			}
			else {
				$person = new person_v2( $this->getKontaktpersonId() );
			}
			$this->setKontaktperson( $person );
		}
		return $this->kontaktperson;
	}
	
	/**
	 * Hent playback
	 *
	 * @return playback_collection
	 *
	**/
	public function getPlayback() {
		if( null == $this->playback ) {
			$this->playback = new playback_filer( $this->getId() );
		}
		return $this->playback;
	}

	/**
	 * Hent påmeldingstidspunkt
	 *
	 * @return DateTime tidspunkt
	**/
	public function getSubscriptionTime() {
		//
		// OBS OBS OBS OBS OBS
		//
		// AVVIKER FRA V1-kode
		// Pre UKMdelta var korrekt påloggingstidspunkt for tittelløse innslag
		// lagret i loggen. Sjekker kun denne loggtabellen hvis innslaget ikke har 
		// b_subscr_time
		if( empty( $this->subscriptionTime ) ) {
			$qry = new SQL("SELECT `log_time` FROM `ukmno_smartukm_log`
							WHERE `log_b_id` = '#bid'
							AND `log_code` = '22'
							ORDER BY `log_id` DESC",
							array( 'bid' => $this->getId() ) );
			$this->subscriptionTime = $qry->run('field','log_time');
		}
		
		$datetime = new DateTime();
		$datetime->setTimestamp( $this->subscriptionTime );
		return $datetime;

	}
	 
	/**
	 * Hent program for dette innslaget på gitt mønstring
	 *
	 * @param monstring $monstring
	 * @return list program
	 *
	**/
	public function getProgram( $monstring ) {
		if( !is_object( $monstring ) || 'monstring_v2' != get_class( $monstring ) ) {
			throw new Exception('INNSLAG_V2: Mønstring må være mønstrings-objekt! Gitt '. (is_object( $monstring ) ? get_class( $monstring ) : (is_array( $monstring )?'Array':'ukjent')));
		}
		if( null == $this->program ) {
			require_once('UKM/forestillinger.collection.php');
			$this->program = new program( 'innslag', $this->getId() );
			$this->program->setMonstringId( is_numeric( $monstring ) ? $monstring : $monstring->getId() );
		}
		return $this->program;
	}
	
	public function getTitler( $monstring ) {
		if( null == $this->titler ) {
			$this->titler = new titler( $this->getId(), $this->getType(), $monstring );
		}
		return $this->titler;
	}

	/**
	 * Sjekk om innslaget er videresendt fra lokalnivå.
	 *
	 * @return boolean
	 */
	public function erVideresendt() {
		if( null != $this->erVideresendt ) {
			return $this->erVideresendt;
		}

		$qry = new SQL("SELECT COUNT(*) FROM `smartukm_rel_pl_b` WHERE `b_id` = '#b_id'", 
			array('b_id' => $this->getId() ) );
		$res = $qry->run('field', 'COUNT(*)');
		if( $res > 1 ) {
			$this->erVideresendt = true;
			return true;
		}

		$qry = new SQL("SELECT COUNT(*) FROM `smartukm_fylkestep` WHERE `b_id` = '#b_id'",
			array('b_id' => $this->getId() ) );
		$res = $qry->run('field', 'COUNT(*)');
		if( $res > 0 ) {
			$this->erVideresendt = true;
			return true;
		}

		$this->erVideresendt = false;
		return false;
	}
	
	private function _calcAdvarsler( $monstring) {
		require_once('UKM/advarsler.collection.php');
		
		$this->advarsler = new advarsler();

		// Har 0 personer
		if( 0 == $this->getPersoner( $monstring )->getAntall()) {
			$this->advarsler->add( new advarsel( advarsel::create('personer', 'Innslaget har ingen personer' ) ) );
		}
		// Utstilling har mer enn 3 verk
		if( 'utstilling' == $this->getType()->getKey() && $this->getTitler( $monstring )->getAntall() > 3 ) {
			$this->advarsler->add( new advarsel( advarsel::create('titler', 'Innslaget har mer enn 3 kunstverk') ) );
		// Utstilling har ingen verk
		} elseif( 'utstilling' == $this->getType()->getKey() && $this->getTitler( $monstring )->getAntall() == 0) {
			$this->advarsler->add( new advarsel( advarsel::create('titler', 'Innslaget har ingen kunstverk') ) );
		// Innslaget har mer enn 3 titler
		} elseif( $this->getType()->harTitler() && $this->getTitler( $monstring )->getAntall() > 2 ) {
			$this->advarsler->add( new advarsel( advarsel::create('titler', 'Innslaget har mer enn 2 titler' ) ) );
		// Innslaget har ingen titler
		} elseif( $this->getType()->harTitler() && $this->getTitler( $monstring )->getAntall() == 0) {
			$this->advarsler->add( new advarsel( advarsel::create('titler', 'Innslaget har ingen titler, og derfor ingen varighet.') ) );
		}		
		// Innslaget har en varighet over 5 min
		if( $this->getType()->harTitler() && (5*60) < $this->getVarighet( $monstring )->getSekunder() ) {
			$this->advarsler->add( new advarsel( advarsel::create('titler', 'Innslaget er lengre enn 5 minutter ') ) );
		}
	}
	
	public function getVarighet( $monstring ) {
		return $this->getTitler( $monstring )->getVarighet();
	}
	
	// TODO: Load valideringsadvarsler fra b_status_text
	public function getAdvarsler( $monstring ) {
		if( null == $this->advarsler ) {
			$this->_calcAdvarsler( $monstring );
		}
		return $this->advarsler;
	}
	
	/**
	 * Sett attributt
	 * Sett egenskaper som for enkelhets skyld kan følge innslaget et lite stykke
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

}
