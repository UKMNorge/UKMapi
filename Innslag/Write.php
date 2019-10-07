<?php

namespace UKMNorge\Innslag;

use statistikk;

use Exception;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Arrangement\Program\Write as WriteHendelse;
use UKMNorge\Database\SQL\Delete;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Geografi\Kommune;
use UKMNorge\Innslag\Personer\Person;
use UKMNorge\Innslag\Personer\Write as WritePerson;
use UKMNorge\Logger\Logger;
use UKMNorge\Samtykke\Person as PersonSamtykke;

require_once('UKM/Autoloader.php');

class Write {
    	/**
	 * Opprett et nytt innslag, og relater til kommune
	 *
	 * @param kommune $kommune
	 * @param monstring $monstring
	 * @param innslag_type $type 
	 * @param string $navn
	 * @param person_v2 $contact
	 *
	 * @return Innslag $innslag
	**/
	public static function create( $kommune, $monstring, $type, $navn, $contact ) {
		// Valider at logger er på plass
		if( !Logger::ready() ) {
			throw new Exception(
                Logger::getError(),
                50501
            );
		}
		// Valider alle input-parametre
		try {
			Write::_validerCreate( $kommune, $monstring, $type, $navn, $contact );
		} catch( Exception $e ) {
			throw new Exception(
				'Kunne ikke opprette innslag. '. $e->getMessage(),
				$e->getCode()
			);
		}
		
		## CREATE INNSLAG-SQL
		$band = new Insert('smartukm_band');
		$band->add('b_season', $monstring->getSesong() );
		$band->add('b_status', 0); ## Hvorfor får innslaget b_status 8 her???
		$band->add('b_name', $navn);
		$band->add('b_kommune', $kommune->getId());
		$band->add('b_year', date('Y'));
		$band->add('b_subscr_time', time());
		$band->add('bt_id', $type->getId() );
        $band->add('b_contact', $contact->getId() );
        $band->add('b_home_pl', $monstring->getId());

		if( 1 == $type->getId() ) {
			$band->add('b_kategori', $type->getKey() );
		}

		$band_id = $band->run();
		if( !$band_id ) {

			throw new Exception(
				"Klarte ikke å opprette et nytt innslag.",
				50508
			);
		}

		$tech = new Insert('smartukm_technical');
		$tech->add('b_id', $band_id );
		$tech->add('pl_id', $monstring->getId() );
		
		$techres = $tech->run();
		if( !$techres ) {
			throw new Exception(
				"Klarte ikke å opprette tekniske behov-rad i tabellen.",
				50509
			);
		}		

		// TODO: Burde benytte $monstring->getInnslag()->leggTil( $innslag );
		$rel = new Insert('smartukm_rel_pl_b');
		$rel->add('pl_id', $monstring->getId() );
		$rel->add('b_id', $band_id );
		$rel->add('season', $monstring->getSesong() );
		
		$relres = $rel->run();
		if( !$relres ) {
			throw new Exception(
				"Klarte ikke å melde på det nye innslaget til mønstringen.",
				50510
			);
		}
		
		// TODO: KREVER at relasjonen over gjøres riktig (leggTil, ikke db-insert)
		return $monstring->getInnslag()->get( $band_id, true );
		// TODO: Oppdater statistikk
		#$innslag = new innslag( $b_id, false );
		#$innslag->statistikk_oppdater();
		return new Innslag( (Int)$band_id ); // Tror ikke cast er nødvendig, men det er gjort sånn i WritePerson.
	}	




	/********************************************************************************
	 *
	 *
	 * LAGRE DETALJER DIREKTE PÅ INNSLAGET
	 *
	 *
	 ********************************************************************************/


