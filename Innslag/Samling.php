<?php

namespace UKMNorge\Innslag;

use Exception;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Geografi\Fylke;
use UKMNorge\Geografi\Kommune;
use UKMNorge\Innslag\Typer\Type;
use UKMNorge\Innslag\Context\Context;
use UKMNorge\Innslag\Context\Innslag as InnslagContext;
use UKMNorge\Innslag\Context\Monstring;
use UKMNorge\Tid;

require_once('UKM/Autoloader.php');

class Samling {
    var $context = null;
	
	var $quickcount = null;
	var $innslag = null;
	var $innslag_ufullstendige = null;
	var $containerType = null;
	var $containerId = null;

	var $monstring_type = null; // Brukes av container_type 'monstring'
	var $monstring_sesong = null; // Brukes av container_type 'monstring'
	var $monstring_kommuner = null; // Brukes av container_type 'monstring'
	var $monstring_fylke = null; // Brukes av container_type 'monstring'
	
	var $videresendte = null;
	/**
	 * Class constructor
	 * OBS: monstring-collection krever kall til $this->setContainerDataMonstring()
	 *
	 * @param Context $context
	**/
	public function __construct( Context $context ) {
		$this->setContext( $context );
	}

	/**
	 * Hurtig-funksjon for å avgjøre om samlingen har innslag
	 *
	 * Kjører _load() i countOnly-modus, som returnerer SQL::numRows
	 *
	 * Funksjonen henter alle rader fra databasen med joins
	 * så krever litt, men likevel mye mindre enn å loope 
	 * alle innslag og opprette innslags-objekter
	 *
	 * Skal du bruke både harInnslag og loope innslagene
	 * bør du sette $forceLoad = true
	**/
	public function harInnslag( $forceLoad=false ) {
		// Hvis vi ikke har info, last inn quickCount såfremt $forceLoad ikke er true
		if( $this->innslag === null && $this->quickCount === null && $forceLoad === false) {
			$this->quickCount = $this->_load( true, true );
		}
		// Hvis innslag ikke er lastet og $forceLoad er true
		elseif( $this->innslag === null && $forceLoad ) {
			$this->_load();
		}
		
		// Hvis vi har quickCount, bruk denne
		if( $this->quickCount !== null ) {
			return $this->quickCount > 0;
		}
		// Hvis vi ikke har quickCount skal vi ha innslag/bruke denne
		return $this->getAntall() > 0;
	}

	/**
	 * Sjekker om collectionen har et innslag med en gitt ID. Fint for å verifisere forespørsler.
	 *
	 */
	public function har( $id ) {
		return $this->harInnslagMedId( $id );
	}
	public function harInnslagMedId($id) {
		if( is_object( $id ) && Innslag::validateClass( $id ) ) {
			$id = $id->getId();
		}
		if ( null == $this->innslag ) {
			$this->getAll();
		}

		foreach($this->innslag as $innslag) {
			if($id == $innslag->getId()){
				return true;
			}
		}
		return false;
	}

	/**
	 * Hent ut innslag med gitt id
	 *
	 * Hvis $mulig_ufullstendig == true, vil den også sjekke
	 * listen over ufullstendige innslag
	 *
	 * @param (int|innslag_v2) $id
	 * @param bool $mulig_ufullstendig=false
	 * @return Innslag 
	**/
	public function get( $id, $mulig_ufullstendig=false ) {
		if( is_object( $id ) && Innslag::validateClass( $id ) ) {
			$id = $id->getId();
		}
		if( !is_numeric( $id ) ) {
			throw new Exception('Kan ikke finne innslag uten ID', 10402);
		}

		foreach( $this->getAll() as $item ) {
			if( $id == $item->getId() ) {
				return $item;
			}
		}
		if( $mulig_ufullstendig ) {
			foreach( $this->getAllUfullstendige() as $item ) {
				if( $id == $item->getId() ) {
					return $item;
				}
			}
		}
		throw new Exception(
			'Fant ikke innslag '. $id .'.',
			2
		); // OBS: code brukes av harPerson
	}

