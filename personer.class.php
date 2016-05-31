<?php
require_once('UKM/person.class.php');

class personer {
	
	var $b_id = null;
	var $personer = null;
	
	public function __construct( $b_id ) {
		$this->b_id = $b_id;
		
		$this->_load();
	}
	
	/**
	 * getAll
	 * Returner alle personer i innslaget
	 *
	 * @return array $personer
	**/
	public function getAll() {
		return $this->personer;
	}
	
	/**
	 * getAllVideresendt
	 * Hent alle personer i innslaget videresendt til gitt mønstring
	 *
	 * @param int $pl_id
	 * @return bool
	**/
	public function getAllVideresendt( $pl_id ) {
		foreach( $this->getAll() as $person ) {
			if( $person->erVideresendt( $pl_id ) ) {
				
			}
		}
	}
	
	/**
	 * getSingle
	 * Hent én enkelt person fra innslaget. 
	 * Er beregnet for tittelløse innslag, som aldri har mer enn én person
	 * Kaster Exception hvis innslaget har mer enn én person
	 *
	 * @return person_v2 $person
	**/
	public function getSingle() {
		if( 1 < sizeof( $this->getAntall() ) ) {
			throw new Exception( 'PERSON_V2: getSingle() er kun ment for bruk med tittelløse innslag som har ett personobjekt. '
								.'Dette innslaget har '. $this->getAntall() .' personer');	
		}
		$all = $this->getAll();
		return end( $all ); // and only...
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
	 	
	private function _load() {
		$this->personer = array();
		
		$SQL = new SQL("SELECT 
							`participant`.*, 
							`relation`.`instrument`,
							GROUP_CONCAT(`smartukm_fylkestep_p`.`pl_id`) AS `pl_ids`,
							`band`.`bt_id`
						FROM `smartukm_participant` AS `participant` 
						JOIN `smartukm_rel_b_p` AS `relation` 
							ON (`relation`.`p_id` = `participant`.`p_id`) 
						INNER JOIN `smartukm_fylkestep_p`
							ON(`smartukm_fylkestep_p`.`b_id` = '#bid' AND `smartukm_fylkestep_p`.`p_id` = `participant`.`p_id`)
						JOIN `smartukm_band` AS `band`
							ON(`band`.`b_id` = `relation`.`b_id`)
						WHERE `relation`.`b_id` = '#bid'
						GROUP BY `participant`.`p_id`
						ORDER BY 
							`participant`.`p_firstname` ASC, 
							`participant`.`p_lastname` ASC",
						array('bid' => $this->_getBID() ));
		$res = $SQL->run();
		if( isset( $_GET['debug'] ) )  {
			echo $SQL->debug();
		}
		while( $r = mysql_fetch_assoc( $res ) ) {
			$person = new person_v2( $r );
			$this->personer[ $person->getId() ] = $person;
		}
	}
	
	private function _setBID( $bid ) {
		$this->b_id = $bid;
		return $this;
	}
	private function _getBID() {
		return $this->b_id;
	}
}