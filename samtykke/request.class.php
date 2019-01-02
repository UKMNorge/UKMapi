<?php

namespace UKMNorge\Samtykke;
use SQL;
use Exception;

class Request {
	var $id;
	var $prosjekt = null;
	var $prosjekt_id;
	var $fornavn;
	var $etternavn;
	var $mobil;
	var $melding;
	var $lenker;
	var $hash;
	var $timestamp;
	
	var $approval = null;
	var $attributes = null;
	
	public function __construct( $id ) {
		if( is_numeric( $id ) ) {
			$this->_load_by_id( $id );
		} elseif( is_array( $id ) ) {
			$this->_load_by_row( $id );
		} else {
			throw new Exception('Kan kun laste inn samtykke-request med numerisk ID');
		}
	}
	
	private function _load_by_id( $id ) {
		$sql = new SQL("
			SELECT * 
			FROM `samtykke_request`
			WHERE `id` = '#id'",
			[
				'id' => $id
			]
		);
		$sql->charset('UTF-8');
		$res = $sql->run('array');
		
		if( $res ) {
			$this->_load_by_row( $res );
		}
	}
	
	private function _load_by_row( $row ) {
		$this->id = $row['id'];
		$this->prosjekt_id = $row['prosjekt'];
		$this->fornavn = $row['fornavn'];
		$this->etternavn = $row['etternavn'];
		$this->mobil = $row['mobil'];
		$this->melding = $row['melding'];
		$this->lenker = json_decode( $row['lenker'] );
		$this->hash = $row['hash'];
        $this->timestamp = $row['created'];
	}
	
	
	/**
	 * Sett attributt
	 * Sett egenskaper som for enkelhets skyld kan følge mønstringen et lite stykke
	 * Vil aldri kunne lagres
	 *
	 * @param string $key
	 * @param $value
	 *
	 * @return innslag
	**/
	public function setAttr( $key, $value ) {
		if( null == $this->attributes ) {
			$this->attributes = [];
		}
		$this->attributes[ $key ] = $value;
		return $this;
	}
	
	/**
	 * Hent attributt
	 *
	 * @param string $key
	 *
	 * @return value
	**/
	public function getAttr( $key ) {
		return isset( $this->attributes[ $key ] ) ? $this->attributes[ $key ] : false;
	}
	
	
	public function erGodkjent() {
		try {
			$this->getApproval();
			// Over 15, trenger ikke foresattes godkjenning
			if( !$this->getApproval()->trengerForesatt() ) {
				return true;
			}
			// Under 15, trenger foresattes godkjenning
			else {
				try {
					$godkjent = $this->getApproval()->getForesattApproval();
					return true;
				} catch( Exception $e ) {
					if( $e->getCode() == 2 ) {
						return false;
					}
					throw $e;
				}
			}
		} catch( Exception $e ) {
			if( $e->getCode() == 2 ) {
				return false;
			}
			throw $e;
		}
		return false;
	}
	
	public function harSvart() {
		try {
			$this->getApproval();
			return true;
		} catch( Exception $e ) {
			if( $e->getCode() == 2 ) {
				return false;
			}
			throw $e;
		}
		return false;
	}
	public function getId() {
		return $this->id;
	}
	
	public function getProsjektId() {
		return $this->prosjekt_id;
	}
	public function getProsjekt() {
		if( null == $this->prosjekt ) {
			$this->prosjekt = new Prosjekt( $this->getProsjektId() );
		}
		return $this->prosjekt;
	}
	
	public function getFornavn() {
		return $this->fornavn;
	}
	public function getEtternavn() {
		return $this->etternavn;
	}
	public function getMobil() {
		return $this->mobil;
	}
	public function getMelding() {
		return $this->melding;
	}
	public function getLenker() {
		return $this->lenker;
	}
	public function getTimestamp() {
		return $this->timestamp;
	}
	public function getHash() {
		return $this->hash;
    }
	
	public function getLenkeHash() {
		return substr( $this->getHash(), 6, 10 );
	}
	
	public function getApproval() {
		require_once('UKM/samtykke/approval.class.php');
		if( null == $this->approval ) {
			$this->approval = new Approval( $this->getId() );
		}
		return $this->approval;
	}

	
	public static function createMelding( $prosjekt, $melding, $lenker, $fornavn, $mobil, $hashexcerpt ) {
		$lenke = 'https://personvern.'. UKM_HOSTNAME .'/samtykke/'. 
			'?prosjekt='. $prosjekt->getLenkeHash() .
			'&samtykke='. $hashexcerpt
			;
		
		$search = [
			'$navn',
			'[lenke]',
		];
		
		$replace = [
			$fornavn,
			$lenke
		];
		
		return str_replace( $search, $replace, $melding );
	}

	public static function createMeldingForeldre( $request, $foresatt_navn, $foresatt_mobil ) {
		$lenke = 'https://personvern.'. UKM_HOSTNAME .'/samtykke/'. 
			'?prosjekt='. $request->getProsjekt()->getLenkeHash() .
			'&samtykke='. $request->getLenkeHash() .
			'&foresatt='. $foresatt_mobil
			;
		
		return 'Hei! '. 
			$request->getFornavn() .' har gitt sitt samtykke, men fordi '. $request->getFornavn() .' er under 15 år, '.
			'trenger vi din godkjenning. Les mer og eventuelt gi ditt samtykke her: '.
			$lenke
			;
	}
	
	public static function createMeldingTakk( $request ) {
		if( $request->getApproval()->trengerForesatt() ) {
			$hash = $request->getApproval()->getForesattApproval()->getLenkeHash();
		} else {
			$hash = $request->getApproval()->getLenkeHash();
		}
		return 'Takk, vi har nå mottatt ditt samtykke! '. "\r\n".
			'Din kvittering: '. 
			$request->getId() .'-'. $request->getLenkeHash() .'@'. $hash;
	}

	
	public static function loadFromHash( $prosjekt, $samtykke ) {
		require_once('UKM/samtykke/prosjekt.class.php');
		
		$sql = new SQL("SELECT *,
			`request`.`id` AS `request_id`,
			`prosjekt`.`id` AS `prosjekt_id` 
			FROM `samtykke_prosjekt` AS `prosjekt`
			JOIN `samtykke_request` AS `request` 
				ON (`request`.`prosjekt` = `prosjekt`.`id`)
			WHERE `prosjekt`.`hash-excerpt` = '#prosjekt'
			AND `request`.`hash-excerpt` = '#samtykke'
			",
			[
				'prosjekt' => $prosjekt,
				'samtykke' => $samtykke
			]
		);
		$res = $sql->run('array');
		
		if( is_array( $res ) ) {
			return new Request( $res );
		}
		throw new Exception('Kunne ikke finne gitt samtykke-forespørsel');
	}
}