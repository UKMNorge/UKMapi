<?php

namespace UKMNorge\Samtykke;

use Exception;
use UKMNorge\Database\SQL\Query;

require_once('UKM/Autoloader.php');

class Approval {
	var $id;
	var $prosjekt = null;
	var $prosjekt_id;
	var $request = null;
	var $request_id;
	var $alder;
	var $ip;

	var $foresatt_navn;
	var $foresatt_mobil;
	
	var $hash;
	
	var $timestamp;
	
	var $trenger_foresatt;
		
	public function __construct( $request_id ) {
		if( is_numeric( $request_id ) ) {
			$this->_load_by_id( $request_id );
#		} elseif( is_array( $id ) ) {
#			$this->_load_by_row( $id );
		} else {
			throw new Exception('Kan kun laste inn samtykke-godkjenning med numerisk ID', 1);
		}
	}
	
	private function _load_by_id( $request_id ) {
		$sql = new Query("
			SELECT * 
			FROM `samtykke_approval`
			WHERE `request` = '#request'",
			[
				'request' => $request_id,
			]
		);
		$sql->charset('UTF-8');
		$res = $sql->run('array');
		
		if( $res ) {
			return $this->_load_by_row( $res );
		}
		throw new Exception('Fant ingen godkjenning for forespørsel '. $request_id, 2 );
	}
	
	private function _load_by_row( $row ) {
		$this->id = $row['id'];
		$this->prosjekt_id = $row['prosjekt'];
		$this->request_id = $row['request'];
		
		$this->alder = $row['alder'];
		$this->ip = $row['ip'];
		
		$this->foresatt_navn = $row['foresatt_navn'];
		$this->foresatt_mobil = $row['foresatt_mobil'];
		
		$this->hash = $row['hash'];
		$this->timestamp = $row['timestamp'];
		$this->trenger_foresatt = $row['trenger_foresatt'] == 'true';
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
	
	public function getRequestId() {
		return $this->request_id;
	}
	public function getRequest() {
		if( null == $this->request ) {
			$this->request = new Request( $this->getRequestId() );
		}
		return $this->request;
	}
	
	public function getAlder() {
		return $this->alder;
	}
	public function getIP() {
		return $this->ip;
	}
	public function getForesattNavn() {
		return $this->foresatt_navn;
	}
	public function getForesattMobil() {
		return $this->foresatt_mobil;
	}
	
	public function getTimestamp() {
		return $this->timestamp;
	}
	public function trengerForesatt() {
		return $this->getTrengerForesatt();
	}
	public function getTrengerForesatt() {
		return $this->trenger_foresatt;
	}
	
	public function getForesattApproval() {
		if( null == $this->foresatt_approval ) {
			$this->foresatt_approval = new ForesattApproval( $this->getId() );
		}
		return $this->foresatt_approval;
	}
	
	public function getHash() {
		return $this->hash;
	}
	
	public function getLenkeHash() {
		return substr( $this->getHash(), 6, 10 );
	}
}