	/**
	 * Lagre et innslag-objekt
	 *
	 * @param Innslag $innslag_save
	 * @return bool true
	**/
	public static function save( $innslag_save ) {
		// Valider logger
		static::validerLogger();
		// Valider input-data
		try {
			Write::validerInnslag( $innslag_save );
		} catch( Exception $e ) {
			throw new Exception(
				'Kan ikke lagre innslag. '. $e->getMessage(),
				$e->getCode()
			);
		}

		// Hent sammenligningsgrunnlag
		try {
			$innslag_db = new Innslag( (Int) $innslag_save->getId(), true );
		} catch( Exception $e ) {
			throw new Exception(
				'Kan ikke lagre innslagets endringer. Feil ved henting av kontroll-innslag. '. $e->getMessage(),
				$e->getCode()
			);
        }

		// TABELLER SOM KAN OPPDATERES
		$smartukm_band = new Update('smartukm_band', array('b_id'=>$innslag_save->getId()));
		$smartukm_technical = new Update('smartukm_technical', array('b_id'=>$innslag_save->getId()));

        // EVALUER INNSLAGETS MANGLER
        $innslag_save->evaluerMangler();
        // Hvis innslaget ikke har noen mangler, er status=8 (påmeldt)
        if( $innslag_save->getMangler()->getAntall() == 0 ) {
            $innslag_save->setStatus(8);
            $smartukm_band->add('b_status', 8);
            Logger::log( 304, $innslag_save->getId(), '');
        }
        
		// VERDIER SOM KAN OPPDATERES
		$properties = [
			'Navn' 			=> ['smartukm_band', 'b_name', 301],
			'Sjanger' 		=> ['smartukm_band', 'b_sjanger', 306],
			'Beskrivelse'	=> ['smartukm_band', 'b_description', 309],
            'TekniskeBehov'	=> ['smartukm_technical', 'td_demand', 308],
            'ManglerJSON'   => ['smartukm_band', 'b_status_object', 328],
		];
		
		// LOOP ALLE VERDIER, OG EVT LEGG TIL I SQL
		foreach( $properties as $functionName => $logValues ) {
			$function = 'get'.$functionName;
			$table = $logValues[0];
			$field = $logValues[1];
			$action = $logValues[2];
			$sql = $$table;
			
			if( $innslag_db->$function() != $innslag_save->$function() ) {
				# Mellomlagre verdi som skal settes
				$value = $innslag_save->$function();
				# Legg til i SQL
				$sql->add( $field, $value ); 	// SQL satt dynamisk i foreach til $$table
				# Logg (eller dø) før vi kjører run
				Logger::log( $action, $innslag_save->getId(), $value );
			}
		}
		
		// SPESIAL-VERDIER
		# KOMMUNE
		if( $innslag_db->getKommune()->getId() != $innslag_save->getKommune()->getId() ) {
			$smartukm_band->add('b_kommune', $innslag_save->getKommune()->getId() );
			Logger::log( 307, $innslag_save->getId(), $innslag_save->getKommune()->getId() );
		}
		# KONTAKTPERSON
		if( $innslag_db->getKontaktperson()->getId() != $innslag_save->getKontaktperson()->getId() ) {
			$smartukm_band->add('b_contact', $innslag_save->getKontaktperson()->getId() );
			Logger::log( 302, $innslag_save->getId(), $innslag_save->getKontaktperson()->getId() );
		}		

		if( $smartukm_band->hasChanges() ) {
			#echo $smartukm_band->debug();
			$smartukm_band->run();
		}
		if( $smartukm_technical->hasChanges() ) {
			#echo $smartukm_technical->debug();
			$smartukm_technical->run();
		}
		
		require_once('UKM/statistikk.class.php');
        statistikk::oppdater_innslag( $innslag_save );
        
        // Etterspør samtykke fra alle deltakere i innslaget
        static::requestSamtykke( $innslag_save );

        #return $innslag_save;
	}


