<?php
require_once('UKM/sql.class.php');
require_once('UKM/forestilling.class.php');

class forestillinger extends program {}

class program {
	var $loaded = false;
	var $forestillinger = null;
	var $skjulte_forestillinger = null;
	var $containerType = null;
	var $containerId = null;
	var $rekkefolge = null;
	
	var $container_pl_id = null; // Brukes av container_type 'innslag'
	
	public function __construct($container_type, $container_object_id) {
		$this->setContainerType( $container_type );
		$this->setContainerId( $container_object_id );
		$this->rekkefolge = [];
	}
	
	public static function sorterPerDag( $forestillinger ) {
		$sortert = [];
		foreach( $forestillinger as $forestilling ) {
			$key = $forestilling->getStart()->format('d_m');
			if( !isset( $sortert[ $key ] ) ) {
				$dag = new stdClass();
				$dag->key	= $key;
				$dag->date 	= $forestilling->getStart();
				$dag->forestillinger = [];
				$sortert[ $key ] = $dag;
			}
			$sortert[ $key ]->forestillinger[] = $forestilling;
		}
		return $sortert;
	}

	public function getAntall() {
		return sizeof( $this->getAll() );
	}
	
	public function getAntallSkjulte() {
		return sizeof( $this->getAllSkjulte() );
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

	
	public function setContainerId( $id ) {
		$this->containerId = $id;
		return $this;
	}
	public function getContainerId() {
		return $this->containerId;
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
			if( 'innslag' == $this->getContainerType() ) {
				$this->setRekkefolge( $forestilling->getId(), $row['order'] );
			}
		}
		$this->loaded = true;
		return true;
	}
	
	public function setRekkefolge( $forestilling_id, $order ) {
		$this->rekkefolge[ $forestilling_id ] = $order;
		return $this;
	}
	public function getRekkefolge( $forestilling ) {
		if( is_numeric( $forestilling ) ) {
			$id = $forestilling;
		} else {
			$id = $forestilling->getId();
		}
		
		if( !isset( $this->rekkefolge[ $id ] ) ) {
			throw new Exception('Innslaget er ikke med i denne hendelsen! ('. $id .')');
		}
		return $this->rekkefolge[ $id ];
	}

	private function _getQuery() {
		switch( $this->getContainerType() ) {
			case 'monstring':
				if( null == $this->getContainerId() ) {
					throw new Exception('FORESTILLINGER: Krever MønstringID (containerId) for å hente mønstringens program');
				}
				
				return new SQL( "SELECT *
						    	 FROM `smartukm_concert` 
						    	 WHERE `pl_id` = '#pl_id'
						    	 ORDER BY #order ASC",
						    array('pl_id' => $this->getContainerId(),
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
							array('b_id' => $this->getContainerId(), 'pl_id' => $this->getMonstringId() )
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