	/**
	 * Hent alle fullstendig påmeldte innslag
	 *
	 * @return Innslag[] $innslag
	**/
	public function getAll() {
        // is_null fordi [] == null, og det er ikke alltid sant
        // (f.eks hvis vi fjernet siste element i lista)
		if ( is_null( $this->innslag ) ) {
            $this->_load();
		}
		return $this->innslag;
	}
	
	public function getVideresendte() {
		if( $this->getContext()->getType() != 'monstring' ) {
			throw new Exception(
				'Kan kun hente videresendte fra mønstring-context',
				10405
			);
		}

		if( null == $this->videresendte ) {
			$videresendTil = $this->getContext()->getMonstring()->getVideresendTil();
			
			// LOKAL TIL FYLKE
			if( $this->getContext()->getMonstring()->getType() == 'kommune' ) {		
				foreach( $this->getAll() as $innslag ) {
					if( !$innslag->erVideresendtTil( $videresendTil[ $innslag->getFylke()->getId() ] ) ) {
						continue;
					}
					$innslag->getContext()->setVideresendTil( $videresendTil[ $innslag->getFylke()->getId() ] );
					$this->videresendte[] = $innslag;
				}
			} else {
				foreach( $this->getAll() as $innslag ) {
					if( !$innslag->erVideresendtTil( $videresendTil ) ) {
						continue;
					}
					if( is_object( $videresendTil ) ) { 
						$innslag->getContext()->setVideresendTil( $videresendTil );
					} else {
						$innslag->getContext()->setVideresendTil( $videresendTil[ $innslag->getFylke()->getId() ] );
					}
					$this->videresendte[] = $innslag;
				}
			}
		}
		return $this->videresendte;
	}
	
	/**
	 * Hent antall innslag i collection
	 *
	 * @return int sizeof( $this->innslag )
	**/
	public function getAntall() {
		return sizeof( $this->getAll() );
    }
    

    public function getTid() {
        if( null == $this->varighet ) {
            $sekunder = 0;
            foreach( $this->getAll() as $innslag ) {
                if( $innslag->getType()->harTid() ) {
                    $sekunder += $innslag->getTid()->getSekunder();
                }
            }
            $this->varighet = new Tid( $sekunder );
        }
        return $this->varighet;
    }


	/********************************************************************************
	 *
	 *
	 * GET FILTERED SUBSETS FROM COLLECTION
	 *
	 *
	 ********************************************************************************/

	/**
	 * Hent alle ufullstendig påmeldte innslag
	 *
	 * @return array [innslag_v2]
	**/
	public function getAllUfullstendige() {
		if( null == $this->innslag_ufullstendige ) {
			$this->_load( false );
		}
		return $this->innslag_ufullstendige;
	}

	/**
	 * Hent alle innslag fra gitt kommune
	 *
	 * @param kommune $kommune
	 * @return array [innslag_v2]
	**/
	public function getAllByKommune( $kommune ) {
		return self::filterByGeografi( $kommune, $this->getAll() );
	}

	/**
	 * Hent alle innslag fra gitt fylke
	 *
	 * @param fylke $fylke
	 * @return array [innslag_v2]
	**/
	public function getAllByFylke( $fylke ) {
		return self::filterByGeografi( $fylke, $this->getAll() );
	}
	
	/**
	 * Hent alle innslag av gitt type
	 *
	 * @param Type $innslag_type
	 * @return array [innslag_v2]
	**/
	public function getAllByType( Type $innslag_type ) {
		return self::filterByType( $innslag_type, $this->getAll() );
	}
	
	/**
	 * Hent alle innslag av gitt status
	 *
	 * @param array [status]
	 * @return array [innslag_v2]
	**/
	public function getAllByStatus( $status_array ) {
		return self::filterByStatus( $status_array, $this->getAll() );
	}




