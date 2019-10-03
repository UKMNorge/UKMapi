<?php

namespace UKMNorge\Innslag\Personer;

use Exception;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Database\SQL\Delete;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Geografi\Kommune;
use UKMNorge\Logger\Logger;

use UKMNorge\Samtykke\Person as PersonSamtykke;

class Write {
    	/**
	 * Hent ut fødselsdato som unixtimestamp fra int alder 
	 *
	 * @param integer $alder
	 * @return integer unix timestamp.
	 */
	public static function fodselsdatoFraAlder($alder) {
		if( $alder == 0 ) {
			return 0;
		}
		return mktime(0,0,0,1,1, (Int)date("Y") - (Int)$alder);
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
							  'phone'=>(Int)$phone));
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
		if( !Logger::ready() ) {
			throw new Exception(
				'Logger is missing or incorrect set up.',
				50701
			);
		}
		// Valider input-data
		try {
			Write::_validerCreate( $fornavn, $etternavn, $mobil, $fodselsdato, $kommune_id );
		} catch( Exception $e ) {
			throw new Exception(
				'Kunne ikke opprette person. '. $e->getMessage(),
				$e->getCode()
			);
		}

		// Opprett kommune-objekt
		$kommune = new Kommune($kommune_id);

		// Har vi denne personen?
		$p_id = self::finnEksisterendePerson($fornavn, $etternavn, $mobil);
		// Personen finnes ikke
		if(false == $p_id) {
			$sql = new Insert("smartukm_participant");
			$sql->add('p_firstname', $fornavn);
			$sql->add('p_lastname', $etternavn);
			$sql->add('p_phone', $mobil);
			$sql->add('p_kommune', $kommune->getId());
			$sql->add('p_dob', $fodselsdato);
			$insert_id = $sql->run(); 
			
			// Database-oppdatering feilet
			if( !$insert_id ) {
				throw new Exception(
					"Klarte ikke å opprette et personobjekt for ".$fornavn." ". $etternavn.".",
					50706
				);
			}
			$p_id = $insert_id;
		}
		// Personen finnes i databasen, oppdater kommune og fødselsdato
		else {
			$sql = new Insert("smartukm_participant", array('p_id'=>$p_id));
			$sql->add('p_kommune', $kommune->getId());
			$sql->add('p_dob', $fodselsdato);
			$res = $sql->run(); 
		}
		
		return new Person( (Int) $p_id );
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
		if( !Logger::ready() ) {
			throw new Exception(
				'Logger is missing or incorrect set up.',
				50701
			);
		}
		// Valider inputdata
		try {
			Write::validerPerson( $person_save );
		} catch( Exception $e ) {
			throw new Exception(
				'Kan ikke lagre person. '. $e->getMessage(),
				$e->getCode()
			);
		}
		
		// Hent sammenligningsgrunnlag
		$person_db = new Person( $person_save->getId() );

		// TABELLER SOM KAN OPPDATERES
		$smartukm_participant = new Update(
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
				Logger::log( $action, $person_save->getId(), $value );
			}
		}

		// SETT KOMMUNE-DATA
		if( $person_db->getKommune()->getId() != $person_save->getKommune()->getId() ) {
			$smartukm_participant->add('p_kommune', $person_save->getKommune()->getId() );
			Logger::log( 406, $person_save->getId(), $person_save->getKommune()->getId() );
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
	 * Kan ikke alltid kjøres som en del av savePerson da personer
	 * kan redigeres uten å ha en rolle i et innslag (og da mangle relasjonen)
	 *
	 * @param Write $person_save
	 * @param rolle string
	 *
	 * @return this
	 */
	public function saveRolle( $person_save ) {
		// Valider input-data
		try {
			Write::validerPerson( $person_save );
		} catch( Exception $e ) {
			throw new Exception(
				'Kan ikke oppdatere personens rolle. '. $e->getMessage(),
				$e->getCode()
			);
		}
		
		// Valider kontekst (tilknytning til mønstring)
		if( $person_save->getContext() == null || $person_save->getContext()->getMonstring() == null ) {
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
		$monstring = new Arrangement( $person_save->getContext()->getMonstring()->getId() );
		// Hent innslaget fra gitt mønstring
		$innslag_db = $monstring->getInnslag()->get( $person_save->getContext()->getInnslag()->getId(), true );
		// Hent personen fra gitt innslag
		$person_db = $innslag_db->getPersoner()->get( $person_save->getId() );
		
		// Sammenlign de to person-objektene for å se om lagring er nødvendig
		if( $person_db->getRolle() != $person_save->getRolle() ) {
			
			$sql = new Update(
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
			Logger::log( 411, $person_save->getId(), $person_save->getRolle() );

			return $sql->run();
		}
	}



	/********************************************************************************
	 *
	 *
	 * LEGG TIL OG FJERN PERSON FRA COLLECTION
	 *
	 *
	 ********************************************************************************/

	/**
	 * Legg til person i innslaget
	 * Videresender automatisk til context-mønstring
	 * 
	 * @param person_v2 $person_save
	**/
	public static function leggTil( $person_save ) {
		// Valider inputs
		Person::_validerLeggtil( $person_save );

		// Opprett mønstringen personen kommer fra
		$monstring = new Arrangement( $person_save->getContext()->getMonstring()->getId() );
		// Hent innslaget fra gitt mønstring
		$innslag_db = $monstring->getInnslag()->get( $person_save->getContext()->getInnslag()->getId(), true );
		
		// Alltid legg til personen lokalt
		$res = Write::_leggTilLokalt( $person_save );

		// Sett rolle på personen. 
		// Worst case: vi setter blank rolle hvis dette ikke er satt på objektet fra før
		if( $res ) {
			Write::saveRolle( $person_save );
		}


        /**
         * SAMTYKKE:
         * Legger til samtykke-forespørsel for denne personen.
         * Hvis personen allerede har godkjent gjøres ingenting.
         * Hvis personen deltar i flere innslag legges innslaget til i listen over
         * relevante innslag, slik at hvis personen blir fjernet fra alle innslag, 
         * blir også forespørselen satt på vent
         */
        // Hvis innslaget er påmeldt ønsker vi å innhente samtykke for denne personen
        if( $innslag_db->getStatus() == 8 ) {
            $samtykke = new PersonSamtykke( $person_save, $innslag_db );
            $samtykke->leggTilInnslag( $innslag_db->getId() );
        }


		// Videresend personen hvis ikke lokalmønstring
		if( $res && $monstring->getType() != 'kommune' ) {
			$res = Write::_leggTilVideresend( $person_save );
		}
		
		if( $res ) {
			return $person_save;
		}
		
		throw new Exception(
			'Kunne ikke legge til '. $person_save->getNavn() .' i innslaget. ',
			50513
		);
	}

	/**
	 * Fjern en videresendt person, og avmelder hvis gitt lokalmønstring
	 *
	 * @param person_v2 $person_save
	 *
	 * @return (bool true|throw exception)
	 */
	public function fjern( $person_save ) {
		// Valider inputs
		Write::_validerLeggtil( $person_save );

		// Opprett mønstringen personen kommer fra
		$monstring = new Arrangement( $person_save->getContext()->getMonstring()->getId() );
		// Hent innslaget fra gitt mønstring
		$innslag_db = $monstring->getInnslag()->get( $person_save->getContext()->getInnslag()->getId(), true );

		if( $monstring->getType() == 'kommune' || $person_save->getContext()->getInnslag()->getType()->getId() == 1 ) {
			$res = Write::_fjernLokalt( $person_save );
		} else {
			$res = Write::_fjernVideresend( $person_save );
		}
        
        /**
         * SAMTYKKE:
         * Fjerner samtykke-forespørsel for dette innslaget.
         * Hvis personen allerede har godkjent gjøres ingenting.
         * Hvis personen deltar i flere innslag fjernes kun forespørsel for dette
         * innslaget, mens for andre innslag vil den fortsatt stå.
         */
        $samtykke = new PersonSamtykke( $person_save, $innslag_db );
        $samtykke->fjernInnslag( $innslag_db->getId() );

		if( $res ) {
			return true;
		}
		
		throw new Exception(
			'Kunne ikke fjerne '. $person_save->getNavn() .' fra innslaget. ',
			50514
		);
	}




	/********************************************************************************
	 *
	 *
	 * LEGG TIL-HJELPERE
	 *
	 *
	 ********************************************************************************/

	/**
	 * Legg til en person på lokalnivå (ikke videresend)
	 *
	 * @param person_v2 $person
	 * @return bool $success
	**/
	private static function _leggTilLokalt( $person_save ) {
		// Er personen allerede lagt til i innslaget?
		$sql = new SQL("SELECT COUNT(*) 
						FROM smartukm_rel_b_p 
						WHERE 'b_id' = '#b_id' 
							AND 'p_id' = '#p_id'",
						array(	'b_id' => $person_save->getContext()->getInnslag()->getId(), 
								'p_id' => $person_save->getId()) 
						);
		$exists = $sql->run('field', 'COUNT(*)');
		if($exists) {
			return true;
		}

		// Legg til i innslaget
		$sql = new Insert("smartukm_rel_b_p");
		$sql->add('b_id', $person_save->getContext()->getInnslag()->getId() );
		$sql->add('p_id', $person_save->getId());

		Logger::log( 324, $person_save->getContext()->getInnslag()->getId(), $person_save->getId().': '. $person_save->getNavn() );
		$res = $sql->run();
		
		if( !$res ) {
			return false;
		}
		return true;
	}
	
	/**
	 * Legg til en person på videresendt nivå
	 *
	 * @param person_v2 $person_save
	**/
	private function _leggTilVideresend( $person_save ) {
		// FOR INNSLAG I KATEGORI 1 (SCENE) FØLGER ALLE DELTAKERE ALLTID INNSLAGET VIDERE
		if( $person_save->getContext()->getInnslag()->getType()->getId() == 1 ) {
			return true;
		}
		
		$test_relasjon = new Query(
			"SELECT * FROM `smartukm_fylkestep_p`
				WHERE `pl_id` = '#pl_id'
				AND `b_id` = '#b_id'
				AND `p_id` = '#p_id'",
			[
				'pl_id'		=> $person_save->getContext()->getMonstring()->getId(), 
		  		'b_id'		=> $person_save->getContext()->getInnslag()->getId(), 
				'p_id'		=> $person_save->getId(),
			]
		);
		$test_relasjon = $test_relasjon->run();
		
		// Hvis allerede videresendt, alt ok
		if( Query::numRows($test_relasjon) > 0 ) {
			return true;
		}
		// Videresend personen
		else {
			$videresend_person = new Insert('smartukm_fylkestep_p');
			$videresend_person->add('pl_id', $person_save->getContext()->getMonstring()->getId() );
			$videresend_person->add('b_id', $person_save->getContext()->getInnslag()->getId() );
			$videresend_person->add('p_id', $person_save->getId() );

			$log_msg = $person_save->getId().': '. $person_save->getNavn() .' => PL: '. $person_save->getContext()->getMonstring()->getId();
			Logger::log( 320, $person_save->getContext()->getInnslag()->getId(), $log_msg );
			$res = $videresend_person->run();
		
			if( $res ) {
				return true;
			}
		}

		throw new Exception(
			'Kunne ikke videresende '. $person_save->getNavn() .'.',
			50516
		);
	}


	
	/********************************************************************************
	 *
	 *
	 * FJERN-HJELPERE
	 *
	 *
	 ********************************************************************************/

	/**
	 * Fjerner en person helt fra innslaget (avmelding lokalnivå)
	 *
	 * @param person_v2 $person
	 *
	 * @return (bool true|throw exception)
	 */	 
	private function _fjernLokalt( $person_save ) {
		$sql = new Delete("smartukm_rel_b_p", 
			array( 	'b_id' => $person_save->getContext()->getInnslag()->getId(),
					'p_id' => $person_save->getId(),
					));
		Logger::log( 325, $person_save->getContext()->getInnslag()->getId(), $person_save->getId().': '. $person_save->getNavn() );
		$res = $sql->run();
		if( $res ) {
			return true;
		}
		
		throw new Exception(
			'Kunne ikke fjerne '. $person_save->getNavn() .' fra innslaget. ',
			50515
		);
	}
	
	/**
	 * 
	 * Avrelaterer en person til dette innslaget.
	 *
	 * @param write_person $person
	 * @param write_monstring $monstring
	 *
	 * @return (bool true|throw exception)
	 */
	public function _fjernVideresend( $person_save ) {
		// FOR INNSLAG I KATEGORI 1 (SCENE) FØLGER ALLE DELTAKERE ALLTID INNSLAGET VIDERE
		if( $person_save->getContext()->getInnslag()->getType()->getId() == 1 ) {
			return false;
		}

		$videresend_person = new Delete(
			'smartukm_fylkestep_p', 
			[
				'pl_id' 	=> $person_save->getContext()->getMonstring()->getId(),
				'b_id' 		=> $person_save->getContext()->getInnslag()->getId(),
				'p_id' 		=> $person_save->getId()
			]
		);
		
		$log_msg = $person_save->getId() .': '. $person_save->getNavn() .' => PL: '. $person_save->getContext()->getMonstring()->getId();
		Logger::log( 321, $person_save->getContext()->getInnslag()->getId(), $log_msg );

		$res = $videresend_person->run();

		if( $res ) {
			return true;
		}
		
		// Sjekk om det finnes en rad
		$db_test = new Query("
			SELECT `b_id`
			FROM `smartukm_fylkestep_p`
			WHERE `pl_id` = '#pl_id'
			AND `b_id` = '#b_id' 
			AND `p_id` = '#p_id'",
			[
				'pl_id' 	=> $person_save->getContext()->getMonstring()->getId(),
				'b_id' 		=> $person_save->getContext()->getInnslag()->getId(),
				'p_id' 		=> $person_save->getId()
			]
		);
		$test_res = $db_test->run('field','b_id');
		if( null == $test_res ) {
			return true;
		}
		
		
		throw new Exception(
			'Kunne ikke avmelde '. $person_save->getNavn() .'.',
			50717
		);
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
		if( !is_object( $person ) || !in_array(get_class( $person ), ['UKMNorge\Innslag\Personer\Person', 'person_v2']) ) {
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
	
	/**
	 * Valider alle input-parametre for å legge til ny person
	 *
	 * @see leggTil
	**/
	private static function _validerLeggtil( $person_save ) {
		// Valider input-data
		try {
			Write::validerPerson( $person_save );
		} catch( Exception $e ) {
			throw new Exception(
				'Kan ikke legge til/fjerne person. '. $e->getMessage(),
				$e->getCode()
			);
		}
		
		// Valider kontekst (tilknytning til mønstring)
		if( $person_save->getContext()->getMonstring() == null ) {
			throw new Exception(
				'Kan ikke legge til/fjerne person. '.
				'Person-objektet er ikke opprettet i riktig kontekst',
				50511
			);
		}
		// Valider kontekst (tilknytning til innslag)
		if( $person_save->getContext()->getInnslag() == null ) {
			throw new Exception(
				'Kan ikke legge til/fjerne person. '.
				'Person-objektet er ikke opprettet i riktig kontekst',
				50512
			);
		}
	}
}