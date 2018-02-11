<?php
require_once('UKM/sql.class.php');
require_once('UKM/tittel.class.php');

class write_tittel {
	

	/**
	 * Oppretter et nytt tittel og lagrer i databasen.
	 *
	 * @param innslag_v2 $innslag
 	 * @return false or integer (insert-ID).
 	 */
	public static function create( $innslag ) {		
		// Valider logger
		if( !UKMlogger::ready() ) {
			throw new Exception(
				'Logger is missing or incorrect set up.',
				50901
			);
		}
		// Valider input-data
		try {
			write_innslag::validerInnslag( $innslag );
		} catch( Exception $e ) {
			throw new Exception(
				'Kunne ikke opprette tittel'. $e->getMessage(),
				$e->getCode()
			);
		}

		// Opprett spørringen
		$qry = new SQLins( $innslag->getType()->getTabell() );
		$qry->add( 'b_id', $innslag->getId() );
		switch( $innslag->getType()->getTabell() ) {
			case 'smartukm_titles_scene':
				$action = 501;
				break;
			case 'smartukm_titles_video':
				$action = 510; 
				break;
			case 'smartukm_titles_exhibition':
				$action = 514;
				break;
			default:
				// TODO
				throw new Exception(
					'Kan kun opprette en ny tittel for scene, video eller utstilling. '.$table.' er ikke støttet enda.',
					50902
				);
		}
		// Logg (eller dø) før insert
		UKMlogger::log( $action, $innslag->getId(), $qry->insid() );
		
		$res = $qry->run();
		if( 1 == $res ) {
			return $qry->insid();
		}

		throw new Exception(
			'Klarte ikke å opprette ny tittel.',
			50903
		);
	}

	public static function save( $tittel_save ) {
		// Valider logger
		if( !UKMlogger::ready() ) {
			throw new Exception(
				'Logger is missing or incorrect set up.',
				50901
			);
		}
		// Valider inputdata
		try {
			write_tittel::validerTittel( $tittel_save );
		} catch( Exception $e ) {
			throw new Exception(
				'Kan ikke lagre tittel. '. $e->getMessage(),
				$e->getCode()
			);
		}
		
		// Opprett mønstringen tittelen kommer fra
		$monstring = new monstring_v2( $tittel_save->getContext()->getMonstring()->getId() );
		// Hent innslaget fra gitt mønstring
		$innslag_db = $monstring->getInnslag()->get( $tittel_save->getContext()->getInnslag()->getId(), true );
		// Hent personen fra gitt innslag
		$tittel_db = $innslag_db->getTitler()->get( $tittel_save->getId() );

		// TABELLER SOM KAN OPPDATERES
		$sql = new SQLins(
			$tittel_save->getTable(), 
			[
				't_id' => $tittel_save->getId(),
				'b_id' => $innslag_db->getId(),
			]
		);
		// VERDIER SOM KAN OPPDATERES
		switch( $tittel_save->getTable() ) {
			case 'smartukm_titles_scene':
				$properties = [
					'Tittel' 				=> ['t_name', 502],
					'VarighetSomSekunder'	=> ['t_time', 503],
					'Instrumental'			=> ['t_instrumental', 504],
					'Selvlaget'				=> ['t_selfmade', 505],
					'TekstAv'				=> ['t_titleby', 506],
					'MelodiAv'				=> ['t_musicby', 507],
					'KoreografiAv'			=> ['t_coreography', 508],
					'LitteraturLesOpp'		=> ['t_litterature_read', 509],
				];
				break;
			case 'smartukm_titles_video':
				$properties = [
					'Tittel' 				=> ['t_v_title', 511],
					'VarighetSomSekunder'	=> ['t_v_time', 512],
					'Format'				=> ['t_v_format', 513],
				];
				break;
			case 'smartukm_titles_exhibition':
				$properties = [
					'Tittel' 				=> ['t_e_title', 515],
					'Type'					=> ['t_e_type', 516],
					'Beskrivelse'			=> ['t_e_comments', 517],
				];
				break;
			default: 
				throw new Exception(
					'Kunne ikke lagre tittel. Ukjent database-tabell '. $tittel_save->getTable(),
					50904
				);
		}

		// LOOP ALLE VERDIER, OG EVT LEGG TIL I SQL
		foreach( $properties as $functionName => $logValues ) {
			$function = 'get'.$functionName;
			$field = $logValues[0];
			$action = $logValues[1];
			
			if( $tittel_db->$function() != $tittel_save->$function() ) {
				# Mellomlagre verdi som skal settes
				$value = $tittel_save->$function();
				# Legg til i SQL
				$sql->add( $field, $value );
				# Logg (eller dø) før vi kjører run
				UKMlogger::log( $action, $tittel_save->getId(), $value );
			}
		}

		if( $sql->hasChanges() ) {
			$sql->run();
		}

		return true;
	}


