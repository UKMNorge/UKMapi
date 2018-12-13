<?php
	
class samtykke_kommunikasjon_collection {
	
	var $id = null;
	var $meldinger = null;
	
	public function __construct( $samtykke_id ) {
		$this->id = $samtykke_id;
	}
	
	public function getAll() {
		if( null == $this->meldinger ) {
			$this->_load();
		}
		return $this->meldinger;
	}
	
	public function har( $melding_type ) {
		try {
			$melding = $this->get( $melding_type );
			if( is_object( $melding ) ) {
				return true;
			}
		} catch( Exception $e ) { }

		return false;
	}
	
	public function get( $melding_type ) {
		foreach( $this->getAll() as $melding ) {
			if( $melding->getType() == $melding_type ) {
				return $melding;
			}
		}
		
		throw new Exception('Beklager, det finnes ingen slike meldinger for denne personen');
	}
	
	public function _load() {
		$sql = new SQL("
			SELECT * 
			FROM `samtykke_deltaker_kommunikasjon`
			WHERE `samtykke_id` = '#id'
			ORDER BY `id` DESC",
			[
				'id' => $this->getId(),
			]
		);
		$res = $sql->run();
		$this->meldinger = [];
		while( $row = SQL::fetch( $res ) ) {
			$this->meldinger[] = new samtykke_melding( $row );
		}
	}
	
	public function getId() {
		return $this->id;
	}
}