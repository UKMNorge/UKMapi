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
		return $this->seconds;
	}

	public function getHumanShort() {
		return $this->_getHuman('sek', 'min');
	}
	
	public function getHumanLong() {
		return $this->_getHuman('sekunder', 'minutter');
	}
	

	private function _getHuman( $sek, $min ) {
		$q = floor($sec / 60);
		$r = $sec % 60;
		
		if ($q == 0)
			return $r.' '.$sek;
			
		if ($r == 0)
			return $q.' '.$min;
		
		return $q . $min .' '. $r . $sek;
	}
}