<?php
require_once('UKM/sql.class.php');
require_once('UKM/v1_person.class.php');

class person_v2 {
	var $context = null;
	
	var $id = null;
	var $fornavn = null;
	var $etternavn = null;
	var $mobil = null;
	var $rolle = null;
	var $epost = null;
    var $attributes = null;
    
    private $sensitivt = null;
	
	var $videresendtTil = null;
	
	public function __construct( $person ) {
		$this->attributes = [];
		if( is_numeric( $person ) ) {
			$this->_load_from_db( $person );
		} elseif( is_array( $person ) ) {
			$this->_load_from_array( $person );
		} else {
			throw new Exception(
				'PERSON_V2: Oppretting krever parameter $person som numerisk id eller array, fikk '. gettype($person) .'.',
				109001
			);
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
	 * @param string $key
	 * @return value
	**/
	public function getAttr( $key ) {
		return isset( $this->attributes[ $key ] ) ? $this->attributes[ $key ] : false;
	}
	
	public static function getLoadQuery() {
		return "SELECT * FROM `smartukm_participant` ";
	}
	
	public static function loadFromData( $fornavn, $etternavn, $mobil ) {
		$qry = new SQL(
			self::getLoadQuery() . "
			WHERE `p_firstname` = '#fornavn' 
			AND `p_lastname` = '#etternavn' 
			AND `p_phone` = '#mobil'", 
			[
				'fornavn' => $fornavn, 
				'etternavn' => $etternavn,
				'mobil' => $mobil
			]
		);
		$person_data = $qry->run('array');
		
		if( !$person_data ) {
			throw new Exception(
				'Beklager, fant ikke '. $fornavn .' '. $etternavn .' ('. $mobil .')',
				109004
			);
		}
		
		return new person_v2( $person_data );
	}
	
	private function _load_from_db( $person ) {
		$sql = new SQL(
			person_v2::getLoadQuery() . "	WHERE `p_id` = '#p_id'",
			array('p_id' => $person )
		);
		$res = $sql->run('array');
		return $this->_load_from_array( $res );
	}
	private function _load_from_array( $person ) {
		$this->setId( $person['p_id'] );
		$this->setFornavn( $person['p_firstname'] );
		$this->setEtternavn( $person['p_lastname'] );
		$this->setMobil( $person['p_phone'] );
		$this->setEpost( $person['p_email'] );
		$this->setFodselsdato( $person['p_dob'] );
		$this->setKommune( $person['p_kommune'] );
		if( array_key_exists('instrument', $person ) ) {
			$this->setRolle( $person['instrument'] );
		}
		if( array_key_exists('instrument_object', $person ) ) {
			$this->setRolleObject( json_decode( $person['instrument_object'] ) );
		}
		if( array_key_exists('pl_ids', $person ) ) {
			$this->setVideresendtTil( explode(',', $person['pl_ids']) );
		}
		if( array_key_exists('bt_id', $person ) ) {
			$this->_setBTID( $person['bt_id'] );
		}
	}
	
	/**
	 * Er videresendt
	 * Er personen videresendt til gitt mønstring?
	 *
	 * @param int $pl_id
	 * @return bool
	**/
	public function erVideresendt( $pl_id ) {
		if( is_object( $pl_id ) && ( 'monstring_v2' == get_class( $pl_id ) || 'write_monstring' == get_class( $pl_id ) || 'monstring' == get_class( $pl_id ) ) ) {
			$pl_id = $pl_id->getId();
		}

		if( in_array( $this->_getBTID(), array(1,4,5,8,9)) ) {
			return true;
		}
		if( null == $this->videresendtTil ){
			throw new Exception(
				'PERSON_V2 (p'. $this->getId() .'): Kan ikke svare om person er videresendt '.
				'på objekt som ikke er initiert med pl_ids (via collection?)',
				109002
			);
		}
		return in_array($pl_id, $this->getVideresendtTil() );
	}
	public function erVideresendtTil( $monstring ) {
		return $this->erVideresendt( $monstring );
	}

	
	/**
	 * Sett videresendt til
	 *
	 * @param array pl_ids
	 * @return $this
	**/
	public function setVideresendtTil( $videresendtTil ) {
		$this->videresendtTil = $videresendtTil;
		return $this;
	}
	/**
	 * Hent videresendt til
	 * 
	 * @return array $videresendtTil
	**/
	public function getVideresendtTil() {
		return $this->videresendtTil;
	}
	
	/**
	 * Sett id
	 *
	 * @param integer $id
	 * @return $this
	**/
	public function setId( $id ) {
		$this->id = $id;
		return $this;
	}
	/**
	 * Hent Id
	 *
	 * @return int $id
	**/
	public function getId() {
		return $this->id;
	}
	
	/**
	 * Sett fornavn
	 *
	 * @param string $fornavn
	 * @return $this
	**/
	public function setFornavn( $fornavn ) {
		$this->fornavn = stripslashes( mb_convert_case($fornavn, MB_CASE_TITLE, "UTF-8" ) );
		return $this;
	}
	/**
	 * Hent Fornavn
	 *
	 * @return string $fornavn
	**/
	public function getFornavn() {
		return $this->fornavn;
	}
	
	/**
	 * Sett etternavn
	 *
	 * @param string $etternavn
	 * @return $this
	**/
	public function setEtternavn( $etternavn ) {
		$this->etternavn = stripslashes( mb_convert_case($etternavn, MB_CASE_TITLE, "UTF-8") );
		return $this;
	}
	/**
	 * Hent Fornavn
	 *
	 * @return string $fornavn
	**/
	public function getEtternavn() {
		return $this->etternavn;
	}
	
	/**
	 * Hent fullt navn
	 *
	 * @return string CONCAT(getFornavn() + ' ' + getEtternavn())
	**/
	public function getNavn() {
		return $this->getFornavn() .' '. $this->getEtternavn();
	}
	
	/**
	 * Sett mobil
	 *
	 * @param string $mobil
	 * @return $this
	**/
	public function setMobil( $mobil ) {
		$this->mobil = preg_replace("/[^0-9]/","", $mobil);
		return $this;
	}
	/**
	 * Hent mobil
	 *
	 * @return string $mobil
	**/
	public function getMobil() {
		return $this->mobil;
	}
	
	/**
	 * Sett e-post
	 *
	 * @param string $epost
	 * @return $this
	**/
	public function setEpost( $epost ) {
		$this->epost = $epost;
		return $this;
	}
	/**
	 * Hent e-post
	 *
	 * @return string $epost
	**/
	public function getEpost() {
		return $this->epost;
	}

	/**
	 * Sett rolle (i.e. instrument for scene, film/flerkamera/tekst/foto for UKM Media osv)
	 *
	 * @param string|array $rolle
	 * @return $this
	 */
	public function setRolle( $rolle ) {
		if( is_array( $rolle ) ) {
			$rolle_object = array();
			$rolle_nicename = '';

			foreach ($rolle as $key => $r) {
				$rolle_object[] = $key;
				$rolle_nicename = $rolle_nicename . $r . ', ';
			}
			
			$this->setRolleObject($rolle_object);
			$rolle = rtrim($rolle_nicename, ', ');
		}

		$this->rolle = stripslashes( $rolle );
		return $this;
	}

	/**
	 * Hent rolle (i.e. instrument for scene, film/flerkamera/tekst/foto for UKM Media osv)
	 *
	 * @return string $rolle
	 */
	public function getRolle() {
		return $this->rolle;
	}

	/**
	 * JSON-encodes på vei inn i databasen, vanlig array ellers.
	 * @param array
	 * @return $this
	 */
	public function setRolleObject( $rolleArray ) {
		$this->rolleObject = $rolleArray;
		return $this;
	}

	public function getRolleObject() {
		return $this->rolleObject;
	}
	
	/**
	 * Sett instrument. Alias of setRolle().
	 *
	 * @param string $instrument
	 * @return $this
	**/
	public function setInstrument( $instrument ) {
		$this->setRolle($instrument);
		return $this;

		/*$this->instrument = $instrument;
		return $this;*/
	}
	/**
	 * Hent instrument. Alias of getRolle().
	 *
	 * @return string $instrument
	**/
	public function getInstrument() {
		return $this->getRolle();
		#return $this->instrument;
	}

	/**
	 * Sett instrumentObject
	 * Brukes av nettredaksjon + arrangør for å holde styr på undergrupper
	 *
	 * @param array $instrumentObject
	 * @return $this
	**/
	public function setInstrumentObject( $instrumentArray ) {
		$this->setRolleObject($instrumentArray);
		#$this->instrumentObject = $instrumentArray;
		return $this;
	}
	/**
	 * Hent instrumentObject
	 * Brukes av nettredaksjon + arrangør for å holde styr på undergrupper
	 *
	 * @return array $instrumentObject
	**/
	public function getInstrumentObject() {
		return $this->getRolleObject();
		#return $this->instrumentObject;
	}

	/**
	 * Sett fødselsdato
	 *
	 * @param integer unixtime $fodselsdato
	 * @return $this
	**/
	public function setFodselsdato( $fodselsdato ) {
		$this->fodselsdato = $fodselsdato;
		return $this;
	}
	/**
	 * Hent fødselsdato
	 *
	 * @return integer unixtime $fodselsdato
	**/
	public function getFodselsdato() {
		return $this->fodselsdato;
	}
	
	/**
	 * Hent alder
	 *
	 * @param $suffix=' år'
	 *
	 * @return string alder
	**/
	public function getAlder( $suffix=' år' ) {
		if( 0 == $this->getFodselsdato() ) {
			return '25+'. $suffix;
		}
		$birthdate = new DateTime();
		$birthdate->setTimestamp( $this->getFodselsdato() );
        $now = new DateTime('now');

		return $birthdate->diff($now)->y . $suffix;
	}
	
	public function getAlderTall() {
		return $this->getAlder(null);
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
		throw new Exception(
			'PERSON V2: setFylke() er ikke mulig. Bruk setKommune( $kommune_id )',
			109003
		);
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
	 * hent kjønn
	 * Henter kjønn fra navnetabellen
	 *
	 * OBS: krever databasespørring!
	 * TODO: write_person bør lagre dette på personobjektet
	 *
	 * @return string kjønn
	**/
	public function getKjonn() {
		$first_name = explode(" ", str_replace("-", " ", $this->getFornavn()) );
		$first_name = $first_name[0];
		
		$qry = "SELECT `kjonn`
				FROM `ukm_navn`
				WHERE `navn` = '" . $first_name ."' ";
		
		$qry = new SQL($qry);
		$res = $qry->run('field','kjonn');
		
		return ($res == null) ? 'unknown' : $res;
	}
	
	public function getKjonnspronomen() {
		#echo $this->getNavn() .': '. $this->getKjonn();
		switch( $this->getKjonn() ) {
			case 'male':
				return 'han';
			case 'female':
				return 'hun';
			default: 
				return 'han/hun';
		}
    }

    public function getSensitivt() {
        if( null == $this->sensitivt ) {
            require_once('UKM/Sensitivt/Person.php');
            $this->sensitivt = new UKMNorge\Sensitivt\Person( $this->getId() );
        }
        return $this->sensitivt;
    }


	/**
	 * Set BT_ID (innslagstype)
	 * Brukes for å definere om man er videresendt (BTID==1 gir videresendt uavhengig av database-relasjoner)
	 *
	 * @param int $bt_id
	 * @return $this
	**/
	private function _setBTID( $bt_id ) {
		$this->bt_id = $bt_id;
		return $this;
	}
	/**
	 * Hent BT_ID (innslagstype)
	 * Brukes for å definere om man er videresendt (BTID==1 gir videresendt uavhengig av database-relasjoner)
	 *
	 * @return int $bt_id
	**/
	private function _getBTID() {
		return $this->bt_id;
	}
}
?>