	/********************************************************************************
	 *
	 *
	 * STATIC FILTER FUNCTIONS
	 *
	 *
	 ********************************************************************************/
	
	/**
	 * Filtrer gitte innslag for gitt status
	 *
	 * @param array [status]
	 * @param array [innslag_v2]
	 * @return array [innslag_v2]
	**/
	public static function filterByStatus( $status_array, $innslag_array ) {
		if( !is_array( $status_array ) ) {
			throw new Exception('innslag_collection::filterByStatus() krever at parameter 1 er array. Gitt '. get_class( $status_array ) );
		}

		$selected_innslag = [];
		foreach( $innslag_array as $innslag ) {
			if( in_array( $innslag->getStatus(), $status_array ) ) {
				$selected_innslag[] = $innslag;
			}
		}
		return $selected_innslag;
	}
	
	/**
	 * Filtrer gitte innslag for gitt type
	 *
	 * @param Type [type]
	 * @param Array [innslag_v2]
	 * @return Array [innslag_v2]
	**/
	public static function filterByType( Type $innslag_type, Array $innslag_array ) {
		if( !Type::validateClass($innslag_type ) ) {
			throw new Exception('innslag_collection::getAllByType() krever objekt av klassen innslag_type. Gitt '. get_class( $innslag_type ) );
		}
		
		$selected_innslag = [];
		foreach( $innslag_array as $innslag ) {
			if( $innslag->getType()->getId() == $innslag_type->getId() 
				&& $innslag->getType()->getKey() == $innslag_type->getKey() )
			{
				$selected_innslag[] = $innslag;
			}
		}
		return $selected_innslag;
	}

	/**
	 * Filtrer gitte innslag for gitt geografi
	 *
	 * @param (kommune|fylke) $geografi
	 * @param string $type
	 * @param array [innslag_v2]
	 * @return array [innslag_v2]
	**/
	public static function filterByGeografi( $geografi, $innslag_array ) {
		if( !Kommune::validateClass( $geografi ) && !Fylke::validateClass( $geografi ) ) {
			throw new Exception(
				'innslag_collection::filterByGeografi: '.
				'Type (param 2) må være kommune eller fylke'
			);
		}
		$selected_innslag = [];
		foreach( $innslag_array as $innslag ) {
			if( Kommune::validateClass( $geografi ) ) {
				if( $innslag->getKommune()->getId() == $geografi->getId() ) {
					$selected_innslag[] = $innslag;
				}
			} elseif( Fylke::validateClass( $geografi ) ) {
				if( $innslag->getFylke()->getId() == $geografi->getId() ) {
					$selected_innslag[] = $innslag;
				}
			}
		}
		return $selected_innslag;
	}



	/********************************************************************************
	 *
	 *
	 * MODIFISER COLLECTIONS
	 *
	 *
	 ********************************************************************************/
    /**
     * Legg til innslag
     *
     * @param Innslag $innslag
     * @return Bool true
     * @throws Exception
     */
    public function leggTil( Innslag $innslag ) {
		try {
			Write::validerInnslag( $innslag );
		} catch( Exception $e ) {
			throw new Exception(
				'Kunne ikke legge til innslag. '. $e->getMessage(),
				10401
			);
		}
		
		// Hvis innslaget allerede er lagt til kan vi skippe resten
		if( $this->har( $innslag ) ) {
			throw new Exception(
				'Innslaget er allerede i lagt til',
				10404
			);
		}
		
		// Gi innslaget riktig context (hent fra collection, samme som new innslag herfra)
		$innslag->setContext( $this->getContext() );
		
		// Legg til innslaget i collection
		if( $this->getContext()->getType() == 'monstring' ) {
			if( $innslag->getStatus() == 8 ) {
				$this->innslag[] = $innslag;
			} else {
				$this->innslag_ufullstendige[] = $innslag;
			}
		}

		return true;
	}

