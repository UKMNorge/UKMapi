<?php
	
class tid {
	var $sekunder = null;
	
	public function __construct( $sekunder = false ) {
		if( is_numeric( $sekunder ) ) {
			$this->setSekunder( $sekunder );
		}
	}
	
	public function setSekunder( $sekunder ) {
		$this->sekunder = $sekunder;
	}
	
	public function getSekunder() {
		return $this->sekunder;
	}

	public function getHumanShort() {
		return $this->_getHuman('s', 'm');
	}

	public function getHuman() {
		return $this->_getHuman('sek', 'min');
	}
	
	public function getHumanLong() {
		return $this->_getHuman('sekunder', 'minutter');
	}
	

	private function _getHuman( $sek, $min ) {
		$q = floor($this->sekunder / 60);
		$r = $this->sekunder % 60;
		
		if ($q == 0)
			return $r.' '.$sek;
			
		if ($r == 0)
			return $q.' '.$min;
		
		return $q . $min .' '. $r . $sek;
	}
}