<?php
require_once('UKM/logger.class.php');
require_once('UKM/innslag.class.php');
require_once('UKM/advarsel.class.php');

class write_forestilling {
	/**
	 * Opprett en ny hendelse
	 *
	 * @param monstring $monstring
	 *
	 * @return forestilling_v2 $forestilling
	**/
	public static function create( $monstring ) {
		throw new Exception(
			'write_forestilling::create er ikke implementert enda'
		);
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
}