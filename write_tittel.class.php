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
		$innslag_db = $monstring->getInnslag()->get( $tittel_save->getContext()->getInnslag()->getId() );
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
}