	/********************************************************************************
	 *
	 *
	 * LEGG TIL OG FJERN PERSON FRA COLLECTION
	 *
	 *
	 ********************************************************************************/

	/**
	 * Legg til tittelen i innslaget
	 * Videresender automatisk til context-mønstring
	 * 
	 * @param tittel_v2 $tittel_save
	**/
	public static function leggtil( $tittel_save ) {
		// Valider inputs
		write_tittel::_validerLeggtil( $tittel_save );

		// Opprett mønstringen tittelen kommer fra
		$monstring = new monstring_v2( $tittel_save->getContext()->getMonstring()->getId() );
		// Hent innslaget fra gitt mønstring
		$innslag_db = $monstring->getInnslag()->get( $tittel_save->getContext()->getInnslag()->getId(), true );
		
		// En tittel vil alltid være lagt til lokalt
		
		// Videresend tittelen hvis ikke lokalmønstring
		if( $monstring->getType() != 'kommune' ) {
			$res = write_tittel::_leggTilVideresend( $tittel_save );
		}
		
		if( $res ) {
			return $this;
		}
		
		throw new Exception(
			'Kunne ikke legge til '. $tittel_save->getNavn() .' i innslaget. ',
			50913
		);
	}


	/**
	 * Fjern en videresendt tittel, og avmelder hvis gitt lokalmønstring
	 *
	 * @param tittel_v2 $tittel_save
	 *
	 * @return (bool true|throw exception)
	 */
	public function fjern( $tittel_save ) {
		// Valider inputs
		write_tittel::_validerLeggtil( $tittel_save );

		// Opprett mønstringen tittelen kommer fra
		$monstring = new monstring_v2( $tittel_save->getContext()->getMonstring()->getId() );
		// Hent innslaget fra gitt mønstring
		$innslag_db = $monstring->getInnslag()->get( $tittel_save->getContext()->getInnslag()->getId(), true );
		

		if( $monstring->getType() == 'kommune' ) {
			$res = write_tittel::_fjernLokalt( $tittel_save );
		} else {
			$res = write_tittel::_fjernVideresend( $tittel_save );
		}
		
		if( $res ) {
			return true;
		}
		
		throw new Exception(
			'Kunne ikke fjerne '. $tittel_save->getTittel() .' fra innslaget. ',
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
	 * Legg til en tittel på videresendt nivå
	 *
	 * @param tittel_v2 $tittel_save
	**/
	private function _leggTilVideresend( $tittel_save ) {
		$test_relasjon = new SQL(
			"SELECT * FROM `smartukm_fylkestep`
				WHERE `pl_id` = '#pl_id'
				AND `b_id` = '#b_id'
				AND `t_id` = '#t_id'",
			[
				'pl_id'		=> $tittel_save->getContext()->getMonstring()->getId(), 
		  		'b_id'		=> $tittel_save->getContext()->getInnslag()->getId(), 
				't_id'		=> $tittel_save->getId(),
			]
		);
		$test_relasjon = $test_relasjon->run();
		
		// Hvis allerede videresendt, alt ok
		if( mysql_num_rows($test_relasjon) > 0 ) {
			return true;
		}
		// Videresend tittelen
		else {
			$videresend_tittel = new SQLins('smartukm_fylkestep');
			$videresend_tittel->add('pl_id', $tittel_save->getContext()->getMonstring()->getId() );
			$videresend_tittel->add('b_id', $tittel_save->getContext()->getInnslag()->getId() );
			$videresend_tittel->add('t_id', $tittel_save->getId() );

			$log_msg = $tittel_save->getId().': '. $tittel_save->getTittel() .' => PL: '. $tittel_save->getContext()->getMonstring()->getId();
			UKMlogger::log( 322, $tittel_save->getContext()->getInnslag()->getId(), $log_msg );
			$res = $videresend_tittel->run();
		
			if( $res ) {
				return true;
			}
		}

		throw new Exception(
			'Kunne ikke videresende '. $tittel_save->getTittel(),
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
	 * Fjern en tittel fra innslaget helt
	 * @param tittel_v2 $tittel_save
	**/
	private static function _fjernLokalt( $tittel_save ) {
		UKMlogger::log( 327, $tittel_save->getContext()->getInnslag()->getId(), $tittel_save->getId() .': '. $tittel_save->getTittel() );
		$qry = new SQLdel( 
			$tittel_save->getTable(), 
			[
				't_id' => $tittel_save->getId(),
				'b_id' => $tittel_save->getContext()->getInnslag()->getId(),
			]
		);
		$res = $qry->run();

		if($res == 1) {
			return true;
		}

		throw new Exception(
			'Klarte ikke fjerne tittel ' . $tittel->getTittel(),
			50515
		);
	}
	
	/**
	 * 
	 * Avrelaterer en tittel fra dette innslaget.
	 *
	 * @param tittel_v2 $tittel_save
	 *
	 * @return (bool true|throw exception)
	 */
	public function _fjernVideresend( $tittel_save ) {
		$videresend_tittel = new SQLdel(
			'smartukm_fylkestep', 
			[
				'pl_id' 	=> $tittel_save->getContext()->getMonstring()->getId(),
				'b_id' 		=> $tittel_save->getContext()->getInnslag()->getId(),
				't_id' 		=> $tittel_save->getId()
			]
		);
		$log_msg = $tittel_save->getId().': '. $tittel_save->getTittel() .' => PL: '. $tittel_save->getContext()->getMonstring()->getId();
		UKMlogger::log( 323, $tittel_save->getContext()->getInnslag()->getId(), $log_msg );

		// Slett tittelen
		$res = $videresend_tittel->run();
		
		// Hvis slettingen gikk bra
		if( $res ) {

			/**
			 * Fjerning av siste tittel vil avmelde innslaget
			 * Skulle dette ikke være ønsket effekt, må det her settes inn en ny fylkesstep-rad
			 * med blank tittel-ID (som igjen må slettes ved innslag::avmeld()
			**/

			// Sjekk antall relasjoner som er igjen
			$test_remaining_fylkestep = new SQL(
				"SELECT COUNT(`id`) AS `num`
				FROM `smartukm_fylkestep`
				WHERE `pl_id` = '#pl_id'
				AND `b_id` = '#b_id'",
				[
					'pl_id' 	=> $tittel_save->getContext()->getMonstring()->getId(),
					'b_id' 		=> $tittel_save->getContext()->getInnslag()->getId(),
				]
			);
			$remaining_fylkestep = $test_remaining_fylkestep->run('field', 'num');
	
			/**
			 * HVIS ingen relasjoner gjenstår etter tittelen er fjernet, avmeld innslaget (manuelt)
			 * Kan ikke kalle write_innslag::fjern() da det blir circular loop
			**/
			if( $remaining_fylkestep == 0 ) {
				// Slett relasjonen manuelt
				$SQLdel = new SQLdel(
					'smartukm_rel_pl_b',
					[
						'b_id' 		=> $tittel_save->getContext()->getInnslag()->getId(),
						'pl_id' 	=> $tittel_save->getContext()->getMonstring()->getId(),
						'season' 	=> $tittel_save->getContext()->getMonstring()->getSesong()
					]
				);
				UKMlogger::log( 311, $tittel_save->getContext()->getInnslag()->getId(), $tittel_save->getContext()->getInnslag()->getId() );
				$res2 = $SQLdel->run();
			}
		}

		if( $res ) {
			return true;
		}

		throw new Exception(
			'Kunne ikke avmelde '. $tittel_save->getTittel() .' fra mønstringen',
			50907
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
	 * Valider at gitt tittel-objekt er av riktig type
	 * og har en numerisk Id som kan brukes til database-modifisering
	 *
	 * @param tittel_V2 $tittel
	 * @return void
	**/
	public static function validerTittel( $tittel ) {
		if( !is_object( $tittel ) || get_class( $tittel ) != 'tittel_v2' ) {
			throw new Exception(
				'Tittel må være objekt av klassen tittel_v2',
				50905
			);
		}
		if( !is_numeric( $tittel->getId() ) || $tittel->getId() <= 0 ) {
			throw new Exception(
				'Tittel-objektet må ha en numerisk ID større enn null',
				50906
			);
		}
	}
	
	
	/**
	 * Valider alle input-parametre for å legge til ny tittel
	 *
	 * @see leggTil
	**/
	private static function _validerLeggtil( $tittel_save ) {
		// Valider input-data
		try {
			write_tittel::validerTittel( $tittel_save );
		} catch( Exception $e ) {
			throw new Exception(
				'Kan ikke legge til/fjerne tittel. '. $e->getMessage(),
				$e->getCode()
			);
		}
		
		// Valider kontekst (tilknytning til mønstring)
		if( $tittel_save->getContext()->getMonstring() == null ) {
			throw new Exception(
				'Kan ikke legge til/fjerne tittel. '.
				'Tittel-objektet er ikke opprettet i riktig kontekst',
				50911
			);
		}
		// Valider kontekst (tilknytning til innslag)
		if( $tittel_save->getContext()->getInnslag() == null ) {
			throw new Exception(
				'Kan ikke legge til/fjerne tittel. '.
				'Tittel-objektet er ikke opprettet i riktig kontekst',
				50912
			);
		}
	}
}