<?php
require_once('UKM/monstring.class.php');
require_once('UKM/logger.class.php');


class write_monstring {
	public static function create( $type, $sesong, $navn, $datoer, $geografi ) {
		// Oppdater loggeren til å bruke riktig PL_ID
		UKMlogger::setPlId( 0 );
		
		/**
		 *
		 * SJEKK INPUT-DATA
		 *
		**/
		if( !in_array( $type, array('kommune', 'fylke', 'land') ) ) {
			throw new Exception('Monstring_v2::create: Ukjent type mønstring "'. $type .'"');	
		}
		if( !is_int( $sesong ) ) {
			throw new Exception('Monstring_v2::create: Sesong må være integer');
		}
		if( !is_object( $datoer ) || get_class( $datoer ) !== 'stdClass' ) {
			throw new Exception('Monstring_v2::create: Datoer må være objekt stdClass');
		}
		if( !isset( $datoer->frist ) ) {
			throw new Exception('Monstring_v2::create: Datoobjektet må ha frist');
		}
		if( get_class( $datoer->frist ) !== 'DateTime' ) {
			throw new Exception(
						'Monstring_v2::create: Datoer->frist må være DateTime, ikke '. 
						(is_object( $datoer->frist ) ? get_class( $datoer->frist ) : 
							is_array( $datoer->frist ) ? 'array' : 
								is_integer( $datoer->frist ) ? 'integer' :
									is_string( $datoer->frist ) ? 'string' : 'ukjent datatype'
						)
					);
		}
		switch( $type ) {
			case 'kommune':
				if( !is_array( $geografi ) ) {
					throw new Exception(
								'Monstring_V2::create: Geografiobjekt må være array kommuner, '. 
									(is_object( $datoer->frist ) ? get_class( $datoer->frist ) : 
										is_array( $datoer->frist ) ? 'array' : 
											is_integer( $datoer->frist ) ? 'integer' :
												is_string( $datoer->frist ) ? 'string' : 'ukjent datatype'
									)
							);
				}
				foreach( $geografi as $kommune ) {
					if( !is_object( $kommune ) || get_class( $kommune ) !== 'kommune' ) {
						throw new Exception('Monstring_v2::create: Alle Geografi->kommuneobjekt må være av typen UKMApi::kommune');
					}
				}
				break;
			case 'fylke':
				if( !is_object( $geografi ) || get_class( $geografi ) !== 'fylke' ) {
					throw new Exception('Monstring_v2::create: Geografiobjekt må være av typen UKMApi::fylke');
				}
				break;
			case 'land':
				break;
		}

		/**
		 *
		 * SETT INN RAD I smartukm_place
		 *
		**/		
		$place = new SQLins('smartukm_place');
		$place->add('pl_start', 0);
		$place->add('pl_stop', 0);
		$place->add('pl_public', 0);
		$place->add('pl_missing', 0);
		$place->add('pl_contact', 0);
		$place->add('pl_form', 0);
		
		switch( $type ) {
			case 'kommune':
				$place->add('pl_fylke', 0);
				$place->add('pl_kommune', time());
				$place->add('pl_deadline', $datoer->frist->getTimestamp());
				$place->add('pl_deadline2', $datoer->frist->getTimestamp());
				break;
			case 'fylke':
				$place->add('pl_fylke', $geografi->getId());
				$place->add('pl_kommune', 0);
				$place->add('pl_deadline', $datoer->frist->getTimestamp());
				$place->add('pl_deadline2', $datoer->frist->getTimestamp());
				break;
			case 'land':
				$place->add('pl_fylke', 123456789);
				$place->add('pl_kommune', 123456789);
				$place->add('pl_deadline', $datoer->frist->getTimestamp());
				$place->add('pl_deadline2', $datoer->frist->getTimestamp());
				break;
		}

		$place->add('pl_name', ltrim( rtrim( $navn ) ));
		$place->add('season', $sesong);
		
		$pl_id = $place->run();
		
		$monstring = new monstring_v2( $pl_id );

		// Oppdater loggeren til å bruke riktig PL_ID
		UKMlogger::setPlId( $monstring->getId() );

		foreach( $geografi as $kommune ) {
			$monstring->getKommuner()->leggTil( $kommune );
		}
		
		$monstring->setPath( 
			self::generatePath(
				$type,
				$geografi,
				$sesong
			)
		);

		self::save( $monstring );
		
		return $monstring;
	}
	
	
	