	/**
	 * Lagre endringer i innslagets status
	 *
	 * @param Innslag $innslag_save
	 * @return bool true
	**/
	public static function saveStatus( $innslag_save ) {
		// Valider logger
		static::validerLogger();
		// Valider input-data
		try {
			Write::validerInnslag( $innslag_save );
		} catch( Exception $e ) {
			throw new Exception(
				'Kan ikke lagre innslagets status. '. $e->getMessage(),
				$e->getCode()
			);
		}

		// Hent sammenligningsgrunnlag
		try {
			$innslag_db = new Innslag( $innslag_save->getId(), true );
		} catch( Exception $e ) {
			throw new Exception(
				'Kan ikke lagre innslagets status. Feil ved henting av kontroll-innslag. '. $e->getMessage(),
				$e->getCode()
			);
		}

		if( $innslag_db->getStatus() == $innslag_save->getStatus() ) {
			return true;
		}

		// TABELLER SOM KAN OPPDATERES
		$smartukm_band = new Update(
			'smartukm_band', 
			[
				'b_id' => $innslag_save->getId()
			]
		);
		$smartukm_band->add('b_status', $innslag_save->getStatus() );
		$res = $smartukm_band->run();

		require_once('UKM/statistikk.class.php');
		statistikk::oppdater_innslag( $innslag_save );
		
		return $res;
	}

	/********************************************************************************
	 *
	 *
	 * LAGRE ENDRINGER I INNSLAGETS INTERNE COLLECTIONS
	 *
	 *
	 ********************************************************************************/


	/**
	 * Lagre endringer i personer-collection
	 *
	 * Samme som å kjøre flere 
	 *  WritePerson::leggTil( $person ) eller
	 *  WritePerson::fjern( $person )
	 *
	 * @param Innslag $innslag_save
	 * @return void
	**/
	public static function savePersoner( $innslag_save ) {
		// Valider logger
		if( !Logger::ready() ) {
			throw new Exception(
				'Logger is missing or incorrect set up.',
				50501
			);
		}
		// Valider input-data
		try {
			Write::validerInnslag( $innslag_save );
		} catch( Exception $e ) {
			throw new Exception(
				'Kan ikke lagre personer i innslaget. '. $e->getMessage(),
				$e->getCode()
			);
		}
		
		// Opprett mønstringen innslaget kommer fra
		$monstring = new Arrangement( $innslag_save->getContext()->getMonstring()->getId() );
		// Hent innslaget fra gitt mønstring
		$innslag_db = $monstring->getInnslag()->get( $innslag_save->getId(), true );

		foreach( $innslag_save->getPersoner()->getAll() as $person ) {
			if( !$innslag_db->getPersoner()->har( $person ) ) {
				WritePerson::leggTil( $person );
			}
		}
		foreach( $innslag_db->getPersoner()->getAll() as $person ) {
			if( !$innslag_save->getPersoner()->har( $person ) ) {
				WritePerson::fjern( $person );
			}
		}
	}


	/**
	 * Lagre endringer i titler-collection
	 *
	 * Samme som å kjøre flere 
	 *  write_tittel::leggTil( $tittel ) eller
	 *  write_tittel::fjern( $tittel )
	 *
	 * @param Innslag $innslag_save
	 * @return void
	**/
	public static function saveTitler( $innslag_save ) {
		// Valider logger
		if( !Logger::ready() ) {
			throw new Exception(
				'Logger is missing or incorrect set up.',
				50501
			);
		}
		// Valider input-data
		try {
			Write::validerInnslag( $innslag_save );
		} catch( Exception $e ) {
			throw new Exception(
				'Kan ikke lagre titler i innslaget. '. $e->getMessage(),
				$e->getCode()
			);
		}
		
		// Opprett mønstringen innslaget kommer fra
		$monstring = new Arrangement( $innslag_save->getContext()->getMonstring()->getId() );
		// Hent innslaget fra gitt mønstring
		$innslag_db = $monstring->getInnslag()->get( $innslag_save->getId(), true );
		
		// Hvis lagre-innslaget ikke har noen titler, slett alle
		if( $innslag_save->getTitler()->getAntall() == 0 ) {
			foreach( $innslag_db->getTitler()->getAll() as $tittel ) {
				WriteTittel::fjern( $tittel );
			}
		}

		// Legg til alle titler $innslag_save har som ikke $innslag_db har
		foreach( $innslag_save->getTitler()->getAll() as $tittel ) {
			if( !$innslag_db->getTitler()->har( $tittel ) ) {
				WriteTittel::leggTil( $tittel );
			}
		}
		
		// Fjern alle titler som $innslag_db har, som ikke $innslag_save har
		foreach( $innslag_db->getTitler()->getAll() as $tittel ) {
			if( !$innslag_save->getTitler()->har( $tittel ) ) {
				WriteTittel::fjern( $tittel );
			}
		}
	}

