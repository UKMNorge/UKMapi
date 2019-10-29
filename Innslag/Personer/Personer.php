<?php

namespace UKMNorge\Innslag\Personer;

use Exception;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Innslag\Context\Context;
use UKMNorge\Innslag\Type;

require_once('UKM/Autoloader.php');

class Personer {
    var $context = null;
	var $innslag_id = null;
	var $innslag_type = null;
	
	var $personer = null;
	var $personer_videresendt = null;
	var $personer_ikke_videresendt = null;
	var $debug = false;
	
	public function __construct( Int $innslag_id, Type $innslag_type, Context $context ) {
		$this->innslag_id = $innslag_id;
        $this->innslag_type = $innslag_type;
		$this->context = $context;

		$this->_load();
	}

	/**
	 * getAll
	 * Returner alle personer i innslaget
	 *
	 * @return Array<Person> $personer
	**/
	public function getAll() {
		return $this->personer;
    }
    
    /**
     * Hent ID-liste for alle personer i getAll()
     *
     * @return Array<Int> 
     */
    public function getAllIds() {
        return array_keys( $this->getAll() );
    }
	
	/**
	 * getSingle
	 * Hent én enkelt person fra innslaget. 
	 * Er beregnet for tittelløse innslag, som aldri har mer enn én person
	 *
	 * @return Person $person
     * @throws Exception hvis innslaget har mer enn én person
	**/
	public function getSingle() {
		if( 1 < $this->getAntall() ) {
			throw new Exception( 'PERSON_V2: getSingle() er kun ment for bruk med tittelløse innslag som har ett personobjekt. '
								.'Dette innslaget har '. $this->getAntall() .' personer');	
		}
		$all = $this->getAll();
		return end( $all ); // and only...
	}
	
	/**
	 * getAllVideresendt
	 * Hent alle personer i innslaget videresendt til GITT mønstring
	 *
	 * @param Int $pl_id
	 * @return bool
	**/
	public function getAllVideresendt( $pl_id=false ) {
		$pl_id = $this->_autoloadPlidParameter( $pl_id );
		if( null == $this->personer_videresendt ) {
			$this->personer_videresendt = array();
			foreach( $this->getAll() as $person ) {
				if( $person->erVideresendt( $pl_id ) ) {
					$this->personer_videresendt[] = $person;
				}
			}
		}
		return $this->personer_videresendt;
	}

	/**
	 * getAllIkkeVideresendt
	 * Hent alle personer i innslaget videresendt til GITT mønstring
	 *
	 * @param int $pl_id
	 * @return bool
	**/
	public function getAllIkkeVideresendt( $pl_id=false ) {
		if( $pl_id == false ) {
			$pl_id = $this->getContext()->getMonstring()->getId();
		} elseif( Arrangement::validateClass( $pl_id ) ) {
			$pl_id = $pl_id->getId();
		}
		if( null == $this->personer_ikke_videresendt ) {
			$this->personer_ikke_videresendt = array();
			foreach( $this->getAll() as $person ) {
				if( !$person->erVideresendt( $pl_id ) ) {
					$this->personer_ikke_videresendt[] = $person;
				}
			}
		}
		return $this->personer_ikke_videresendt;
	}

	/**
	 * getAntall
	 * Hvor mange personer er det i innslaget?
	 * Tar ikke høyde for filtrering på videresendte
	 *
	 * @return int sizeof( $this->getAll() )
	**/
	public function getAntall() {
		return sizeof( $this->getAll() );
	}
	
	public function getAntallVideresendt( $pl_id=false ) {
		return sizeof( $this->getAllVideresendt( $pl_id ) );
	}

	public function getAntallIkkeVideresendt( $pl_id=false ) {
		return sizeof( $this->getAllIkkeVideresendt( $pl_id ) );
	}
	
	/**
	 * get
	 *
	 * Finn en person med gitt ID
	 *
	 * @alias getById
	 *
	 * @param integer id
	 * @return person
	**/
	public function get( $id ) {
		if( Person::validateClass( $id ) ) {
			$id = $id->getId();
		}
		
		if( !is_numeric( $id ) ) {
			throw new Exception('Kan ikke finne person uten ID', 1);
		}
		foreach( $this->getAll() as $person ) {
			if( $person->getId() == $id ) {
				return $person;
			}
		}
		throw new Exception('PERSONER_COLLECTION: Kunne ikke finne person '. $id .' i innslag '. $this->getInnslagId(), 2); // OBS: code brukes av harPerson
	}
	public function getById( $id ) {
		return $this->get( $id );
	}