	/**
	 * Fjern et innslag fra collection
	 *
	 * @param Innslag $innslag
     * @return Bool true
     * @throws Exception
	**/
	public function fjern( Innslag $innslag ) {
		try {
			Write::validerInnslag( $innslag );
		} catch( Exception $e ) {
			throw new Exception(
				'Kunne ikke fjerne innslaget. '. $e->getMessage(),
				10403
			);
		}
		
		if( !$this->har( $innslag ) ) {
			return true;
		}
		
		// Fjern fra collection containers
		foreach( ['innslag', 'innslag_ufullstendige'] as $container ) {
			if( is_array( $this->$container ) ) {
				foreach( $this->$container as $pos => $search_innslag ) {
					if( $search_innslag->getId() == $innslag->getId() ) {
                        unset( $this->{$container}[ $pos ] );
                        if( is_null( $this->{$container} ) ) {
                            $this->{$container} = [];
                        }
					}
				}
			}
        }
        
		return true;
	}
	
	/**
	 * Last inn innslag til collection
	 *
	 * $pameldte avgjør om innslagene som lastes inn skal sorteres
	 *   inn er påmeldt eller delvis påmeldt
	 * $countOnly benyttes hvis du kun har bruk for å telle opp antall
	 *   innslag. 
	 *   OBS: skal samme script laste inn hele collection på et senere
	 *        tidspunkt bruker du countOnly=false (så sparer du en spørring)
	 *
	 * @param bool $pameldte (true)
	 * @param bool $countOnly (false)
	 * @return bool
	**/
	private function _load( $pameldte=true, $countOnly=false ) {
		$internal_var = $pameldte ? 'innslag' : 'innslag_ufullstendige';
		$this->$internal_var = array();
		
		$SQL = $this->_getQuery( $pameldte );
		$res = $SQL->run();
		#echo $SQL->debug();
		if( !$res ) {
			return false;
		}
		if( $countOnly ) {
			return Query::numRows( $res );
		}
		while( $row = Query::fetch( $res ) ) {
			$innslag = new Innslag( $row, true );
            $innslag->setContext( $this->getContext() );
            // Hvis samlingen er opprettet fra kontaktperson (som i UKMdelta),
            // har vi ikke tilgang på arrangementet, og dette må håndteres internt.
            // For å ikke kjøre alt for heavy objekter, prøver vi først uten listen
            // med kommuneID'er (november 2019) til første feil oppstår.
            // 
            // Fix 16.01: Forsøk å skippe innslag med kontakt-person context dersom arrangementet ikke finnes. Issue #315.
            if( $this->getContext()->getType() == 'kontaktperson' ) {
                try {
                    $innslag->getContext()->setMonstring(
                        new Monstring(
                            $innslag->getHomeId(),
                            'kommune',
                            $innslag->getSesong(),
                            $innslag->getFylke()->getId(),
                            null
                        )
                    );
                } catch( Exception $e ) {
                    // TODO: Error log - dette skjer kun dersom arrangementet ikke finnes lenger uten at innslaget er flyttet til en annen kommune. Dette BURDE vi få vite om.
                    continue;
                }
            }
			array_push( $this->$internal_var, $innslag);
		}
		return true;
	}

