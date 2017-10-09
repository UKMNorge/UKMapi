<?php
require_once('v1_kontakt.class.php');

class kontakt_v2 {
	public $id = null;
	
	public $fornavn = null;
	public $etternavn = null;
	public $telefon = null;
	public $epost = null;

	public $tittel = null;
	public $facebook = null;
	public $bilde = null;
	
	public $adresse = null;
	public $postnummer = null;
	public $kommune_id = null;

	private $lastUpdated = null;
	private $system_locked = null;

	public function __construct( $id_or_row ) {
		if( is_numeric( $id_or_row ) ) {
			$this->_load_by_id( $id_or_row );
		} elseif( is_array( $id_or_row ) ) {
			$this->_load_by_row( $id_or_row );
		} else {
			throw new Exception('KONTAKT: Oppretting av objekt krever numerisk id eller databaserad');
		}
	}
	
	private function _load_by_id( $id ) {
		$qry = new SQL( self::getLoadQry() 
						. "WHERE `kontakt`.`id` = '#id'",
					array('id' => $id)
					);
		$res = $qry->run('array');
		if( $res ) {
			$this->_load_by_row( $res );
		} else {
			throw new Exception('KONTAKT: Fant ikke kontaktperson '. $id );
		}
	}
	
	private function _load_by_row( $row ) {
		if( !is_array( $row ) ) {
			throw new Exception('KONTAKT: _load_by_row krever dataarray!');
		}
		$this->id = $row['id'];
		$this->fornavn = utf8_encode( $row['firstname'] );
		$this->etternavn = utf8_encode( $row['lastname'] );
		$this->telefon = $row['tlf'];
		$this->epost = $row['email'];
		$this->tittel = utf8_encode( $row['title'] );
		$this->facebook = $row['facebook'];
		$this->bilde = $row['picture'];
		$this->adresse = $row['adress'];
		$this->postnummer = $row['postalcode'];
		$this->kommune_id = $row['kommune'];
		$this->last_updated = $row['last_updated'];
		$this->system_locked = $row['system_locked'];
		if( isset( $row['beskrivelse'] ) ) {
			$this->beskrivelse = $row['beskrivelse'];
		}
	}
	
	public static function getLoadQry() {
		return "SELECT * FROM `smartukm_contacts` AS `kontakt` ";
	}
	
	public function setId( $id ) {
		$this->id = $id;
		return $this;
	}
	public function getId() {
		return $this->id;
	}

	public function getNavn() {
		return $this->getFornavn() .' '. $this->getEtternavn();
	}

	public function getFornavn(){
		return $this->fornavn;
	}
	public function setFornavn( $fornavn ){
		$this->fornavn = $fornavn;
		return $this;
	}
	
	public function getEtternavn(){
		return $this->etternavn;
	}
	public function setEtternavn( $etternavn ){
		$this->etternavn = $etternavn;
		return $this;
	}
	
	public function getTelefon(){
		return $this->telefon;
	}
	public function setTelefon( $telefon ){
		$this->telefon = $telefon;
		return $this;
	}
	
	public function getEpost(){
		return $this->epost;
	}
	public function setEpost( $epost ){
		$this->epost = $epost;
		return $this;
	}
	
	public function getTittel(){
		return $this->tittel;
	}
	public function setTittel( $tittel ){
		$this->tittel = $tittel;
		return $this;
	}
	
	public function getFacebook(){
		return $this->facebook;
	}
	public function setFacebook( $facebook ){
		$this->facebook = $facebook;
		return $this;
	}
	
	public function getSystem_locked(){
		return $this->system_locked;
	}
	public function setSystem_locked( $system_locked ){
		$this->system_locked = $system_locked;
		return $this;
	}
	
	public function getBilde(){
		return $this->bilde;
	}
	public function setBilde( $bilde ){
		$this->bilde = $bilde;
		return $this;
	}
	
	public function getAdresse(){
		return $this->adresse;
	}
	public function setAdresse( $adresse ){
		$this->adresse = $adresse;
		return $this;
	}
	
	public function getPostnummer(){
		return $this->postnummer;
	}
	public function setPostnummer( $postnummer ){
		$this->postnummer = $postnummer;
		return $this;
	}
	
	public function getBeskrivelse(){
		return $this->beskrivelse;
	}
	public function setBeskrivelse( $beskrivelse ){
		$this->beskrivelse = $beskrivelse;
		return $this;
	}
	
	/**
	 * Sett kommune
	 *
	 * @param kommune_id
	 * @return $this
	**/
	public function setKommune( $kommune_id ) {
		$this->kommune_id = $kommune_id;
		return $this;
	}
	/**
	 * Hent kommune
	 *
	 * @return object $kommune
	**/
	public function getKommune() {
		if( null == $this->kommune ) {
			$this->kommune = new kommune( $this->kommune_id );
		}
		return $this->kommune;
	}
	
	public function getLastUpdated(){
		return $this->lastUpdated;
	}
	public function setLastUpdated( $lastUpdated ){
		$this->lastUpdated = $lastUpdated;
		return $this;
	}
}
?>