	public static function save( $monstring_save ) {
		// DB-OBJEKT
		$monstring_db = new monstring_v2( $monstring_save->getId() );
		
		// TABELLER SOM KAN OPPDATERES
		$smartukm_place = new SQLins(
			'smartukm_place', 
			[
				'pl_id' => $monstring_save->getId()
			]
		);
		
		// VERDIER SOM KAN OPPDATERES
		$properties = [
			'Navn' 			=> ['smartukm_place', 'pl_name', 100],
			'Path' 			=> ['smartukm_place', 'pl_link', 110],
			'Uregistrerte'	=> ['smartukm_place', 'pl_missing', 108],
			'Publikum'		=> ['smartukm_place', 'pl_public', 109],
			'Sted'			=> ['smartukm_place', 'pl_place', 101],
			'Start'			=> ['smartukm_place', 'pl_start', 102],
			'Stop'			=> ['smartukm_place', 'pl_stop', 103],
			'Frist1'		=> ['smartukm_place', 'pl_deadline', 106],
			'Frist2'		=> ['smartukm_place', 'pl_deadline2', 107] 
		];
		// VERDIER SOM KUN KAN OPPDATERES HVIS FYLKE
		if( $monstring_save->getType() == 'fylke' ) {
			$properties['Skjema'] = ['smartukm_place', 'pl_form', 113];
		}

		// LOOP ALLE VERDIER, OG EVT LEGG TIL I SQL
		foreach( $properties as $functionName => $logValues ) {
			$function = 'get'.$functionName;
			$table = $logValues[0];
			$field = $logValues[1];
			$action = $logValues[2];
			$sql = $$table;
			
			if( $monstring_db->$function() != $monstring_save->$function() ) {
				# Mellomlagre verdi som skal settes
				$value = $monstring_save->$function();

				if( is_object( $value ) && get_class( $value ) == 'DateTime' ) {
					$value = $value->getTimestamp(); // Fordi databasen lagrer datoer som int
				}
				# Legg til i SQL
				$sql->add( $field, $value ); 	// SQL satt dynamisk i foreach til $$table
				# Logg (eller dø) før vi kjører run
				UKMlogger::log( $action, $monstring_save->getId(), $value );
			}
		}
		
		$res = true; // Fordi smartukm_place->run() vil overskrive hvis det oppstår feil
		if( $smartukm_place->hasChanges() ) {
			#echo $smartukm_place->debug();
			$res = $smartukm_place->run();
		}
		if( !$res ) {
            # echo $smartukm_place->getError();
			throw new Exception('Kunne ikke lagre mønstring skikkelig, da lagring av detaljer feilet.');
		}


		// Hvis lokalmønstring, sjekk og lagre kommunesammensetning
		if( $monstring_save->getType() == 'kommune') {
			foreach( $monstring_save->getKommuner()->getAll() as $kommune ) {
				if( !$monstring_db->getKommuner()->har( $kommune ) ) {
					self::_leggTilKommune( $monstring_save, $kommune ); 
				}
			}
			foreach( $monstring_db->getKommuner()->getAll() as $kommune ) {
				if( !$monstring_save->getKommuner()->har( $kommune ) ) {
					self::_fjernKommune( $monstring_save, $kommune );
				}
			}
		}

		// Sjekk kontaktpersoner og lagre endringer
		foreach( $monstring_save->getKontaktpersoner()->getAll() as $kontakt ) {
			if( !$monstring_db->getKontaktpersoner()->har( $kontakt ) ) {
				self::_leggTilKontaktperson( $monstring_save, $kontakt ); 
			}
		}
		foreach( $monstring_db->getKontaktpersoner()->getAll() as $kontakt ) {
			if( !$monstring_save->getKontaktpersoner()->har( $kontakt ) ) {
				self::_fjernKontaktperson( $monstring_save, $kontakt );
			}
		}


		// Sjekk tillatte typer innslag og lagre endringer
		foreach( $monstring_save->getInnslagtyper()->getAll() as $innslag_type ) {
			if( !$monstring_db->getInnslagtyper()->har( $innslag_type ) ) {
				self::_leggTilInnslagtype( $monstring_save, $innslag_type ); 
			}
		}
		foreach( $monstring_db->getInnslagtyper()->getAll() as $innslag_type ) {
			if( !$monstring_save->getInnslagtyper()->har( $innslag_type ) ) {
				self::_fjernInnslagtype( $monstring_save, $innslag_type );
			}
		}

		return $res;
	}


