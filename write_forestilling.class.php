<?php
require_once('UKM/logger.class.php');
require_once('UKM/innslag.class.php');
require_once('UKM/advarsel.class.php');
require_once('UKM/forestilling.class.php');

class write_forestilling {
	/**
	 * Opprett en ny hendelse
	 *
	 * @param monstring $monstring
	 *
	 * @return forestilling_v2 $forestilling
	**/
	public static function create( $monstring, $navn, $start ) {
			// Valider at logger er på plass
			if( !UKMlogger::ready() ) {
				throw new Exception(
					'Logger is missing or incorrect set up.',
					517004
				);
			}
			
			// Må være gitt mønstring
			if( !is_object( $monstring ) || get_class( $monstring ) != 'monstring_v2' ) {
				throw new Exception(
					'Kan ikke opprette hendelse uten gyldig mønstring-objekt',
					517005
				);
			}

			// Må være gitt navn som string
			if( !is_string( $navn ) ) {
				throw new Exception(
					'Kan ikke opprette hendelse uten navn som string',
					517006
				);
			}

			// Må være gitt start som DateTime
			if( get_class( $start ) != 'DateTime' ) {
				throw new Exception(
					'Kan ikke opprette hendelse uten start-tidspunkt som DateTime',
					517007
				);
			}
			
			## CREATE INNSLAG-SQL
			$sql = new SQLins('smartukm_concert');
			$sql->add('c_name', $navn );
			$sql->add('pl_id', $monstring->getId() );
			$sql->add('c_start', $start->getTimestamp() );
			
			$id = $sql->run();

			if( $id ) {
				UKMlogger::log( 219, $id, $navn );
			} else {
				throw new Exception(
					'Klarte ikke å opprette hendelse',
					517008
				);
			}

			return new forestilling_v2( $id );
		}

	/**
	 * Lagre et hendelse-objekt
	 *
	 * @param hendelse_v2 $hendelse_save
	 * @return bool true
	**/
	public static function save( $hendelse_save ) {
		// Valider logger
		if( !UKMlogger::ready() ) {
			throw new Exception(
				'Logger is missing or incorrect set up.',
				517003
			);
		}

		// Valider input-data
		try {
			write_forestilling::validerHendelse( $hendelse_save );
		} catch( Exception $e ) {
			throw new Exception(
				'Kan ikke lagre hendelse. '. $e->getMessage(),
				$e->getCode()
			);
		}

		// Hent sammenligningsgrunnlag
		try {
			$hendelse_db = new hendelse_v2( $hendelse_save->getId() );
		} catch( Exception $e ) {
			throw new Exception(
				'Kan ikke lagre hendelsens endringer. Feil ved henting av kontroll-hendelse. '. $e->getMessage(),
				$e->getCode()
			);
		}

		// TABELLER SOM KAN OPPDATERES
		$smartukm_concert = new SQLins(
			'smartukm_concert',
			[
				'c_id' => $hendelse_save->getId()
			]
		);
		
		// VERDIER SOM KAN OPPDATERES
		$properties = [
			'Navn' 					=> ['c_name', 'String', 206],
			'Sted'					=> ['c_place', 'String', 207],
			'Start'					=> ['c_start', 'DateTime', 208],
			'OppmoteFor'			=> ['c_before', 'Int', 214],
			'OppmoteDelay'			=> ['c_delay', 'Int', 215],
			'SynligRammeprogram'	=> ['c_visible_program', 'Bool', 213],
			'SynligDetaljprogram'	=> ['c_visible_detail', 'Bool', 217],
			'SynligOppmotetid'		=> ['c_visible_oppmote', 'Bool', 224],
			'Type'					=> ['c_type', 'String', 221],
			'TypePostId'			=> ['c_type_post_id', 'Int', 222],
			'TypeCategoryId'		=> ['c_type_category_id', 'Int', 223],
			'Intern'				=> ['c_intern', 'Bool', 225],
			'Beskrivelse'			=> ['c_beskrivelse', 'String', 226]
		];
		
		// LOOP ALLE VERDIER, OG EVT LEGG TIL I SQL
		foreach( $properties as $functionName => $logValues ) {
			$function = 'get'.$functionName;
			$field = $logValues[0];
			$type = $logValues[1];
			$action = $logValues[2];
			
			if( $hendelse_db->$function() != $hendelse_save->$function() ) {
				# Mellomlagre verdi som skal settes
				$value = $hendelse_save->$function();

				switch( $type ) {
					case 'Int':
						$value = (int) $value;
						break;
					case 'Bool':
						$value = $value ? 'true' : 'false';
						break;
					case 'DateTime':
						$value = $value->getTimestamp();
						break;
				}
				# Legg til i SQL
				$smartukm_concert->add( $field, $value );
				# Logg (eller dø) før vi kjører run
				UKMlogger::log( $action, $hendelse_save->getId(), $value );
			}
		}

		if( $smartukm_concert->hasChanges() ) {
			#echo $smartukm_concert->debug();
			$smartukm_concert->run();
		}
	}

	
	/**
	 * Legg til innslaget i hendelsen
	 * Videresender automatisk til context-mønstring
	 * 
	 * @param innslag_v2 $innslag_save
	**/
	public static function leggTil( $innslag_save ) {
		// Valider inputs
		write_forestilling::_validerLeggtil( $innslag_save );

		// Opprett mønstringen tittelen kommer fra
		$monstring = new monstring_v2( $tittel_save->getContext()->getMonstring()->getId() );
		// Hent innslaget fra gitt mønstring
		$innslag_db = $monstring->getInnslag()->get( $tittel_save->getContext()->getInnslag()->getId() );
		
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
	 * Valider alle input-parametre for å legge til 
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
	
	/**
	 * Fjern et innslag fra alle forestillinger på en mønstring
	 * Gjøres når et innslag er avmeldt en mønstring
	 *
	 * @param write_innslag $innslag
	 * @return $this
	**/
	public static function fjernInnslagFraAlleForestillingerIMonstring( $innslag ) {
		write_innslag::validerLeggtil( $innslag );

		// Opprett mønstringen innslaget kommer fra
		$monstring = new monstring_v2( $innslag->getContext()->getMonstring()->getId() );

		// Fjern innslaget fra alle hendelser i mønstringen
		foreach( $monstring->getProgram()->getAllInkludertSkjulte() as $forestilling ) {
			if( $forestilling->getInnslag()->har( $innslag ) ) {
				// Modifiserer ikke collectionen, da den kun eksisterer internt i funksjonen
				UKMlogger::log( 220, $forestilling->getId(), $innslag->getId() );
				$qry = new SQLdel(
					'smartukm_rel_b_c', 
					[
						'c_id' => $forestilling->getId(),
						'b_id' => $innslag->getId()
					]
				);
				$res = $qry->run();
			}
		}
	}

	/**
	 * Valider at gitt hendelse-objekt er av riktig type
	 * og har en numerisk Id som kan brukes til database-modifisering
	 *
	 * @param hendelse_v2 $hendelse
	 * @return void
	**/
	public static function validerHendelse( $hendelse ) {
		if( !is_object( $hendelse ) || !in_array(get_class( $hendelse ), ['forestilling_v2','hendelse_v2']) ) {
			throw new Exception(
				'Hendelse må være objekt av klassen hendelse_v2',
				517001
			);
		}
		if( !is_numeric( $hendelse->getId() ) || $hendelse->getId() <= 0 ) {
			throw new Exception(
				'Hendelse-objektet må ha en numerisk ID større enn null',
				517002
			);
		}
	}
}