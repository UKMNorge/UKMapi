<?php

namespace UKMNorge\Innslag;

use statistikk;

use Exception;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Arrangement\Program\Hendelse;
use UKMNorge\Arrangement\Write as WriteArrangement;
use UKMNorge\Arrangement\Program\Write as WriteHendelse;
use UKMNorge\Database\SQL\Delete;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Geografi\Kommune;
use UKMNorge\Innslag\Context\Innslag as InnslagContext;
use UKMNorge\Innslag\Personer\Person;
use UKMNorge\Innslag\Personer\Write as WritePerson;
use UKMNorge\Innslag\Typer\Typer;
use UKMNorge\Innslag\Typer\Type;
use UKMNorge\Log\Logger;
use UKMNorge\Samtykke\Person as PersonSamtykke;

require_once('UKM/Autoloader.php');

class Write {
    	/**
	 * Opprett et nytt innslag, og relater til kommune
	 *
	 * @param Kommune $kommune
	 * @param Arrangement $arrangement
	 * @param Type $type 
	 * @param String $navn
	 * @param Person $kontaktperson
	 *
	 * @return Innslag $innslag
	**/
	public static function create( Kommune $kommune, Arrangement $arrangement, Type $type, String $navn, Person $kontaktperson ) {
		// Valider at logger er på plass
		if( !Logger::ready() ) {
			throw new Exception(
                Logger::getError(),
                505001
            );
		}
		// Valider alle input-parametre
		try {
			Write::_validerCreate( $kommune, $arrangement, $type, $navn, $kontaktperson );
		} catch( Exception $e ) {
			throw new Exception(
				'Kunne ikke opprette innslag. '. $e->getMessage(),
				$e->getCode()
			);
		}
		
		## CREATE INNSLAG-SQL
		$band = new Insert('smartukm_band');
		$band->add('b_season', $arrangement->getSesong() );
		$band->add('b_status', 0); ## Hvorfor får innslaget b_status 8 her???
		$band->add('b_name', $navn);
		$band->add('b_kommune', $kommune->getId());
		$band->add('b_year', date('Y'));
		$band->add('b_subscr_time', time());
		$band->add('bt_id', $type->getId() );
        $band->add('b_contact', $kontaktperson->getId() );
        $band->add('b_home_pl', $arrangement->getId());

		if( 1 == $type->getId() ) {
			$band->add('b_kategori', $type->getKey() );
		}

		$band_id = $band->run();
		if( !$band_id ) {
			throw new Exception(
				"Klarte ikke å opprette et nytt innslag.",
				505008
			);
		}

		$tech = new Insert('smartukm_technical');
		$tech->add('b_id', $band_id );
		$tech->add('pl_id', $arrangement->getId() );
		
		$techres = $tech->run();
		if( !$techres ) {
			throw new Exception(
				"Klarte ikke å opprette tekniske behov-rad i tabellen.",
				505009
			);
        }

        $innslag = Innslag::getById( (Int) $band_id, true );
        $arrangement->getInnslag()->leggTil($innslag);

        $innslag = $arrangement->getInnslag()->get( (Int) $band_id, true );
        WriteArrangement::leggTilInnslag( $arrangement, $innslag, $arrangement );

        return $innslag;		
        
        // TODO: Oppdater statistikk
		#$innslag = new innslag( $b_id, false );
		#$innslag->statistikk_oppdater();
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
            $status = $innslag_save->getMangler()->getStatus();
            $innslag_save->setStatus($status);
            $smartukm_band->add('b_status', $status);
            Logger::log( 304, $innslag_save->getId(), $status);
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
	public static function savePersoner( Innslag $innslag_save ) {
		// Valider logger
		if( !Logger::ready() ) {
			throw new Exception(
				'Logger is missing or incorrect set up.',
				505022
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
				50523
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
	public static function saveProgram( Innslag $innslag_save ) {
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
				WriteHendelse::leggTil( $hendelse, $innslag_save );
			}
		}
		foreach( $innslag_db->getProgram()->getAllInkludertSkjulte() as $hendelse ) {
			if( !$innslag_save->getProgram()->har( $innslag_save ) ) {
				WriteHendelse::fjern( $hendelse, $innslag_save );
			}
		}
    }
    
    /**
	 * Meld av innslag
	 * 
     * Dette vil endre innslaget status, og effektivt melde det av.
     * @see WriteArrangement::fjernInnslag for å fjerne fra et arrangement uten å melde helt av
	 * 
	 * @param Write $innslag
	 * @return $innslag
	**/
    public static function meldAv( Innslag $innslag ) {
        // Er innslaget videresendt kan det ikke avmeldes
		if( $innslag->erVideresendt() ) {
			throw new Exception(
				'Du kan ikke melde av et innslag som er videresendt før du har fjernet videresendingen.',
				505024
			);
		}
		
		// Fjern fra alle forestillinger på mønstringen
		WriteHendelse::fjernInnslagFraAlleForestillingerIMonstring( $innslag );
    
		Logger::log( 311, $innslag->getId(), $innslag->getId() );
        $innslag->setStatus(77);
		Write::saveStatus( $innslag );

        // Sletter ikke relasjon fra `ukm_rel_arrangement_innslag`, da denne må være tilstede
        // ved en eventuell gjenopprettelse. Innslag med status 77 vil uansett ikke vises noe
        // sted.

        // Slett gammel relasjon til mønstringen
		$SQLdel = new Delete(
			'smartukm_rel_pl_b',
			[
				'b_id' => $innslag->getId(),
				'pl_id' => $innslag->getContext()->getMonstring()->getId(),
				'season' => $innslag->getContext()->getMonstring()->getSesong()
			]
		);
		$res = $SQLdel->run();

        // Avbryt samtykkeforespørsel
        static::requestSamtykkeCancel( $innslag );

		return $innslag;

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
				505028
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
				505014
			);
		}
		if( !is_numeric( $innslag->getId() ) || $innslag->getId() <= 0 ) {
			throw new Exception(
				'Innslag-objektet må ha en numerisk ID større enn null',
				505015
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
				505002
			);
		}
		if( !Kommune::validateClass($kommune) ) {
			throw new Exception(
				"Krever kommune-objekt, ikke ".get_class($kommune).".",
				505003
			);
		}
		if( !Type::validateClass($type) ) {
			throw new Exception(
				"Krever Type-objekt, ikke ". get_class($type) .".",
				505004
			);
		}
		if( !Person::validateClass($kontaktperson) ) {
			throw new Exception(
				"Krever skrivbar person, ikke ".get_class($kontaktperson),
				505005
			);	
		}
		if( empty($navn) ) {
			throw new Exception(
				"Må ha innslagsnavn.",
				505006
			);
		}

		if( !in_array($type->getKey(), array('scene', 'musikk', 'dans', 'teater', 'litteratur', 'film', 'video', 'utstilling', 'konferansier', 'nettredaksjon', 'arrangor','ressurs') ) ) {
			throw new Exception(
				"Kan ikke opprette ".$type->getKey()."-innslag.",
				505007
			);
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