	/**
	 * Beregn hvilken SQL-spørring som kreves for å laste inn samlingen
	 *
	**/
	private function _getQuery( $pameldte ) {
        $operand = $pameldte ? '=' : '<';
		switch( $this->getContext()->getType() ) {
			case 'monstring':
				if( null == $this->getContext()->getMonstring()->getId() ) {
					throw new Exception('innslag: Krever MønstringID for å hente mønstringens innslag');
                }
                
                // 2020 regionreform gir ny beregning av relasjon til arrangement. Strengt tatt samme løsning
                // som smartukm_fylkestep/smartukm_rel_pl_b, men nå rendyrket i egen tabell for å sikre at ikke APIv1
                // tuller til relasjoner i ny sesong. Nå brukes relasjonstabellen for ALLE arrangementer,
                // uavhengig om innslaget er videresendt eller ikke.
                if( $this->getContext()->getMonstring()->getSesong() > 2019 ) {
                    return new Query(
                        Innslag::getLoadQuery("
                            `arrangement`.`fra_arrangement_navn` AS `fra_navn`,
                            `arrangement`.`fra_arrangement_id` AS `fra_id`"
                        )."
                        JOIN `ukm_rel_arrangement_innslag` AS `arrangement`
                            ON(`arrangement`.`innslag_id` = `smartukm_band`.`b_id`)
                        WHERE `arrangement`.`arrangement_id` = '#arrangement'
                            AND `b_status` ". $operand ." '8'
                        GROUP BY `smartukm_band`.`b_id`
                        ORDER BY `smartukm_band`.`b_name` ASC
                        ",
                        [
                            'arrangement' => $this->getContext()->getMonstring()->getId()
                        ]
                    );
                }
				// PRE 2011 DID NOT USE BAND SEASON FIELD
				if( 2011 >= $this->getContext()->getMonstring()->getSesong() ) {
					return new Query("SELECT `band`.*, 
										   `td`.`td_demand`,
										   `td`.`td_konferansier`
									FROM `smartukm_band` AS `band`
									JOIN `smartukm_rel_pl_b` AS `pl_b` ON (`pl_b`.`b_id` = `band`.`b_id`)
									LEFT JOIN `smartukm_technical` AS `td` ON (`td`.`b_id` = `band`.`b_id`) 
									WHERE `pl_b`.`pl_id` = '#pl_id'
										AND `b_status` ". $operand ." '8'
									GROUP BY `band`.`b_id`
									ORDER BY `bt_id` ASC,
											`band`.`b_name` ASC",
								array(	'season' => $this->getContext()->getMonstring()->getSesong(),
										'pl_id' => $this->getContext()->getMonstring()->getId(),
									)
								);
				}
				
				// POST 2011
				switch( $this->getContext()->getMonstring()->getType() ) {
					case 'land':
						return new Query("SELECT `band`.*, 
											   `td`.`td_demand`,
											   `td`.`td_konferansier`
										FROM `smartukm_fylkestep` AS `fs` 
										JOIN `smartukm_band` AS `band` ON (`band`.`b_id` = `fs`.`b_id`)
										LEFT JOIN `smartukm_technical` AS `td` ON (`td`.`b_id` = `band`.`b_id`) 
										WHERE   `band`.`b_season` = '#season'
											AND `b_status` ". $operand ." '8'
											AND `fs`.`pl_id` = '#pl_id'
										GROUP BY `band`.`b_id`
										ORDER BY `bt_id` ASC,
												`band`.`b_name` ASC",
									array(	'season' => $this->getContext()->getMonstring()->getSesong(),
											'pl_id' => $this->getContext()->getMonstring()->getId(),
										)
									);
					case 'fylke':
						return new Query("SELECT `band`.*, 
											   `td`.`td_demand`,
											   `td`.`td_konferansier`
										FROM `smartukm_fylkestep` AS `fs` 
										JOIN `smartukm_band` AS `band` ON (`band`.`b_id` = `fs`.`b_id`)
										LEFT JOIN `smartukm_technical` AS `td` ON (`td`.`b_id` = `band`.`b_id`) 
										JOIN `smartukm_kommune` AS `k` ON (`k`.`id`=`band`.`b_kommune`)
										WHERE   `b_season` = '#season'
											AND `b_status` ". $operand ." '8'
											AND `k`.`idfylke` = '#fylke_id'
										GROUP BY `band`.`b_id`
										ORDER BY `bt_id` ASC,
												 `band`.`b_name` ASC",
									array(	'season' => $this->getContext()->getMonstring()->getSesong(),
											'fylke_id' => $this->getContext()->getMonstring()->getFylke(),
										)
									);
					default:			
						return new Query("SELECT `band`.*, 
											   `td`.`td_demand`,
											   `td`.`td_konferansier`
										FROM `smartukm_band` AS `band`
										LEFT JOIN `smartukm_rel_pl_b` AS `pl_b` ON (`pl_b`.`b_id` = `band`.`b_id`)
										LEFT JOIN `smartukm_technical` AS `td` ON (`td`.`b_id` = `band`.`b_id`) 
										WHERE   `b_season` = '#season'
											AND `b_status` ". $operand ." '8'
											AND `b_kommune` IN ('". implode("','", $this->getContext()->getMonstring()->getKommuner() ) ."')
										GROUP BY `band`.`b_id`
										ORDER BY `bt_id` ASC,
												 `band`.`b_name` ASC",
									array(	'season' => $this->getContext()->getMonstring()->getSesong(),
											# IDs inputted directly to avoid escaping
										)
									);
				}
			case 'forestilling':
				if( null == $this->getContext()->getForestilling()->getId() ) {
					throw new Exception('INNSLAG_COLLECTION: Krever forestilling-ID for å hente forestillingens innslag', 2);
				}
				return new Query(Innslag::getLoadQuery()."
								JOIN `smartukm_rel_b_c` AS `rel`
									ON `rel`.`b_id` = `smartukm_band`.`b_id`
								WHERE `rel`.`c_id` = '#c_id'
								AND `smartukm_band`.`b_status` = '8'
								ORDER BY `order` ASC",
								array( 'c_id' => $this->getContext()->getForestilling()->getId() ) );
            case 'kontaktperson':
                return new Query(
                        Innslag::getLoadQuery()."
                        WHERE `b_contact` = '#kontakt'
                        AND `b_season` = '#sesong'
                        AND `b_status` <= 8
                        ",
                        [
                            'kontakt' => $this->getContext()->getKontaktperson()->getId(),
                            'sesong' => $this->getContext()->getSesong()
                        ]
                );
            case 'deltauser':
                $qry = new Query(
                    Innslag::getLoadQuery()."
                    WHERE `b_password` = 'delta_#user_id' 
                    AND `b_season` = '#sesong'
                    AND `b_status` <= 8",
                    [
                        'user_id' => $this->getContext()->getDeltaUserId(),
                        'sesong' => $this->getContext()->getSesong()
                    ]
                );
                throw new Exception("Loading deltausers via b_password is deprecated! Dette skal ikke skje og er en systemfeil. Kontakt UKM Support for hjelp.");
                return $qry;
            case 'sesong':
                return new Query(
                    "SELECT `band`.*, 
                        `td`.`td_demand`,
                        `td`.`td_konferansier`
                    FROM `smartukm_band` AS `band`    
                    LEFT JOIN `smartukm_rel_pl_b` 
                        AS `pl_b` 
                        ON (`pl_b`.`b_id` = `band`.`b_id`)
                    LEFT JOIN `smartukm_technical` 
                        AS `td` 
                        ON (`td`.`b_id` = `band`.`b_id`) 
                    WHERE `b_season` = '#season'
                    AND `b_status` $operand '8'
                    GROUP BY 
                        `band`.`b_id`
                    ORDER BY 
                        `bt_id` ASC,
                        `band`.`b_name` ASC",
                    array(
                        'season' => $this->getContext()->getSesong(),
                    )
                );
			default:
				throw new Exception('innslag: Har ikke støtte for '. $this->getContext()->getType() .'-collection (#2)');
		}
	}
	
	/**
     * Sett hvilken kontekst innslag-samlingen befinner seg i (kommer fra)
     *
     * @param Context $context
     * @return self
     */
	public function setContext( $context ) {
		$this->context = $context;
		return $this;
    }
    /**
     * Hent hvilken kontekst innslag-samlingen befinner seg i (kommer fra)
     *
     * @return Context
     */
	public function getContext() {
		return $this->context;
	}
}