	/**
	 * Faktisk legg til en kontaktperson til mønstringen (db-modifier)
	 * 
	 * Sjekker at databaseraden ikke allerede eksisterer, og
	 * setter inn ny rad ved behov
	 *
	 * @param monstring_v2 $monstring
	 * @param kontakt_v2 $kontakt
	 * @return bool $sucess
	**/
	public static function _leggTilKontaktperson( $monstring_save, $kontakt ) {
		try {
			self::controlMonstring( $monstring_save );
			self::controlKontaktperson( $kontakt );
		} catch( Exception $e ) {
			throw new Exception('Kan ikke legge til kontaktperson da '. $e->getMessage() );
		}

		$test = new SQL("
			SELECT `ab_id`
			FROM `smartukm_rel_pl_ab`
			WHERE `pl_id` = '#pl_id'
			AND `ab_id` = '#ab_id'",
			[
				'pl_id' => $monstring_save->getId(),
				'ab_id' => $kontakt->getId()
			]
		);
		$testRes = $test->run('field', 'ab_id');
		if( is_numeric( $testRes ) && $testRes > 0 ) {
			return true;
		}

		$rel_pl_ab = new SQLins('smartukm_rel_pl_ab');
		$rel_pl_ab->add('pl_id', $monstring_save->getId());
		$rel_pl_ab->add('ab_id', $kontakt->getId());
		$rel_pl_ab->add('order', time());
		$res = $rel_pl_ab->run();
		
		if( !$res ) {
			return false;
		}

		UKMlogger::log( 
			111, 
			$monstring_save->getId(), 
			$kontakt->getId() .': '. $kontakt->getNavn()
		);
		return true;
	}
	

	/**
	 * Faktisk fjern en kontaktperson fra mønstringen (db-modifier)
	 * 
	 * Sletter databaseraden hvis den eksisterer
	 *
	 * @param monstring_v2 $monstring
	 * @param kontakt_v2 $kontakt
	 * @return void
	**/
	public static function _fjernKontaktperson( $monstring_save, $kontakt ) {
		try {
			self::controlMonstring( $monstring_save );
			self::controlKontaktperson( $kontakt );
		} catch( Exception $e ) {
			throw new Exception('Kan ikke fjerne kontaktperson da '. $e->getMessage() );
		}

		$rel_pl_ab = new SQLdel(
			'smartukm_rel_pl_ab',
			[
				'pl_id' => $monstring_save->getId(),
				'ab_id' => $kontakt->getId()
			]
		);
		$res = $rel_pl_ab->run();
		
		if( !$res ) {
			return false;
		}

		UKMlogger::log( 
			116, 
			$monstring_save->getId(), 
			$kontakt->getId() .': '. $kontakt->getNavn()
		);
		
		return true;
	}
	
	/**
	 * Faktisk legg til en kommune i mønstringen (db-modifier)
	 * 
	 * Sjekker at databaseraden ikke allerede eksisterer, og
	 * setter inn ny rad ved behov
	 * 
	 * @param monstring_v2 $monstring
	 * @param kommune $kommune
	 * 
	 * @return bool $result
	 */
	private static function _leggTilKommune( $monstring_save, $kommune ) {
		try {
			self::controlMonstring( $monstring_save );
			if( $monstring_save->getType() != 'kommune' ) {
				throw new Exception('mønstring ikke er lokal-mønstring');
			}	
			self::controlKommune( $kommune );
		} catch( Exception $e ) {
			throw new Exception('Kan ikke legge til kommune da '. $e->getMessage() );
		}

		$test = new SQL("
			SELECT `k_id`
			FROM `smartukm_rel_pl_k`
			WHERE `pl_id` = '#pl_id'
			AND `k_id` = '#k_id'",
			[
				'pl_id' => $monstring_save->getId(),
				'k_id' => $kommune->getId()
			]
		);
		$resTest = $test->run('field', 'k_id');
		if( is_numeric( $resTest ) && $resTest == $kommune->getId() ) {
			return true;
		}
				
		$rel_pl_k = new SQLins('smartukm_rel_pl_k');
		$rel_pl_k->add('pl_id', $monstring_save->getId());
		$rel_pl_k->add('season', $monstring_save->getSesong());
		$rel_pl_k->add('k_id', $kommune->getId());
		$res = $rel_pl_k->run();

		if( !$res ) {
			return false;
		}

		UKMlogger::log( 
			112, 
			$monstring_save->getId(), 
			$kommune->getId() .': '. $kommune->getNavn()
		);
		return true;
	}

