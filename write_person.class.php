<?php
require_once('UKM/monstring.class.php');
require_once('UKM/innslag.class.php');
require_once('UKM/person.class.php');
require_once('UKM/kommune.class.php');

class write_person {
	/**
	 * Hent ut fødselsdato som unixtimestamp fra int alder 
	 *
	 * @param integer $alder
	 * @return integer unix timestamp.
	 */
	public static function fodselsdatoFraAlder($alder) {
		return mktime(0,0,0,1,1, (int)date("Y") - (int)$alder);
	}

	/**
	 * Sjekk om personen eksisterer i databasen
	 * 
	 * Henter P_ID hvis kombinasjonen fornavn, etternavn, mobil finnes i databasen
	 *
	 * @param string $firstname
	 * @param string $lastname
	 * @param string $phone
	 * @return int $person_id
	**/
	public static function finnEksisterendePerson($firstname, $lastname, $phone) {
		$qry = new SQL("SELECT `p_id` FROM `smartukm_participant` 
						WHERE `p_firstname`='#firstname' 
						AND `p_lastname`='#lastname' 
						AND `p_phone`='#phone'", 
						array('firstname'=>$firstname, 
							  'lastname'=>$lastname, 
							  'phone'=>(int)$phone));
		$p_id = $qry->run('field', 'p_id');

		return $p_id;
	}


	/**
	 * create()
	 *
	 * Oppretter et nytt personobjekt og lagrer i databasen.
	 * Finnes kombinasjonen av fornavn, etternavn og mobil fra før,
	 * slås personene sammen
	 *
	 * @param string $fornavn
	 * @param string $etternavn
	 * @param string $mobil
	 * @param unixtime $fodselsdato
	 * @param int $kommune_id
	 * @return write_person
	 */
	public static function create($fornavn, $etternavn, $mobil, $fodselsdato, $kommune_id) {
		// Valider logger
		if( !UKMlogger::ready() ) {
			throw new Exception(
				'Logger is missing or incorrect set up.',
				50701
			);
		}
		// Valider input-data
		try {
			write_person::_validateCreate( $fornavn, $etternavn, $mobil, $fodselsdato, $kommune_id );
		} catch( Exception $e ) {
			throw new Exception(
				'Kunne ikke opprette person'. $e->getMessage(),
				$e->getCode()
			);
		}

		// Opprett kommune-objekt
		$kommune = new kommune($kommune_id);

		// Har vi denne personen?
		$p_id = self::finnEksisterendePerson($fornavn, $etternavn, $mobil);
		// Personen finnes ikke
		if(false == $p_id) {
			$sql = new SQLins("smartukm_participant");
			$sql->add('p_firstname', $fornavn);
			$sql->add('p_lastname', $etternavn);
			$sql->add('p_phone', $mobil);
			$sql->add('p_kommune', $kommune->getId());
			$sql->add('p_dob', $fodselsdato);
			$res = $sql->run(); 
			
			// Database-oppdatering feilet
			if(false == $res) {
				throw new Exception(
					"Klarte ikke å opprette et personobjekt for ".$fornavn." ". $etternavn.".",
					50706
				);
			}
			$p_id = $sql->insid();
		}
		// Personen finnes i databasen, oppdater kommune og fødselsdato
		else {
			$sql = new SQLins("smartukm_participant", array('p_id'=>$p_id));
			$sql->add('p_kommune', $kommune->getId());
			$sql->add('p_dob', $fodselsdato);
			$res = $sql->run(); 
		}
		
		return new person_v2( (int) $p_id );
	}

	/**
	 * Lagre et person-objekt
	 *
	 * Lagring av rolle skjer via write_innslag::setRolle( $innslag, $person );
	 *
	 * @param person_v2 $person_save
	 * @return bool true
	**/
	public static function save( $person_save ) {
		// Valider logger
		if( !UKMlogger::ready() ) {
			throw new Exception(
				'Logger is missing or incorrect set up.',
				50701
			);
		}
		// Valider inputdata
		try {
			write_person::validerPerson( $person_save );
		} catch( Exception $e ) {
			throw new Exception(
				'Kan ikke lagre person. '. $e->getMessage(),
				$e->getCode()
			);
		}
		
		// Hent sammenligningsgrunnlag
		$person_db = new person_v2( $person_save->getId() );

		// TABELLER SOM KAN OPPDATERES
		$smartukm_participant = new SQLins(
			'smartukm_participant', 
			[
				'p_id' => $person_save->getId(),
			]
		);
		// VERDIER SOM KAN OPPDATERES
		$properties = [
			'Fornavn' 			=> ['smartukm_participant', 'p_firstname', 401],
			'Etternavn' 		=> ['smartukm_participant', 'p_lastname', 402],
			'Mobil'				=> ['smartukm_participant', 'p_phone', 405],
			'Epost'				=> ['smartukm_participant', 'p_email', 404],
			'Fodselsdato'		=> ['smartukm_participant', 'p_dob', 403],
			'Epost'				=> ['smartukm_participant', 'p_email', 404],
		];
		
		// LOOP ALLE VERDIER, OG EVT LEGG TIL I SQL
		foreach( $properties as $functionName => $logValues ) {
			$function = 'get'.$functionName;
			$table = $logValues[0];
			$field = $logValues[1];
			$action = $logValues[2];
			$sql = $$table;
			
			if( $person_db->$function() != $person_save->$function() ) {
				# Mellomlagre verdi som skal settes
				$value = $person_save->$function();
				# Legg til i SQL
				$sql->add( $field, $value ); 	// SQL satt dynamisk i foreach til $$table
				# Logg (eller dø) før vi kjører run
				UKMlogger::log( $action, $person_save->getId(), $value );
			}
		}

		// SETT KOMMUNE-DATA
		if( $person_db->getKommune()->getId() != $person_save->getKommune()->getId() ) {
			$smartukm_participant->add('p_kommune', $person_save->getKommune()->getId() );
			UKMlogger::log( 406, $person_save->getId(), $person_save->getKommune()->getId() );
		}

		// KJØR SPØRRING HVIS ENDRINGER FINNES
		if( $smartukm_participant->hasChanges() ) {
			#echo $smartukm_participant->debug();
			$smartukm_participant->run();
		}
		return true;
	}
	
