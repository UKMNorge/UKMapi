<?php
require_once('sql.class.php');

class forestillinger extends program {}

class program {
	var $forestillinger = null;
	var $skjulte_forestillinger = null;
	var $containerType = null;
	var $containerObjectId = null;

	var $container_pl_id = null; // Brukes av container_type 'innslag'
	
	public function __construct($container_type, $container_object_id) {
		$this->setContainerType( $container_type );
		$this->setContainerObjectId( $container_object_id );
	}

	public function getAntall() {
		return sizeof( $this->getAll() );
	}
	
	public function getAll() {
		if( null == $this->forestillinger ) {
			$this->_load();
		}
		return $this->forestillinger;
	}

	public function getAllSkjulte() {
		if( null == $this->forestillinger ) {
			$this->_load();
		}
		return $this->skjulte_forestillinger;
	}	
	
	public function getAllInkludertSkjulte() {
		$alle = array();
		if( is_array( $this->getAll() ) ) {
			$alle = $this->getAll();
		}
		
		if( is_array( $this->getAllSkjulte() ) ) {
			$alle = array_merge( $alle, $this->getAllSkjulte() );
		}
		return $alle;
	}

	
	public function setContainerObjectId( $id ) {
		$this->containerObjectId = $id;
		return $this;
	}
	public function getContainerObjectId() {
		return $this->containerObjectId;
	}
	
	public function setContainerType( $type ) {
		if( !in_array( $type, array('innslag' ) ) ) {
			throw new Exception('FORESTILLINGER: Har ikke støtte for '. $type .'-collection');
		}
		$this->containerType = $type;
		return $this;
	}
	public function getContainerType() {
		return $this->containerType;
	}
	
	public function setMonstringId( $pl_id ) {
		$this->container_pl_id = $pl_id;
		return $this;
	}
	public function getMonstringId() {
		return $this->container_pl_id;
	}
	
	public function _load() {
		$this->forestillinger = array();
		
		$SQL = $this->_getQuery();
		$res = $SQL->run();
		if( !$res ) {
			return array();
		}
		while( $row = mysql_fetch_assoc( $res ) ) {
			$forestilling = new forestilling_v2( $row );
			if( $forestilling->erSynligRammeprogram() ) {
				$this->addForestilling( $forestilling );
			} else {
				$this->addSkjultForestilling( $forestilling );
			}
			
			
			// GJØR NOE MED ORDER-FELTET!!
		}
	}

	private function _getQuery() {
		switch( $this->getContainerType() ) {
			case 'innslag':
				if( null == $this->getMonstringId() ) {
					throw new Exception('FORESTILLINGER: Krever MønstringID for å hente innslagets program');
				}
				return new SQL("SELECT `concert`.*,
								`relation`.`order`
								FROM `smartukm_concert` AS `concert`
								JOIN `smartukm_rel_b_c` AS `relation`
									ON (`relation`.`c_id` = `concert`.`c_id`)
								WHERE `concert`.`pl_id` = '#pl_id'
								AND `relation`.`b_id` = '#b_id'
								ORDER BY `c_start` ASC",
							array('b_id' => $this->getContainerObjectId(), 'pl_id' => $this->getMonstringId() )
							);
		
			default:
				throw new Exception('FORESTILLINGER: Har ikke støtte for '. $type .'-collection (#2)');
		}
	}
	/**
	 * legg til forestilling
	 *
	 * @param $forestilling
	 * @return $this
	**/
	public function addForestilling( $forestilling ) {
		if( null == $this->forestillinger ) {
			$this->forestillinger = array();
		}
		
		$this->forestillinger[] = $forestilling;
		
		return $this;
	}

	/**
	 * legg til skjult forestilling
	 *
	 * @param $forestilling
	 * @return $this
	**/
	public function addSkjultForestilling( $forestilling ) {
		if( null == $this->skjulte_forestillinger ) {
			$this->skjulte_forestillinger = array();
		}
		
		$this->skjulte_forestillinger[] = $forestilling;
		
		return $this;
	}

}