	/**
	 * Lagre endringer i program-collection
	 *
	 * Samme som å kjøre flere 
	 *  write_forestilling::leggTil( $tittel ) eller
	 *  write_forestilling::fjern( $tittel )
	 *
	 * @param Innslag $innslag_save
	 * @return void
	**/
	public static function saveProgram( $innslag_save ) {
		// Valider logger
		static::validerLogger();
		// Valider input-data
		try {
			Write::validerInnslag( $innslag_save );
		} catch( Exception $e ) {
			throw new Exception(
				'Kan ikke lagre endringer i programmet. '. $e->getMessage(),
				$e->getCode()
			);
		}
		
		// Opprett mønstringen innslaget kommer fra
		$monstring = new Arrangement( $innslag_save->getContext()->getMonstring()->getId() );
		// Hent innslaget fra gitt mønstring
		$innslag_db = $monstring->getInnslag()->get( $innslag_save->getId(), true );

		foreach( $innslag_save->getProgram()->getAllInkludertSkjulte() as $hendelse ) {
			if( !$innslag_db->getProgram()->har( $hendelse ) ) {
				WriteHendelse::leggTil( $hendelse );
			}
		}
		foreach( $innslag_db->getTitler()->getAllInkludertSkjulte() as $tittel ) {
			if( !$innslag_save->getTitler()->har( $tittel ) ) {
				WriteHendelse::fjern( $tittel );
			}
		}
	}
	
	
	
	

	/********************************************************************************
	 *
	 *
	 * LEGG TIL OG FJERN INNSLAG FRA COLLECTION
	 *
	 *
	 ********************************************************************************/

	/**
	 * Legger til et innslag i collection og database
	 *
	 * @param Write $innslag
	 * @return $innslag
	 */
	public function leggTil( $innslag ) {
		Write::validerLeggtil( $innslag );

		switch( $innslag->getContext()->getType() ) {
			case 'forestilling':
				Write::_leggTilForestilling( $innslag );
				break;
			case 'monstring':
				Write::_leggTilMonstring( $innslag );
				break;
		}
		return $innslag;
	}	
	
	/**
	 * Fjern et innslag
	 *
	 * @param Innslag $innslag
	 * @return void
	 */
	public static function fjern( Innslag $innslag ) {
		Write::validerLeggtil( $innslag );
		
		switch( $innslag->getContext()->getType() ) {
			case 'forestilling':
				Write::_fjernFraForestilling( $innslag );
				break;
			case 'monstring':
				if( $innslag->getContext()->getMonstring()->getType() == 'kommune' ) {
					Write::_fjernFraLokalMonstring( $innslag );
				} else {
					Write::_fjernVideresending( $innslag );
				}
				break;
			default: 
				throw new Exception(
					'Kan ikke fjerne innslag fra ukjent collection type: ' . $innslag->getContext()->getType(),
					50511
				);
        }
        
        // Fjern samtykke-forespørsel for deltakerne i innslaget
        static::requestSamtykkeCancel( $innslag );
	}
	