	/**
	 * setRolle på person.
	 *
	 * @param write_person
	 * @param rolle string
	 *
	 * @return this
	 */
	public function saveRolle( $person_save ) {
		// Valider input-data
		try {
			write_person::validerPerson( $person_save );
		} catch( Exception $e ) {
			throw new Exception(
				'Kan ikke oppdatere personens rolle. '. $e->getMessage(),
				$e->getCode()
			);
		}
		
		// Valider kontekst (tilknytning til mønstring)
		if( $person_save->getContext()->getMonstring() == null ) {
			throw new Exception(
				'Kan ikke oppdatere personens rolle. '.
				'Person-objektet er ikke opprettet i riktig kontekst',
				50511
			);
		}
		// Valider kontekst (tilknytning til innslag)
		if( $person_save->getContext()->getInnslag() == null ) {
			throw new Exception(
				'Kan ikke oppdatere personens rolle. '.
				'Person-objektet er ikke opprettet i riktig kontekst',
				50512
			);
		}
		
		// Opprett mønstringen personen kommer fra
		$monstring = new monstring_v2( $person_save->getContext()->getMonstring()->getId() );
		// Hent innslaget fra gitt mønstring
		$innslag_db = $monstring->getInnslag()->get( $person_save->getContext()->getInnslag()->getId() );
		// Hent personen fra gitt innslag
		$person_db = $innslag_db->getPersoner()->get( $person_save->getId() );
		
		// Sammenlign de to person-objektene for å se om lagring er nødvendig
		if( $person_db->getRolle() != $person_save->getRolle() ) {
			
			$sql = new SQLins(
				'smartukm_rel_b_p',
				[
					'b_id' => $innslag_db->getId(),
					'p_id' => $person_save->getId(),
				]
			);

			// Hvis innslaget ikke har titler, har det et array med
			// funksjoner som også skal lagres
			if( !$innslag_db->getType()->harTitler() ) {
				$sql->add('instrument_object', json_encode( $person_save->getRolleObject() ) );
			}

			$sql->add('instrument', $person_save->getRolle() );
			UKMlogger::log( 411, $person_save->getId(), $person_save->getRolle() );

			return $sql->run();
		}
	}





	/********************************************************************************
	 *
	 *
	 * VALIDER INPUT-PARAMETRE
	 *
	 *
	 ********************************************************************************/
	
	/**
	 * Valider at gitt person-objekt er av riktig type
	 * og har en numerisk Id som kan brukes til database-modifisering
	 *
	 * @param anything $person
	 * @return void
	**/
	public static function validerPerson( $person ) {
		if( !is_object( $person ) || get_class( $person ) != 'person_v2' ) {
			throw new Exception(
				'Person må være objekt av klassen person_v2',
				50707
			);
		}
		if( !is_numeric( $person->getId() ) || $person->getId() <= 0 ) {
			throw new Exception(
				'Person-objektet må ha en numerisk ID større enn null',
				50708
			);
		}
	}
	
	/**
	 * Valider alle input-parametre for opprettelse av ny person
	 *
	 * @see create()
	**/
	private static function _validerCreate( $fornavn, $etternavn, $mobil, $fodselsdato, $kommune_id ) {
		if(!is_string($fornavn) || empty($fornavn) || !is_string($etternavn) || empty($etternavn) ) {
			throw new Exception(
				"Fornavn og etternavn må være en streng.",
				50702
			);
		}
		if( !is_numeric($mobil) || 8 != strlen($mobil) ) {
			throw new Exception(
				"Mobilnummeret må bestå kun av tall og være 8 siffer langt!",
				50703
			);
		}
		if( !is_numeric($fodselsdato) ) {
			throw new Exception(
				"Fødselsdatoen må være et Unix Timestamp.",
				50704
			);
		}
		if( !is_numeric($kommune_id) ) {
			throw new Exception(
				"Kommune-ID må være et tall.",
				50705
			);
		}
	}
}