	/**
	 * Faktisk fjern en kommune fra mønstringen (db-modifier)
	 * 
	 * Sletter databaseraden hvis den finnes
	 * 
	 * @param monstring_v2 $monstring
	 * @param kommune $kommune
	 * @return void
	 */
	private static function _fjernKommune( $monstring_save, $kommune ) {
		try {
			self::controlMonstring( $monstring_save );
			if( $monstring_save->getType() != 'kommune' ) {
				throw new Exception('mønstring ikke er lokal-mønstring');
			}	
			self::controlKommune( $kommune );
		} catch( Exception $e ) {
			throw new Exception('Kan ikke fjerne kommune da '. $e->getMessage() );
		}

		// Hvis mønstringen på dette tidspunktet
		// fortsattt har kommunen i kommune-collection
		// er det på høy tid å fjerne den.
		// (avlys kan finne på å gjøre dette tror Marius (26.10.2018))
		if( $monstring_save->getKommuner()->har( $kommune ) ) {
			$monstring_save->getKommuner()->fjern( $kommune );
		}

		$rel_pl_k = new SQLdel(
			'smartukm_rel_pl_k',
			[
				'pl_id' => $monstring_save->getId(),
				'k_id' => $kommune->getId(),
				'season' => $monstring_save->getSesong(),
			]
		);
		$res = $rel_pl_k->run();

		UKMlogger::log( 
			114, 
			$monstring_save->getId(), 
			$kommune->getId() .': '. $kommune->getNavn()
		);
	}

	/**
	 * avlys mønstring
	 * 
	 * !! OBS, OBS !!
	 * Denne skal kun benyttes fra UKM Norge-admin,
	 * da bloggen må endres for at alt skal fungere som ønsket.
	 * !! OBS, OBS !! 
	 *
	 * @param monstring_v2 $monstring
	**/
	public static function avlys( $monstring ) {
		if( $monstring->getType() != 'kommune' ) {
			throw new Exception('Mønstring: kun lokalmønstringer kan avlyses');
		}
		if( !$monstring->erSingelmonstring() ) {
			throw new Exception('Mønstring: kun enkeltmønstringer kan avlyses');
		}
		if( !is_numeric( $monstring->getId() ) ) {
			throw new Exception('Mønstring: kan ikke fjerne kommune da mønstring ikke har numerisk ID');
		}
		if( !is_numeric( $monstring->getSesong() ) ) {
			throw new Exception('Mønstring: kan ikke fjerne kommune da sesong ikke har numerisk verdi');
		}
		
		self::_fjernKommune( $monstring, $monstring->getKommune() );
		
		// Fjern databasefelter som identifiserer mønstringen ("soft delete")
		$monstringsnavn = $monstring->getNavn();
		$monstring->setNavn( 'SLETTET: '. $monstring->getNavn() );
		$monstring->setPath( NULL );
		self::save( $monstring );
		
		UKMlogger::log(
			115,
			$monstring->getId(),
			$monstringsnavn
		);
		
		return $monstring;
	}
	
	public static function generatePath( $type, $geografi, $sesong, $skipCheck=false ) {
		switch( $type ) {
			case 'kommune':
				// Legg til kommunerelasjoner og bygg link
				$kommuner = [];
				foreach( $geografi as $kommune ) {
					$kommuner[] = $kommune->getURLsafe();
				}
				sort( $kommuner );
				$link = implode('-', $kommuner );
				
				if( $skipCheck ) {
					return $link;
				}
				// Sjekk om linken er i bruk for gitt sesong
				$linkCheck = new SQL(
									"SELECT `pl_id`
									 FROM `smartukm_place`
									 WHERE `pl_link` = '#link'
									 AND `season` = '#season'",
									array(
										'link'=> $link, 
										'season'=> $sesong,
										)
									);
				$linkExists = $linkCheck->run('field', 'pl_id');
				if( false !== $linkExists && is_numeric( $linkExists ) ) {
					$fylke = $kommune->getFylke(); // Bruker siste kommune fra foreach
					$link = $fylke->getURLsafe() .'-'. $link;
				}
				break;
			case 'fylke':
				$link = $geografi->getURLsafe();
				break;
			case 'land':
				$link = 'festivalen';
				break;
			case 'default':
				throw new Exception('WRITE_MONSTRING::createLink() kan ikke genere link for ukjent type mønstring!');
		}
		return $link;
	}


	public static function controlMonstring( $monstring ) {
		if( get_class( $monstring ) !== 'monstring_v2' ) {
			throw new Exception('mønstring ikke er objekt av typen monstring_v2');
		}
		if( !is_numeric( $monstring->getId() ) ) {
			throw new Exception('mønstring ikke har numerisk ID');
		}
		if( !is_numeric( $monstring->getSesong() ) ) {
			throw new Exception('mønstringen ikke har numerisk sesong-verdi');
		}
	}

