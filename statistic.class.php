<?php
require_once('UKM/monstring.class.php');

class statistics {
	var $data = false;
	public function __construct($kommuneArray) {
		$this->kommuner = $kommuneArray;
	}
	

	public function getTotal($season) {
		if(!$this->data)
			$this->_load();
	}

	public function getCategory($season) {
		
	}
	
	
	
	private function _load() {
		foreach($this->kommuner as $kommune) {
			$kommuneList = implode(', ', $kommune);
		}
		
		$sql = new SQL("SELECT * FROM `ukm_statistics`
						WHERE `k_id` IN (#kommuneList)
						AND `season` = '#season'",
						array('kommuneList' => $kommuneList, 'season' => $season));
		$res = $sql->run();
		
		var_dump($res);
	}
}