	/********************************************************************************
	 *
	 *
	 * FJERN-HJELPERE
	 *
	 *
	 ********************************************************************************/

	/**
	 * Fjern et innslag fra en lokalmønstring
	 * Dette vil endre innslaget status, og effektivt melde det av
	 * 
	 * @param Write $innslag
	 * @return $innslag
	**/
	private static function _fjernFraLokalMonstring( $innslag ) {
		// Sjekk at vi har riktig context
		if( $innslag->getContext()->getType() != 'monstring' ) {
			throw new Exception(
				'fjernFraLokalMonstring kan kun kalles på objekter med mønstring-context',
				50519
			);
		}

		// Er innslaget videresendt kan det ikke avmeldes
		if( $innslag->erVideresendt() ) {
			throw new Exception(
				'Du kan ikke melde av et innslag som er videresendt før du har fjernet videresendingen.',
				5051
			);
		}
		
		// Fjern fra alle forestillinger på mønstringen
		WriteHendelse::fjernInnslagFraAlleForestillingerIMonstring( $innslag );
	
		// "Slett" innslaget
		$SQLdel = new Delete(
			'smartukm_rel_pl_b',
			[
				'b_id' => $innslag->getId(),
				'pl_id' => $innslag->getContext()->getMonstring()->getId(),
				'season' => $innslag->getContext()->getMonstring()->getSesong()
			]
		);
		Logger::log( 311, $innslag->getId(), $innslag->getId() );
		$res = $SQLdel->run();

		$innslag->setStatus(77);
		Write::saveStatus( $innslag );
		
		return $innslag;
	}
	
	/**
	 * Fjern et innslag fra denne forestillingen
	 *
	 * @param Innslag $innslag
	 * @return $innslag
	**/
	private function _fjernFraForestilling( $innslag ) {
		// Sjekk at vi har riktig context
		if( $innslag->getContext()->getType() != 'forestilling' ) {
			throw new Exception(
				'fjernFraForestilling kan kun kalles på objekter med forestilling-context',
				50518
			);
		}

		// Logg (eller dø) før sql utføres
		Logger::log( 220, $innslag->getContext()->getForestilling()->getId(), $innslag->getId() );

		// Fjern fra forestillingen
		$qry = new Delete(	'smartukm_rel_b_c', 
							array(	'c_id' => $innslag->getContext()->getForestilling()->getId(),
									'b_id' => $innslag->getId() ) );
		$res = $qry->run();

		if( 1 != $res ) {
			throw new Exception(
				'Klarte ikke å fjerne innslaget fra forestillingen.',
				50520
			);
		}
		return $innslag;
	}
	
	/**
	 * Fjern et innslag fra en (fylke|land)mønstring
	 * Vil fjerne videresendingen av innslaget
	 *
	 * @param Innslag $innslag
	 * @return $innslag
	**/
	private function _fjernVideresending( $innslag ) {
		// Sjekk at vi har riktig context
		if( $innslag->getContext()->getType() != 'monstring' ) {
			throw new Exception(
				'fjernVideresending kan kun kalles på objekter med mønstring-context',
				50518
			);
		}		
		// Fjern fra alle forestillinger på mønstringen
		WriteHendelse::fjernInnslagFraAlleForestillingerIMonstring( $innslag );
	
		// Meld av alle personer hvis dette er innslag hvor man kan velge personer som følger innslaget
		if( $innslag->getType()->getId() != 1 ) {
			foreach( $innslag->getPersoner()->getAllVideresendt( $innslag->getContext()->getMonstring()->getId() ) as $person ) {
				$innslag->getPersoner()->fjern( $person );
			}
			Write::savePersoner( $innslag );
		}

		// Meld av alle titler
		if( $innslag->getType()->harTitler() ) {
			foreach( $innslag->getTitler()->getAll( ) as $tittel ) {
				$innslag->getTitler()->fjern( $tittel );
			}
			Write::saveTitler( $innslag );
		}
		
		// Fjern videresendingen av innslaget
		$SQLdel = new Delete(
			'smartukm_fylkestep',
			[
				'pl_id' => $innslag->getContext()->getMonstring()->getId(),
				'b_id'	=> $innslag->getId(),
			]
		);
		
		Logger::log( 319, $innslag->getId(), $innslag->getContext()->getMonstring()->getId() );
		$res = $SQLdel->run();
		
		// Fjern rel pl_b
		$slett_relasjon = new Delete(
			'smartukm_rel_pl_b',
			[
				'pl_id'		=> $innslag->getContext()->getMonstring()->getId(),
				'b_id'		=> $innslag->getId(),
				'season'	=> $innslag->getContext()->getMonstring()->getSesong(),
			]
		);
		$slett_relasjon->run();

	
		if($res) {
			return true;
		}
		
		return $innslag;
	}