	public static function controlKontaktperson( $kontakt ) {
		if( get_class( $kontakt ) !== 'kontakt_v2' ) {
			throw new Exception('kontakt ikke er objekt av typen kontakt_v2');
		}
		if( !is_numeric( $kontakt->getId() ) && $kontakt->getId() > 0 ) {
			throw new Exception('kontakt ikke har numerisk id');
		}
	}

	public static function controlKommune( $kommune ) {
		if( get_class( $kommune ) !== 'kommune' ) {
			throw new Exception('kommune ikke er objekt av typen kommune');
		}
		if( !is_numeric( $kommune->getId() ) && $kommune->getId() > 0 ) {
			throw new Exception('kommune ikke har numerisk id');
		}
	}




	
	/**
	 * DEPRECATED: addKommune
	 * Endre kommuner direkte på mønstringen, og kall write_monstring::save( $monstring )
 	 * @param kommune $kommune
 	**/
 	public function leggTilKommune( $kommune ) {
		self::addKommune( $kommune );
	}
   public function addKommune( $kommune ) {
	   die('DEPRECATED: Endre kommuner direkte på mønstringen, og kall  write_monstring::save( $monstring )');
   }
	/**
	 * DEPRECATED: fjernKommune
	 * Endre kommuner direkte på mønstringen, og kall write_monstring::save( $monstring )
	 *
	 * @param kommune $kommune
	**/
	public function fjernKommune( $kommune ) {
		self::addKommune( $kommune );
	}
	/**
	 * DEPRECATED: addKontaktperson
	 * Endre kontakter direkte på mønstringen, og kall write_monstring::save( $monstring )
	 * 
	 * @param kontaktperson $kontakt
	**/
	public function addKontaktperson( $kontakt ) {
		die('DEPRECATED: Endre kontaktpersoner direkte på mønstringen, og kall write_monstring::save( $monstring )');
	}

	/**
	 * Faktisk legg til en ny type innslag til mønstringen (db-modifier)
	 * 
	 * Sjekker at databaseraden ikke allerede eksisterer, og
	 * setter inn ny rad ved behov
	 *
	 * @param monstring_v2 $monstring
	 * @param innslag_type $innslag_type
	 * @return bool $sucess
	**/
	public static function _leggTilInnslagtype( $monstring_save, $innslag_type ) {
		try {
			self::controlMonstring( $monstring_save );
		} catch( Exception $e ) {
			throw new Exception('Kan ikke legge til innslagstype da '. $e->getMessage() );
		}

		$test = new SQL("
			SELECT `pl_bt_id`
			FROM `smartukm_rel_pl_bt`
			WHERE `pl_id` = '#pl_id'
			AND `bt_id` = '#bt_id'",
			[
				'pl_id' => $monstring_save->getId(),
				'bt_id' => $innslag_type->getId()
			]
		);
		$testRes = $test->run('field', 'pl_bt_id');
		if( is_numeric( $testRes ) && $testRes > 0 ) {
			return true;
		}

		$insert = new SQLins('smartukm_rel_pl_bt');
		$insert->add('pl_id', $monstring_save->getId());
		$insert->add('bt_id', $innslag_type->getId());
		$res = $insert->run();
		
		if( !$res ) {
			return false;
		}

		UKMlogger::log( 
			117, 
			$monstring_save->getId(), 
			$innslag_type->getId()
		);
		return true;
	}

	/**
	 * Faktisk legg til en ny type innslag til mønstringen (db-modifier)
	 * 
	 * Sjekker at databaseraden ikke allerede eksisterer, og
	 * setter inn ny rad ved behov
	 *
	 * @param monstring_v2 $monstring
	 * @param innslag_type $innslag_type
	 * @return bool $sucess
	**/
	public static function _fjernInnslagtype( $monstring_save, $innslag_type ) {
		try {
			self::controlMonstring( $monstring_save );
		} catch( Exception $e ) {
			throw new Exception('Kan ikke fjerne innslagstype da '. $e->getMessage() );
		}


		$delete = new SQLdel(
			'smartukm_rel_pl_bt',
			[
				'pl_id' => $monstring_save->getId(),
				'bt_id' => $innslag_type->getId()
			]
		);
		$res = $delete->run();

		if( in_array( $innslag_type->getId(), [8,9] ) ) {
			$delete2 = new SQLdel(
				'smartukm_rel_pl_bt',
				[
					'pl_id' => $monstring_save->getId(),
					'bt_id' => $innslag_type->getId() == 8 ? 9 : 8
				]
			);
			$res = $delete2->run();
		}
			
		if( !$res ) {
			return false;
		}

		UKMlogger::log( 
			118, 
			$monstring_save->getId(), 
			$innslag_type->getId()
		);
		return true;
	}
}