	/**
	 * harPerson
	 * Er personen med i innslaget. OBS: Tar ikke høyde for videresending!
	 *
	 * @param object person
	 * @return boolean
	**/
	public function harPerson( $har_person ) {
		try {
			$this->getById( $har_person );
			return true;
		} catch( Exception $e ) {
			if( $e->getCode() == 2 ) {
				return false;
			}
			throw $e;
		}
	}
	public function har( $person ) {
		return $this->harPerson( $person );
	}
	
	/**
	 * harVideresendtPerson
	 * Er personen med i innslaget og videresendt til gitt mønstring?
	 *
	 * @param objekt person
	 * @param int pl_id
	 *
	**/
	public function harVideresendtPerson( $har_person, $pl_id=false ) {
		$pl_id = $this->_autoloadPlidParameter( $pl_id );
		foreach( $this->getAll() as $person ) {
			if( $person->getId() == $har_person->getId() && $person->erVideresendt( $pl_id ) ) {
				return true;
			}
		}
	}



	/********************************************************************************
	 *
	 *
	 * MODIFISER COLLECTIONS
	 *
	 *
	 ********************************************************************************/
	public function leggTil( $person ) {
		try {
			Person::validateClass( $person );
		} catch( Exception $e ) {
			throw new Exception(
				'Kunne ikke legge til person. '. $e->getMessage(),
				106001
			);
		}
		
		// Hvis personen allerede er lagt til kan vi skippe resten
		if( $this->harPerson( $person ) ) {
			return true;
		}
		
		// Gi personen riktig context (hent fra collection, samme som new person herfra)
		$person->setContext( $this->getContextInnslag() );
		
		// Legg til at personen skal være videresendt
		if( $person->getContext()->getMonstring()->getType() != 'kommune' ) {
			$status_videresendt = $person->getVideresendtTil(); // henter et array av mønstringer personen er videresendt til
			$status_videresendt[] = $person->getContext()->getMonstring()->getid(); // legger til denne mønstringer
			$person->setVideresendtTil( $status_videresendt ); // "lagrer"
		}
		
		// Legg til personen i collection
		$this->personer[ $person->getId() ] = $person;

		return true;
	}

	public function fjern( $person ) {
		try {
			Write::validerPerson( $person );
		} catch( Exception $e ) {
			throw new Exception(
				'Kunne ikke fjerne person. '. $e->getMessage(),
				106002
			);
		}
		
		if( !$this->harPerson( $person ) ) {
			return true;
		}
		
		unset( $this->personer[ $person->getId() ] );

		return true;
	}



