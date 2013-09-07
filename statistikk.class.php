<?php
require_once('UKM/monstring.class.php');

class statistikk {
	var $data = false;
	public function __construct($kommuneArray, $fylkeArray) {
		$this->kommuner = $kommuneArray;
                $this->fylker = $fylkeArray;
	}
	

	public function getTotal($season, $type = 'land') {
            $query_persons = "SELECT count(`p_id`) as `persons` FROM `ukm_statistics`
                                WHERE `season`=#season";
            $query_bands = "SELECT COUNT(DISTINCT `b_id`) as `bands` FROM `ukm_statistics` 
                            WHERE `season`=#season";
            
            if ($type == 'fylke') {
                
            }
            else if ($type == 'kommune') {
                
            }
            else {
                $query_pl_missing = "SELECT SUM(`pl_missing`) as `missing` FROM `smartukm_place`
                                        WHERE `season`=#season";
            }
            
            // PL_missing
            $sql = new SQL($query_pl_missing, array('season'=>(int)$season));
            $persons = (int)$sql->run('field', 'missing');
            
            // Persons
            $sql = new SQL($query_persons, array('season'=>(int)$season));
            $persons += (int)$sql->run('field', 'persons');
            
            // Bands
            $sql = new SQL($query_bands, array('season'=>(int)$season));
            $bands = (int)$sql->run('field', 'bands');
            
            var_dump($persons);
            var_dump($bands);
	}

	public function getCategory($season) {
            if(!$this->data)
                $this->_load($season);
	}
	
	private function _load($season) {
                $kommuneList = implode(', ', $this->kommuner);
		
		$sql = new SQL("SELECT * FROM `ukm_statistics`
						WHERE `k_id` IN (#kommuneList)
						AND `season` = '#season'",
						array('kommuneList' => $kommuneList, 'season' => $season));
		$res = $sql->run('array');
		               
//		var_dump($res);
	}
}
?>