	/********************************************************************************
	 *
	 *
	 * LEGG TIL-HJELPERE
	 *
	 *
	 ********************************************************************************/

	/**
	 * Legg til et innslag i en forestilling
	 *
	 * @param Innslag $innslag
	**/
	private function _leggTilForestilling( $innslag ) {
		if( $innslag->getContext()->getType() != 'forestilling' ) {
			throw new Exception(
				'leggTilForestilling kan kun kalles på objekter med forestilling-context',
				50518
			);
		}

		Logger::log( 219, $innslag->getContext()->getForestilling()->getId(), $innslag->getId() );

		$lastorder = new Query("SELECT `order`
							  FROM `smartukm_rel_b_c`
							  WHERE `c_id` = '#cid'
							  ORDER BY `order` DESC
							  LIMIT 1",
							  array('cid' => $innslag->getContext()->getForestilling()->getId() ) );
		$lastorder = $lastorder->run('field','order');
		$order = (int)$lastorder+1;
		
		$qry = new Insert('smartukm_rel_b_c');
		$qry->add('b_id', $innslag->getId() );
		$qry->add('c_id', $innslag->getContext()->getForestilling()->getId() );
		$qry->add('order', $order);
		$res = $qry->run();
		
		if( !$res ) {
			throw new Exception(
				'Klarte ikke å legge til innslaget i forestillingen.',
				50513
			);
		}
		return $innslag;
	}
	