	/********************************************************************************
	 *
	 *
	 * INTERNE HJELPE-FUNKSJONER
	 *
	 *
	 ********************************************************************************/
	/**
	 * Last inn alle personer i samlingen
	**/
	private function _load() {
		$this->personer = array();
        
        // 2020 regionreform gir ny beregning av personer. Strengt tatt samme løsning
        // som smartukm_fylkestep, men nå rendyrket i egen tabell for å sikre at ikke APIv1
        // tuller til relasjoner i ny sesong. Nå brukes relasjonstabellen for ALLE arrangementer,
        // uavhengig om innslaget er videresendt eller ikke.
        if( $this->getContext()->getSesong() > 2019 ) {
		    $SQL = new Query("SELECT 
                    `participant`.*, 
                    `relation`.`instrument`,
                    `relation`.`instrument_object`,
                    GROUP_CONCAT(`arrangement`.`arrangement_id`) AS `arrangementer`,
                    `band`.`bt_id`
                FROM `smartukm_participant` AS `participant` 
                JOIN `smartukm_rel_b_p` AS `relation` 
                    ON (`relation`.`p_id` = `participant`.`p_id`)
                JOIN `smartukm_band` AS `band`
                    ON(`band`.`b_id` = `relation`.`b_id`)
                LEFT JOIN `ukm_rel_arrangement_person` AS `arrangement`
                    ON(`arrangement`.`innslag_id` = '#innslag' AND `arrangement`.`person_id` = `participant`.`p_id`)
                WHERE `relation`.`b_id` = '#innslag'
                GROUP BY `participant`.`p_id`
                ORDER BY 
                    `participant`.`p_firstname` ASC, 
                    `participant`.`p_lastname` ASC",
                [
                    'innslag' => $this->getInnslagId()
                ]
            );
        } else {
		    $SQL = new Query("SELECT 
                    `participant`.*, 
                    `relation`.`instrument`,
                    `relation`.`instrument_object`,
                    GROUP_CONCAT(`smartukm_fylkestep_p`.`pl_id`) AS `pl_ids`,
                    `band`.`bt_id`
                FROM `smartukm_participant` AS `participant` 
                JOIN `smartukm_rel_b_p` AS `relation` 
                    ON (`relation`.`p_id` = `participant`.`p_id`) 
                LEFT JOIN `smartukm_fylkestep_p`
                    ON(`smartukm_fylkestep_p`.`b_id` = '#bid' AND `smartukm_fylkestep_p`.`p_id` = `participant`.`p_id`)
                JOIN `smartukm_band` AS `band`
                    ON(`band`.`b_id` = `relation`.`b_id`)
                WHERE `relation`.`b_id` = '#bid'
                GROUP BY `participant`.`p_id`
                ORDER BY 
                    `participant`.`p_firstname` ASC, 
                    `participant`.`p_lastname` ASC",
            [
                'bid' => $this->getInnslagId()
            ]
            );
        }
		$res = $SQL->run();
		if( isset( $_GET['debug'] ) || $this->debug )  {
			echo $SQL->debug();
		}
		if($res === false) {
			throw new Exception("PERSONER_COLLECTION: Klarte ikke hente personer og roller - kan databaseskjema være utdatert?" . $SQL->debug());
		}
		while( $r = Query::fetch( $res ) ) {
			$person = new Person( $r );
			$person->setContext( $this->getContextInnslag() );
			$this->personer[ $person->getId() ] = $person;
		}
	}
	

    /**
     * Hent innslagets ID
     *
     * @return Int $id
     */
	public function getInnslagId() {
		return $this->innslag_id;
	}

    /**
     * Hent innslagets type
     *
     * @return Type
     */
    public function getInnslagType() {
		return $this->innslag_type;
	}

    /**
     * Hent innslagets / personers context
     *
     * @return Context
     */
	public function getContext() {
		return $this->context;
	}
    
    /**
     * Opprett et innslagContext-objekt (why?)
     *
     * @return Context
     */
	public function getContextInnslag() {
        /**
         * Hvis kontekst er sesong, snakker vi om lokal-nivået.
         * Det vil da ikke være behov for å filtrere videresendte personer, og
         * det vil teknisk være mulig å hente ut all informasjon uten mønstrings-objektet.
         * 
         * Hvorvidt dette funker 100% som tenkt er vanskelig å si enda, da dette må testes ut over tid.
         * Implementert desember 2018.
         */
        if( $this->getContext()->getType() == 'sesong' && null == $this->getContext()->getMonstring()) {
            throw new Exception(
                'Sesong har ikke tilstrekkelig data for å hente ut ContextInnslag. Kontakt UKM Norge support',
                106003
            );
            /* Sånn var det forsøkt implementert (kodet ut oktober 2019. For et år.)
            return Context::createInnslag(
                $this->getInnslagId(),			    // Innslag ID
                $this->getInnslagType(),			// Innslag type (objekt)
                null,                               // Mønstring ID
                'kommune',                          // Mønstring type
                $this->getContext()->getSesong()    // Mønstring sesong
            );
            */
        }
        
		return Context::createInnslagWithMonstringContext(
			$this->getInnslagId(),					// Innslag ID
			$this->getInnslagType()->getId(),	    // Innslag type (objekt)
            $this->getContext()->getMonstring()     // Mønstring-context
		);
	}

	private function _autoloadPlidParameter( $pl_id ) {
		if( $pl_id == false ) {
			return $this->getContext()->getMonstring()->getId();
		} elseif( Arrangement::validateClass( $pl_id ) ) {
			return $pl_id->getId();
		}
		return $pl_id;
    }
}