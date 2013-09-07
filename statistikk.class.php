<?php
require_once('UKM/monstring.class.php');

class statistikk {
	var $data = false;
	public function __construct($kommuneArray, $fylkeID) {
		$this->kommuner = $kommuneArray;
                $this->fylkeID = $fylkeID;
                                
                if ($kommuneArray == false && $fylkeID == false)
                    $this->type = 'land';
                else if ($kommuneArray == false)
                    $this->type = 'fylke';
                else
                    $this->type = 'kommune';
	}
	

	public function getTotal($season) {
            $query_persons = "SELECT count(`p_id`) as `persons` FROM `ukm_statistics`
                                WHERE `season`=#season AND `f_id` != 21";
            $query_bands = "SELECT COUNT(DISTINCT `b_id`) as `bands` FROM `ukm_statistics` 
                            WHERE `season`=#season AND `f_id` != 21";
            
            // Fylke
            if ($this->type == 'fylke') {
                $query_persons .= ' AND `f_id` =#fylkeID';
                $query_bands .= ' AND `f_id` =#fylkeID';
                $query_pl_missing = "SELECT SUM(`missing2`) AS `missing` FROM 
                    (SELECT SUM(`pl_missing`) AS `missing2`, `pl_name` FROM `smartukm_place` AS `pl` 
                    JOIN `smartukm_rel_pl_k` AS `rel` ON (`rel`.`pl_id` = `pl`.`pl_id`) 
                    JOIN `smartukm_kommune` AS `kommune` ON (`kommune`.`id` = `rel`.`k_id`) 
                    WHERE `kommune`.`idfylke` = #fylkeID 
                    AND `pl`.`season` = #season 
                    GROUP BY `pl`.`pl_id`) AS `temptable`";
                
                $sql = new SQL("SELECT SUM(`pl_missing`) as `missing` FROM `smartukm_place`
                                        WHERE `season`=#season AND `pl_fylke` = #fylkeID",
                                array('season' => (int)$season, 'fylkeID' => (int)$this->fylkeID));
                $missing = $sql->run('field', 'missing');
            }
            // Kommune
            else if ($this->type == 'kommune') {
                
            }
            // Land
            else {
                $missing = 0;
                $query_pl_missing = "SELECT SUM(`pl_missing`) as `missing` FROM `smartukm_place`
                                        WHERE `season`=#season AND `pl_fylke` != 21";
            }
            
            // PL_missing
            $sql = new SQL($query_pl_missing, array('season'=>(int)$season,
                                                    'fylkeID'=>(int)$this->fylkeID));
            $missing += (int)$sql->run('field', 'missing');
            var_dump($missing);
            
            // Persons
            $sql = new SQL($query_persons, array('season'=>(int)$season,
                                                 'fylkeID'=>(int)$this->fylkeID));
            $persons = (int)$sql->run('field', 'persons');
            
            $persons += $missing;
            
            // Bands
            $sql = new SQL($query_bands, array('season'=>(int)$season,
                                                'fylkeID'=>(int)$this->fylkeID));
            $bands = (int)$sql->run('field', 'bands');
            
            var_dump($persons);
            //var_dump($bands);
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