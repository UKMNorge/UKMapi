<?php
require_once('UKM/sql.class.php');
require_once('UKM/forestilling.class.php');

class forestillinger extends program {}

class program {
	var $loaded = false;
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
		$this->_load();
		return $this->forestillinger;
	}

	public function getAllSkjulte() {
		$this->_load();
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
	
	public function getIdArray( $method='getAll' ) {
		if( !in_array( $method, array('getAll', 'getAllSkjulte', 'getAllInkludertSkjulte') ) ) {
			throw new Exception('PROGRAM: getIdArray fikk ugyldig metode-kall ('. $method .')');
		}
		$idArray = [];
		foreach( $this->$method() as $hendelse ) {
			$idArray[] = $hendelse->getId();
		}
		return $idArray;
	}

	
	public function setContainerObjectId( $id ) {
		$this->containerObjectId = $id;
		return $this;
	}
	public function getContainerObjectId() {
		return $this->containerObjectId;
	}
	
	public function setContainerType( $type ) {
		if( !in_array( $type, array('innslag','monstring' ) ) ) {
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
		if( $this->loaded ) {
			return true;
		}

		$this->forestillinger = array();
		
		$SQL = $this->_getQuery();
#		echo $SQL->debug();
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
		}
		$this->loaded = true;
		return true;
	}

	private function _getQuery() {
		switch( $this->getContainerType() ) {
			case 'monstring':
				if( null == $this->getContainerObjectId() ) {
					throw new Exception('FORESTILLINGER: Krever MønstringID (ContainerObjectId) for å hente mønstringens program');
				}
				
				return new SQL( "SELECT *
						    	 FROM `smartukm_concert` 
						    	 WHERE `pl_id` = '#pl_id'
						    	 ORDER BY #order ASC",
						    array('pl_id' => $this->getContainerObjectId(),
						     	  'order' => 'c_start'
						     	 )
						     );
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