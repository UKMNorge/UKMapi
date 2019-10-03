<?php

namespace UKMNorge\Innslag\Context;
require_once('UKM/Autoloader.php');

class Monstring {
	var $id;
	var $type;
	var $sesong;
	var $kommuner;
	var $fylke;

	public function __construct( $id, $type, $sesong, $fylke, $kommuner ) {
		$this->id = $id;
		$this->type = $type;
		$this->sesong = $sesong;
		$this->kommuner = $kommuner;
		$this->fylke = $fylke;
	}
	
	public function setId( $id ) {
		$this->id = $id;
		return $this;
	}
	public function getId() {
		return $this->id;
	}
	
	public function setType( $type ) {
		$this->type = $type;
		return $this;
	}
	public function getType() {
		return $this->type;
	}
	
	public function setSesong( $sesong ) {
		$this->sesong = $sesong;
		return $this;
	}
	public function getSesong() {
		return $this->sesong;
	}
	
	public function setKommuner( $kommuner ) {
		$this->kommuner = $kommuner;
		return $this;
	}
	public function getKommuner() {
		return $this->kommuner;
	}
	
	public function setFylke( $fylke ) {
		if( !is_numeric( $fylke ) ) {
			throw new Exception('CONTEXT_MONSTRING: setFylke krever numerisk fylke-id');	
		}
		$this->fylke = $fylke;
		return $this;
	}
	public function getFylke() {
		return $this->fylke;
	}
	
	public function getVideresendTil() {
        throw new Exception('DEVELOPER ALERT: getVideresendTil() må implementeres for 2019. Varsle support@ukm.no');
        
		if( null == $this->videresend_til ) {
			switch( $this->getType() ) {
				case 'kommune': 
					$videresendTil = [];
					foreach( $this->getKommuner() as $kommune_id ) {
						$kommune = new Kommune( $kommune_id );
						if( !isset( $videresendTil[ $kommune->getFylke()->getId() ] ) ) {
							$fylke = monstringer_v2::fylke( $kommune->getFylke(), $this->getSesong() );
							$videresendTil[ $kommune->getFylke()->getId() ] = $fylke->getId();
						}
					}
					$this->videresend_til = $videresendTil;
				break;
				case 'fylke':
					$this->videresend_til = monstringer_v2::land( $this->getSesong() );
				break;
				default:
					throw new Exception(
						'CONTEXT_MONSTRING: Kan ikke videresende fra landsnivå'
					);
			}
		}
		return $this->videresend_til;
	}
}