	/**
	 * Legg til et innslag i en mønstring
	 *
	 * Bruk ::create for å legge til på lokalnivå, denne
	 * vil bare returnere true
	 *
	 * @param Innslag $innslag
	**/
	private function _leggTilMonstring( $innslag ) {
		if( $innslag->getContext()->getType() != 'monstring' ) {
			throw new Exception(
				'leggTilMonstring kan kun kalles på objekter med mønstring-context',
				50518
			);
		}

		if( $innslag->getContext()->getMonstring()->getType() == 'kommune' ) {
			return true;
		}

		$test_relasjon = new Query(
			"SELECT `id` FROM `smartukm_fylkestep`
				WHERE `pl_id` = '#pl_id'
				AND `b_id` = '#b_id'",
			[
				'pl_id'		=> $innslag->getContext()->getMonstring()->getId(),
		  		'b_id'		=> $innslag->getId(), 
			]
		);
		$test_relasjon = $test_relasjon->run();
		
		// Hvis allerede videresendt, alt ok
		if( Query::numRows($test_relasjon) > 0 ) {
			return true;
		}
		// Videresend innslaget
		else {
			$videresend = new Insert('smartukm_fylkestep');
			$videresend->add('pl_id', $innslag->getContext()->getMonstring()->getId() );
			$videresend->add('b_id', $innslag->getId() );

			Logger::log( 318, $innslag->getId(), $innslag->getContext()->getMonstring()->getId() );
			$res = $videresend->run();
			
			// Test relasjon i rel_pl_b
			$test_relasjon_2 = new Query(
				"SELECT `pl_id`
				FROM `smartukm_rel_pl_b`
				WHERE `pl_id` = '#pl_id'
				AND `b_id` = '#b_id'",
				[
					'pl_id'		=> $innslag->getContext()->getMonstring()->getId(),
					'b_id'		=> $innslag->getId(),
				]
			);
			$test_relasjon_2 = $test_relasjon_2->run();
			if( Query::numRows( $test_relasjon_2 ) == 0 ) {
				$rel_pl_b = new Insert('smartukm_rel_pl_b');
				$rel_pl_b->add('b_id', $innslag->getId() );
				$rel_pl_b->add('pl_id', $innslag->getContext()->getMonstring()->getId() );
				$rel_pl_b->add('season', $innslag->getContext()->getMonstring()->getSesong() );
				$res2 = $rel_pl_b->run();
			}
			
			// Tittelløse innslag i V1 henter ikke automatisk alle personer
			if( !$innslag->getType()->harTitler() ) {
				$test_relasjon_3 = new Query(
					"SELECT `pl_id`
					FROM `smartukm_fylkestep_p`
					WHERE `pl_id` = '#pl_id'
					AND `b_id` = '#b_id'
					AND `p_id` = '#p_id'",
					[
						'pl_id'		=> $innslag->getContext()->getMonstring()->getId(),
						'b_id'		=> $innslag->getId(),
						'p_id'		=> $innslag->getPersoner()->getSingle()->getId()
					]
				);
				$test_relasjon_3 = $test_relasjon_3->run();
				if( Query::numRows( $test_relasjon_3 ) == 0 ) {
					$fylkestep_p = new Insert('smartukm_fylkestep_p');
					$fylkestep_p->add('pl_id', $innslag->getContext()->getMonstring()->getId() );
					$fylkestep_p->add('b_id', $innslag->getId() );
					$fylkestep_p->add('p_id', $innslag->getPersoner()->getSingle()->getId() );
					$res3 = $fylkestep_p->run();
				}

			}
		
			if( $res ) {
				return true;
			}
		}

		throw new Exception(
			'Kunne ikke videresende '. $innslag->getNavn() .' til mønstringen',
			50521
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
     * Sjekk at loggeren er klar, og gi skikkelig tilbakemelding
     *
     * @throws Exception hvis ikke klar
     */
	public static function validerLogger() {
        if( !Logger::ready() ) {
			throw new Exception(
				Logger::getError(),
				50501
			);
		}
    }

	/**
	 * Valider at gitt innslag-objekt er av riktig type
	 * og har en numerisk Id som kan brukes til database-modifisering
	 *
	 * @param anything $innslag
	 * @return void
	**/
	public static function validerInnslag( $innslag ) {
		if( !Innslag::validateClass($innslag) ) {
			throw new Exception(
				'Innslag må være objekt av klassen Innslag',
				50514
			);
		}
		if( !is_numeric( $innslag->getId() ) || $innslag->getId() <= 0 ) {
			throw new Exception(
				'Innslag-objektet må ha en numerisk ID større enn null',
				50515
			);
		}
	}

	/**
	 * Valider alle input-parametre for opprettelse av ny person
	 *
	 * @see create()
	**/
	private static function _validerCreate( $kommune, $arrangement, $type, $navn, $kontaktperson ) {
		if( !Arrangement::validateClass($arrangement) ) {
			throw new Exception(
				"Krever arrangement-objekt, ikke ".get_class($arrangement).".",
				50502
			);
		}
		if( !Kommune::validateClass($kommune) ) {
			throw new Exception(
				"Krever kommune-objekt, ikke ".get_class($kommune).".",
				50503
			);
		}
		if( !Type::validateClass($type) ) {
			throw new Exception(
				"Krever Type-objekt, ikke ". get_class($type) .".",
				50504
			);
		}
		if( !Person::validateClass($kontaktperson) ) {
			throw new Exception(
				"Krever skrivbar person, ikke ".get_class($kontaktperson),
				50505
			);	
		}
		if( empty($navn) ) {
			throw new Exception(
				"Må ha innslagsnavn.",
				50506
			);
		}

		if( !in_array($type->getKey(), array('scene', 'musikk', 'dans', 'teater', 'litteratur', 'film', 'video', 'utstilling', 'konferansier', 'nettredaksjon', 'arrangor','ressurs') ) ) {
			throw new Exception(
				"Kan ikke opprette ".$type->getKey()."-innslag.",
				50507
			);
		}
	}
	
	/**
	 * Valider alle input-parametre for å legge til nytt innslag
	 *
	 * @see leggTil
	**/
	public static function validerLeggtil( $innslag_save ) {
		// Valider input-data
		try {
			Write::validerInnslag( $innslag_save );
		} catch( Exception $e ) {
			throw new Exception(
				'Kan ikke legge til/fjerne innslag. '. $e->getMessage(),
				$e->getCode()
			);
		}
		
		// Valider kontekst (tilknytning til mønstring)
		if( $innslag_save->getContext()->getMonstring() == null && $innslag_save->getContext()->getForestilling() == null ) {
			throw new Exception(
				'Kan ikke legge til/fjerne innslag. '.
				'Person-objektet er ikke opprettet i riktig kontekst',
				50512
			);
		}
		
		if( is_object( $innslag_save->getContext()->getMonstring() ) ) {
			if( !is_numeric( $innslag_save->getContext()->getMonstring()->getId() ) ) {
				throw new Exception(
					'Kan ikke legge til/fjerne innslag. Mangler numerisk mønstrings-ID',
					50516
				);
			}
		}		
		if( is_object( $innslag_save->getContext()->getForestilling() ) ) {
			if( !is_numeric( $innslag_save->getContext()->getForestilling()->getId() ) ) {
				throw new Exception(
					'Kan ikke legge til/fjerne innslag. Mangler numerisk forestilling-ID',
					50517
				);
			}
		}
    }
    
    /**
     * Etterspør samtykke fra alle deltakere
     * 
     * Iterer over alle personer og opprett samtykke-request hvis den
     * ikke allerede eksisterer
     * 
     * Default-state er 'ikke_sendt'. Trenger derfor ikke å kjøre setStatus()
     * 
     * @param Innslag $innslag
     * @return Bool true
     */
    public static function requestSamtykke( Innslag $innslag ) {
        // Hvis innslaget ikke er fullstendig påmeldt, ikke send samtykke-sms
        if( !$innslag->erPameldt() ) {
            return true;
        }
        
        // Varsle alle personer i innslaget
        foreach ($innslag->getPersoner()->getAll() as $person) {
            $samtykke = new PersonSamtykke($person, $innslag);
        }

        // Varsle kontaktpersonens foresatte
        // (kontaktpersonen har selv svart tidligere)
        $samtykke = new PersonSamtykke($innslag->getKontaktperson(), $innslag);
        if ($samtykke->getKategori()->getId() != '15o' && $samtykke->harForesatt()) {
            // SendMelding sjekker om den er sendt fra før, og dobbeltsender ikke
            $samtykke->getKommunikasjon()->sendMelding('samtykke_foresatt');
        }

        return true;
    }

    /**
     * Avbryt samtykkeforespørsel for et innslag
     * Brukes ved avmelding. Sletter ikke et evt mottatt samtykke,
     * kun at denne personen er relatert til dette innslaget.
     *
     * @param Innslag $innslag
     * @return void
     */
    public static function requestSamtykkeCancel( Innslag $innslag ) {
        foreach ($innslag->getPersoner()->getAll() as $person) {
            $samtykke = new PersonSamtykke($person, $innslag);
            $samtykke->fjernInnslag($innslag->getId());
        }
    }
}