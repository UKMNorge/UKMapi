<?php

namespace UKMNorge\Samtykke;

use Exception;
use UKMNorge\Database\SQL\Query;

class ForesattApproval {
	var $id;
	var $approval = null;
	var $approval_id;
	var $ip;
	var $hash;	
	var $timestamp;
		
	public function __construct( $approval_id ) {
		if( is_numeric( $approval_id ) ) {
			$this->_load_by_id( $approval_id );
#		} elseif( is_array( $id ) ) {
#			$this->_load_by_row( $id );
		} else {
			throw new Exception('Kan kun laste inn samtykke-godkjenning med numerisk ID', 1);
		}
	}
	
	private function _load_by_id( $approval_id ) {
		$sql = new Query("
			SELECT * 
			FROM `samtykke_approval_foresatt`
			WHERE `approval` = '#approval'",
			[
				'approval' => $approval_id,
			]
		);
		$sql->charset('UTF-8');
		$res = $sql->run('array');
		
		if( $res ) {
			return $this->_load_by_row( $res );
		}
		throw new Exception('Fant ingen godkjenning fra foresatt for godkjenning '. $approval_id, 2 );
	}
	
	private function _load_by_row( $row ) {
		$this->id = $row['id'];
		$this->approval_id = $row['approval'];
		$this->ip = $row['ip'];
		$this->hash = $row['hash'];
		$this->timestamp = $row['timestamp'];
	}
	
	public function getId() {
		return $this->id;
	}
		
	public function getApprovalId() {
		return $this->approval_id;
	}

	public function getIP() {
		return $this->ip;
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
}