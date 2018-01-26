<?php
require_once('UKM/nominasjon_media.class.php');
require_once('UKM/nominasjon_arrangor.class.php');
require_once('UKM/nominasjon_konferansier.class.php');

/**
 * Innslagets nominasjon behandles som et eget objekt
 * For å kunne kjøre getNominasjon()->har() på et innslag
 * som aldri vil ha nominasjon, eksisterer denne klassen
 * 
**/
class nominasjon_placeholder {
	private $har_nominasjon = false;

	public function __construct( $skip1, $skip2, $skip3 ) {
		// Do nothing	
	}

	public function har() {
		return $this->harNominasjon();
	}
	
	public function harNominasjon() {
		return $this->har_nominasjon;
	}
	
	public function setHarNominasjon( $bool ) {
		$this->har_nominasjon = $bool;
		return $this;
	}
} 

class nominasjon extends nominasjon_placeholder {
	private $id;
	private $delta_id;
	private $innslag_id;
	private $participant;
	private $type;
	
	private $niva;
	private $kommune_id;
	private $fylke_id;
	private $sesong;
	
	private $er_nominert = false;
	private $har_deltakerskjema = false;
	private $har_voksenskjema = false;
	
	private $voksen;
	
	public function __construct( $innslag_id_or_row, $innslag_type=false, $niva=false ) {
		if( is_numeric( $innslag_id_or_row ) ) {
			$this->_loadById( $innslag_id_or_row, $innslag_type, $niva );
		} elseif( is_array( $innslag_id_or_row ) ) {
			$this->_loadByRow( $innslag_id_or_row );
		} else {
			throw new Exception('NOMINASJON: Kan kun opprette objekt fra integer ID eller array', 4);
		}
	}
	
	public static function getLoadQuery() {
		return "SELECT *
			FROM `ukm_nominasjon`
			JOIN `#table` ON (`#table`.`nominasjon` = `ukm_nominasjon`.`id`)";
	}
	
	public static function getDetailTable( $innslag_type ) {
		switch( $innslag_type ) {
			case 'nettredaksjon':
				return 'ukm_nominasjon_media';
			case 'media':
			case 'arrangor';
			case 'konferansier';
				return 'ukm_nominasjon_'. $innslag_type;
			default:
				throw new Exception('NOMINASJON: Kan ikke laste inn nominasjon pga ukjent type '. $innslag_type, 2 );
		}
	}
	
	private function _loadById( $innslag_id, $innslag_type, $niva ) {
		$sql = new SQL(
			nominasjon::getLoadQuery() . "
			WHERE `ukm_nominasjon`.`b_id` = '#innslagid'
			AND `ukm_nominasjon`.`niva` = '#niva'
			ORDER BY `ukm_nominasjon`.`id` ASC
			LIMIT 1",
			[
				'table' => nominasjon::getDetailTable( $innslag_type ),
				'innslagid' => $innslag_id,
				'niva' => $niva,
			]
		);
		$res = $sql->run('array');
		
		if( is_array( $res ) ) {
			$this->_loadByRow( $res );
		}
	}
	
	protected function _loadByRow( $row ) {
		if( !is_array( $row ) ) {
			throw new Exception('NOMINASJON: Kan ikke laste inn nominasjon fra annet enn array', 3);
		}
		
		$this->setId( $row['id'] );
		$this->setInnslagId( $row['b_id'] );
		$this->setNiva( $row['niva'] );
		$this->setFylkeId( $row['fylke_id'] );
		$this->setKommuneId( $row['kommune_id'] );
		$this->setSesong( $row['season'] );
		$this->setType( $row['type'] );
		$this->setErNominert( $row['nominert'] == 'true' );
		$this->exists = true;

		$this->setHarNominasjon( true );
		try {
			$this->setVoksen( new nominasjon_voksen( $row['nominasjon'] ) );
		} catch( Exception $e ) {
			$this->setVoksen = new nominasjon_voksen_placeholder( null );
		}
	}
	
	public function getId(){
		return $this->id;
	}

	public function setId($id){
		$this->id = $id;
		return $this;
	}

	public function getInnslagId(){
		return $this->participant_id;
	}

	public function setInnslagId($participant_id){
		$this->participant_id = $participant_id;
		return $this;
	}
	
	public function getType(){
		return $this->type;
	}

	public function setType($type){
		$this->type = $type;
		return $this;
	}
	
	
	public function getNiva(){
		return $this->niva;
	}

	public function setNiva($niva){
		$this->niva = $niva;
		return $this;
	}

	public function getPlId(){
		return $this->pl_id;
	}

	public function setPlId($pl_id){
		$this->pl_id = $pl_id;
		return $this;
	}

	public function getKommuneId(){
		return $this->kommune_id;
	}

	public function setKommuneId($kommune_id){
		$this->kommune_id = $kommune_id;
		return $this;
	}

	public function getFylkeId(){
		return $this->fylke_id;
	}

	public function setFylkeId($fylke_id){
		$this->fylke_id = $fylke_id;
		return $this;
	}
	
	public function getSesong() {
		return $this->sesong;
	}
	public function setSesong( $sesong ) {
		$this->sesong = $sesong;
		return $this;
	}

	public function setErNominert( $nominert ) {
		$this->er_nominert = $nominert;
		return $this;
	}
	public function erNominert() {
		return $this->er_nominert;
	}
	
	public function harDeltakerskjema() {
		return $this->har_deltakerskjema;
	}
	public function setHarDeltakerskjema( $bool ) {
		$this->har_deltakerskjema = $bool;
		return $this;
	}
	
	public function harVoksenskjema() {
		return $this->har_voksenskjema;
	}
	public function setHarVoksenskjema( $bool ) {
		$this->har_voksenskjema = $bool;
		return $this;
	}
	
	public function setVoksen( $voksen ) {
		$this->voksen = $voksen;
		return $this;
	}
	public function getVoksen() {
		return $this->voksen;
	}
}