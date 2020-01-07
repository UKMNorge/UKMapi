<?php

namespace UKMNorge\Innslag\Personer;

use Exception;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Database\SQL\Delete;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Geografi\Kommune;
use UKMNorge\Log\Logger;

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
	 * @param String $firstname
	 * @param String $lastname
	 * @param  $phone
	 * @return Int $person_id
	**/
	public static function finnEksisterendePerson( String $firstname, String $lastname, $phone) {
		$qry = new Query("SELECT `p_id` FROM `smartukm_participant` 
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
	 * @param String $fornavn
	 * @param String $etternavn
	 * @param Int $mobil
	 * @param Kommune $kommune
	 * @return Person
	 */
    public static function create( String $fornavn, String $etternavn, Int $mobil, Kommune $kommune) {
        static::do_create($fornavn, $etternavn, $mobil, $kommune);
    }

    public static function create_fylke( String $fornavn, String $etternavn, Int $mobil, Fylke $fylke ) {
        static::do_create($fornavn, $etternavn, $mobil, null, $fylke);
    }
    
    private static function do_create( String $fornavn, String $etternavn, Int $mobil, Kommune $kommune = null, Fylke $fylke = null) {
		// Valider logger
		if( !Logger::ready() ) {
			throw new Exception(
				'Logger is missing or incorrect set up.',
				506018
			);
		}
		// Valider input-data
		try {
			Write::_validerCreate( $fornavn, $etternavn, $mobil );
		} catch( Exception $e ) {
			throw new Exception(
				'Kunne ikke opprette person. '. $e->getMessage(),
				$e->getCode()
			);
        }
        
        // Sjekk at enten kommune eller fylke er definert
        if( !Kommune::validateClass($kommune) && !Fylke::validateClass($fylke) ) {
            throw new Exception(
				"Kan ikke opprette påmelding uten enten kommune eller fylke",
				506005
			);
        }

		// Har vi denne personen?
		$p_id = self::finnEksisterendePerson($fornavn, $etternavn, $mobil);
        
        // Personen finnes ikke
		if(false == $p_id) {
			$sql = new Insert("smartukm_participant");
			$sql->add('p_firstname', $fornavn);
			$sql->add('p_lastname', $etternavn);
            $sql->add('p_phone', $mobil);
            if(NULL != $kommune) {
                $sql->add('p_kommune', $kommune->getId());    
            } else {
                $sql->add('p_kommune', 0);
            }
			$insert_id = $sql->run(); 
			
			// Database-oppdatering feilet
			if( !$insert_id ) {
				throw new Exception(
					"Klarte ikke å opprette et personobjekt for ".$fornavn." ". $etternavn.".",
					506017
				);
			}
			$p_id = $insert_id;
		}
		// Personen finnes i databasen og vi har kommune, oppdater kommune
		elseif ( NULL != $kommune ) {
			$sql = new Insert(
                "smartukm_participant",
                ['p_id' => $p_id]
            );
			$sql->add('p_kommune', $kommune->getId());
			$res = $sql->run(); 
		}
		
		return new Person( (Int) $p_id );
	}

	/**
	 * Lagre et person-objekt
	 *
	 * Lagring av rolle skjer via write_innslag::setRolle( $innslag, $person );
	 *
	 * @param Person $person_save
	 * @return bool true
	**/
	public static function save( Person $person_save ) {
		// Valider logger
		if( !Logger::ready() ) {
			throw new Exception(
				'Logger is missing or incorrect set up.',
				506016
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
            'Adresse'           => ['smartukm_participant', 'p_adress', 412],
            'Postnummer'        => ['smartukm_participant', 'p_postnumber', 407],
            'Poststed'          => ['smartukm_participant', 'p_postplace', 408],
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
        
        // Hvis vi har endret mobiltelefon, oppdater den også i Samtykke-raden.
        if( $person_db->getMobil() != $person_save->getMobil() ) {
            $samtykke_endring = new Update(
                'samtykke_deltaker', 
                [
                    'p_id' => $person_save->getId(),
                ]
            );
            $samtykke_endring->add('mobil', $person_save->getMobil());
            $samtykke_endring->run();
        }
    
		return true;
	}
	
	/**
	 * setRolle på person.
	 *
	 * Kan ikke alltid kjøres som en del av savePerson da personer
	 * kan redigeres uten å ha en rolle i et innslag (og da mangle relasjonen)
	 *
	 * @param Person $person_save
	 *
	 * @return this
	 */
	public static function saveRolle( Person $person_save ) {
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
				506001
			);
		}
		// Valider kontekst (tilknytning til innslag)
		if( $person_save->getContext()->getInnslag() == null ) {
			throw new Exception(
				'Kan ikke oppdatere personens rolle. '.
				'Person-objektet er ikke opprettet i riktig kontekst',
				506002
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
	 * Legg til person i innslaget (og arrangementet)
     * 
	 * Videresender automatisk til context-mønstring
	 * 
	 * @param Person $person_save
	**/
	public static function leggTil( Person $person_save ) {
		// Valider inputs
		static::_validerLeggtil( $person_save );

		// Hent mønstringen personen kommer fra
		$monstring = new Arrangement( $person_save->getContext()->getMonstring()->getId() );
		// Hent innslaget fra gitt mønstring (for riktig context)
		$innslag_db = $monstring->getInnslag()->get( $person_save->getContext()->getInnslag()->getId(), true );
		
		// Alltid legg til personen lokalt
		$res = Write::_leggTilInnslaget( $person_save );

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
        if( $innslag_db->erPameldt() ) {
            $samtykke = new PersonSamtykke( $person_save, $innslag_db );
            $samtykke->leggTilInnslag( $innslag_db->getId() );
        }

		// Legg til relasjon til arrangement / gammel videresend
		if( $res ) {
			$res = Write::_leggTilArrangement( $person_save );
		}
		
		if( $res ) {
			return $person_save;
		}
		
		throw new Exception(
			'Kunne ikke legge til '. $person_save->getNavn() .' i innslaget. ',
			506015
		);
	}

	/**
	 * Fjern/meld av person
     * 
     * Finner selv ut om personen er videresendt til flere arrangementer, 
     * og fjerner / melder av ut fra dette
	 *
	 * @param Person $person_save
	 * @return Bool true
     * @throws Exception hvis feilet
	 */
	public static function fjern( Person $person_save ) {
		// Valider inputs
		static::_validerLeggtil( $person_save );

		// Opprett mønstringen personen kommer fra
		$monstring = new Arrangement( $person_save->getContext()->getMonstring()->getId() );
		// Hent innslaget fra gitt mønstring
		$innslag_db = $monstring->getInnslag()->get( $person_save->getContext()->getInnslag()->getId(), true );

        // Pre-2020-relasjon
        if( $innslag_db->getSesong() < 2020 ) {
            if( $monstring->getType() == 'kommune' || $person_save->getContext()->getInnslag()->getType()->getId() == 1 ) {
                $res = Write::_slett( $person_save );
            } else {
                $res = Write::_fjernArrangement( $person_save );
            }
        }
        // 2020-relasjon
        else {
            $antall_relasjoner = new Query("SELECT COUNT(`id`)
                FROM `ukm_rel_arrangement_person`
                WHERE `innslag_id` = '#innslag'
                AND `person_id` = '#person'",
                [
                    'innslag' => $person_save->getContext()->getInnslag()->getId(),
                    'person' => $person_save->getId()
                ]
            );
            $antall_relasjoner = (int) $antall_relasjoner->getField();

            if( $antall_relasjoner == 1 ) {
                $res = Write::_slett( $person_save );
            } else {
                $res = Write::_fjernArrangement( $person_save );
            }
        }
		
		if( $res ) {
			return true;
		}
		
		throw new Exception(
			'Kunne ikke fjerne '. $person_save->getNavn() .' fra innslaget. ',
			506014
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
	 * Legg personen til innslaget
	 *
	 * @param Person $person
	 * @return Bool $success
	**/
	private static function _leggTilInnslaget( Person $person_save ) {
		// Er personen allerede lagt til i innslaget?
		$sql = new Query("SELECT COUNT(*) 
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
		Logger::log( 324, $person_save->getContext()->getInnslag()->getId(), $person_save->getId().': '. $person_save->getNavn() );
		$sql = new Insert("smartukm_rel_b_p");
		$sql->add('b_id', $person_save->getContext()->getInnslag()->getId() );
		$sql->add('p_id', $person_save->getId());
		$res = $sql->run();
        
        // Insert OK, lagre 2020-relasjon 
        if( $res ) {
            $insert = new Insert('ukm_rel_arrangement_person');
            $insert->add('innslag_id', $person_save->getContext()->getInnslag()->getId() );
            $insert->add('person_id', $person_save->getId());
            $insert->add('arrangement_id', $person_save->getContext()->getMonstring()->getId());
            try {
                $res = $insert->run();
                return true;
            } catch( Exception $e ) {
                throw new Exception(
                    'Kunne ikke lagre relasjon mellom arrangement og person. Kontakt support@ukm.no. Systemet sa:'. $e->getMessage(),
                    506013
                );
            }        
        }

		if( !$res ) {
			return false;
		}
		return true;
	}
	
	/**
	 * Legg til relasjon til arrangementet (gammel videresending)
	 *
	 * @param Person $person_save
	**/
	private static function _leggTilArrangement( Person $person_save ) {
        // Pre-2020-relasjon
        if( $person_save->getContext()->getSesong() < 2020 ) {
            if( $person_save->getContext()->getMonstring()->getType() == 'kommune' ) {
                return true;
            }
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
        }

        // 2020-relasjon
        $insert = new Insert('ukm_rel_arrangement_person');
        $insert->add('innslag_id', $person_save->getContext()->getInnslag()->getId() );
        $insert->add('person_id', $person_save->getId());
        $insert->add('arrangement_id', $person_save->getContext()->getMonstring()->getId());
        
        try {
            $res = $insert->run();
            return true;
        } catch( Exception $e ) {
            if( $e->getCode() == 901001 ) {
                return true;
            }
            throw new Exception(
                'En ukjent feil oppsto. Systemet sa: '. $e->getMessage() .' ('. $insert->debug().')',
                506019
            );
        }

        throw new Exception(
            'Kunne ikke videresende '. $person_save->getNavn() .'.',
            506012
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
	 * @param Person $person
	 *
	 * @return Bool true|
     * @throws Exception hvis feilet)
	 */	 
	private static function _slett( Person $person_save ) {

        Logger::log( 325, $person_save->getContext()->getInnslag()->getId(), $person_save->getId().': '. $person_save->getNavn() );

        // 2020-relasjon
        $relasjon = new Delete(
            'ukm_rel_arrangement_person',
            [
                'innslag_id' => $person_save->getContext()->getInnslag()->getId(),
                'person_id' => $person_save->getId(),
            ]
        );
        $relasjon->run();

        // Pre-2020-relasjon
		$sql = new Delete("smartukm_rel_b_p", 
			array( 	'b_id' => $person_save->getContext()->getInnslag()->getId(),
					'p_id' => $person_save->getId(),
					));
		$res = $sql->run();
		if( $res ) {
            /*
             * SAMTYKKE: Fjerner samtykke-forespørsel for dette innslaget.
             * Hvis personen allerede har godkjent gjøres ingenting.
             * Hvis personen deltar i flere innslag fjernes kun forespørsel for dette
             * innslaget, mens for andre innslag vil den fortsatt stå.
             */
            $samtykke = new PersonSamtykke( $person_save, $person_save->getContext()->getInnslag(), $person_save->getContext()->getSesong() );
            $samtykke->fjernInnslag( $person_save->getContext()->getInnslag()->getId() );

			return true;
		}
		
		throw new Exception(
			'Kunne ikke fjerne '. $person_save->getNavn() .' fra innslaget. ',
			506011
		);
	}
	
	/**
	 * 
	 * Avrelaterer en person til dette innslaget.
	 *
	 * @param Person $person
	 *
	 * @return Bool true
     * @throws Exception hvis feilet
	 */
	private static function _fjernArrangement( Person $person_save ) {
        // Pre-2020-relasjon
        if( $person_save->getContext()->getSesong() < 2020 ) {
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
        }
        // 2020-relasjon
        else {
            $delete = new Delete(
                'ukm_rel_arrangement_person',
                [
                    'innslag_id' => $person_save->getContext()->getInnslag()->getId(),
                    'person_id' => $person_save->getId(),
                    'arrangement_id' => $person_save->getContext()->getMonstring()->getId()
                ]
            );
            $res = $delete->run();
            
            if( $res ) {
                return true;
            }
        }
        		
		throw new Exception(
			'Kunne ikke avmelde '. $person_save->getNavn() .'.',
			506010
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
		if( !Person::validateClass($person)) {
			throw new Exception(
				'Person må være objekt av klassen person_v2',
				506009
			);
		}
		if( !is_numeric( $person->getId() ) || $person->getId() <= 0 ) {
			throw new Exception(
				'Person-objektet må ha en numerisk ID større enn null',
				506008
			);
		}
	}
	
	/**
	 * Valider alle input-parametre for opprettelse av ny person
	 *
	 * @see create()
	**/
	private static function _validerCreate( $fornavn, $etternavn, $mobil ) {
		if(!is_string($fornavn) || empty($fornavn) || !is_string($etternavn) || empty($etternavn) ) {
			throw new Exception(
				"Fornavn og etternavn må være en streng.",
				506007
			);
		}
		if( !is_numeric($mobil) || 8 != strlen($mobil) ) {
			throw new Exception(
				"Mobilnummeret må bestå kun av tall og være 8 siffer langt!",
				506006
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
				506003
			);
		}
		// Valider kontekst (tilknytning til innslag)
		if( $person_save->getContext()->getInnslag() == null ) {
			throw new Exception(
				'Kan ikke legge til/fjerne person. '.
				'Person-objektet er ikke opprettet i riktig kontekst',
				506004
			);
		}
	}
}