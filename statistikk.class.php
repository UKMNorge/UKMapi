<?php
require_once 'UKM/sql.class.php';

class statistikk {
	var $data = false;
	var $type = false;
	var $fylkeID = false;
	var $kommuner = array();
	
	public function __construct(){}
	
	public function setKommune($kommuneArray) {
		$this->type = 'kommune';
		$this->kommuner = $kommuneArray;
	}
	
	public function setFylke($fylkeID) {
		$this->type = 'fylke';
		$this->fylkeID = $fylkeID;
	}
	
	public function setLand(){
		$this->type = 'land';
	}
	
        /**
         * Get total persons and bands
         * @param type $season the season requested
         * @return array (persons,bands)
         */
	public function getTotal($season) {
            $query_persons = "SELECT count(`stat_id`) as `persons` FROM `ukm_statistics`
                                WHERE `season`=#season AND `f_id` != 21";
            $query_bands = "SELECT COUNT(DISTINCT `b_id`) as `bands` FROM `ukm_statistics` 
                            WHERE `season`=#season AND `f_id` != 21";
            
            // Fylke
            if ($this->type == 'fylke') {
                $query_persons .= ' AND `f_id` =#fylkeID';
                $query_bands .= ' AND `f_id` =#fylkeID';
                $query_pl_missing = "SELECT SUM(`missing2`) AS `missing` FROM 
                    (SELECT `pl_missing` AS `missing2`, `pl_name` FROM `smartukm_place` AS `pl` 
                    JOIN `smartukm_rel_pl_k` AS `rel` ON (`rel`.`pl_id` = `pl`.`pl_id`) 
                    JOIN `smartukm_kommune` AS `kommune` ON (`kommune`.`id` = `rel`.`k_id`) 
                    WHERE `kommune`.`idfylke` = #fylkeID 
                    AND `pl_missing` > 0
                    AND `pl`.`season` = #season 
                    GROUP BY `pl`.`pl_id`) AS `temptable`";
                
                $sql = new SQL("SELECT SUM(`pl_missing`) as `missing` FROM `smartukm_place`
                                        WHERE `season`=#season AND `pl_fylke` = #fylkeID",
                                array('season' => (int)$season, 'fylkeID' => (int)$this->fylkeID));
                $missing = $sql->run('field', 'missing');
            }
            
            // Kommune
            else if ($this->type == 'kommune') {
                $query_persons .= ' AND `k_id` IN (#kommuner)';
                $query_bands .= ' AND `k_id` IN (#kommuner)';
                $query_pl_missing = "SELECT `pl_missing` as `missing` FROM `smartukm_place` as `place`
                                    JOIN `smartukm_rel_pl_k` as `rel` ON `rel`.`pl_id` = `place`.`pl_id`
                                    WHERE `rel`.`k_id` = #kommune
                                    AND `place`.`season` = #season LIMIT 1";
            }
            
            // Land
            else {
                $missing = 0;
                $query_pl_missing = "SELECT SUM(`pl_missing`) as `missing` FROM `smartukm_place`
                                        WHERE `season`=#season AND `pl_fylke` != 21";
            }
            
            // PL_missing
            $sql = new SQL($query_pl_missing, array('season'=>(int)$season,
                                                    'fylkeID'=>(int)$this->fylkeID,
                                                    'kommune' => $this->kommuner[0],
                                                    'kommuner' => implode(',', $this->kommuner)));

            $missing += (int)$sql->run('field', 'missing');
            
            // Persons
            $sql = new SQL($query_persons, array('season'=>(int)$season,
                                                 'fylkeID'=>(int)$this->fylkeID,
                                                 'kommuner' => implode(',', $this->kommuner)));
            $persons = (int)$sql->run('field', 'persons');
            
            $persons += $missing;
            
            // Bands
            $sql = new SQL($query_bands, array('season'=>(int)$season,
                                                'fylkeID'=>(int)$this->fylkeID,
                                                'kommuner' => implode(',', $this->kommuner)));
            $bands = (int)$sql->run('field', 'bands');
                        
            return array('persons'=>$persons, 'bands'=>$bands);
	}

	public function getCategory($season) {
            if(!$this->data)
                $this->_load($season);
	}
	
	// Jim liker ikke denne
	// henter personer
	public function getUglyStatArrayPerson($season) {
		if (!$this->type) return array();
		
		$missing = 0;
		// Finner telling for hver bandtype (kategori)
		$qry = "SELECT `bt_id`, COUNT(*) as count FROM `ukm_statistics`".
				" WHERE `season` =#season AND `bt_id`>0";
				
		// finner telling for subkategorier i Scene
		$subcat_qry = "SELECT `subcat`,COUNT(*) AS count FROM `ukm_statistics`".
				" WHERE `season` =#season AND `bt_id = 1`".
		
		
		if ($this->type == 'kommune') {
			$qry .= " AND k_id IN #kommuner";
			$subcat_qry = " and k_id IN #kommuner";
			$query_pl_missing = "SELECT `pl_missing` as `missing`".
								" FROM `smartukm_place` as `place`".
								" JOIN `smartukm_rel_pl_k` as `rel`".
								" ON `rel`.`pl_id` = `place`.`pl_id`".
								" WHERE `rel`.`k_id` = #kommune".
								" AND `place`.`season` = #season LIMIT 1";
			
		} else if ($this->type == 'fylke') {
			$qry .= " AND `f_id` =#fylkeID";
			$subcat_qry .=" AND `f_id` =#fylkeID";
			$query_pl_missing = 
				"SELECT SUM(`missing2`) AS `missing` FROM". 
                    	" (SELECT `pl_missing` AS `missing2`, `pl_name`".
					" FROM `smartukm_place` AS `pl` ".
					" JOIN `smartukm_rel_pl_k` AS `rel`".
					" ON (`rel`.`pl_id` = `pl`.`pl_id`)".
					" JOIN `smartukm_kommune` AS `kommune`".
					" ON (`kommune`.`id` = `rel`.`k_id`)".
					" WHERE `kommune`.`idfylke` = #fylkeID".
					" AND `pl_missing` > 0".
					" AND `pl`.`season` = #season".
					" GROUP BY `pl`.`pl_id`)".
				" AS `temptable`";
		} else if ($this->type == 'land') {
			$qry .= " AND `f_id` != 21";
			$subcat_qry .=" AND `f_id` != 21";
			$query_pl_missing = "SELECT SUM(`pl_missing`) as `missing`".
								" FROM `smartukm_place`".
								" WHERE `season`=#season AND `pl_fylke` != 21";
		} 
		
		$qry .= " GROUP BY `bt_id` ORDER BY `bt_id` asc; "; // asc er ikke viktig.
		$qry_pl_missing .= " GROUP BY `subcat` ORDER BY `subcat` desc;"; // desc ER viktig!

		// PL_missing
		$sql = new SQL($query_pl_missing, array('season'=>(int)$season,
											'fylkeID'=>(int)$this->fylkeID,
											'kommune' => $this->kommuner[0],
											'kommuner' => implode(',', $this->kommuner)));

		$missing += (int)$sql->run('field', 'missing');

		// stats
		$sql = new SQL($qry, array('season'=>(int)$season,
									'fylkeID'=>(int)$this->fylkeID,
									'kommuner' => implode(',', $this->kommuner)));
		$result = $sql->run();
		
		$array = array();
		while ($r = mysql_fetch_assoc($result)) {
			$array['bt_'.$r['bt_id']] = $r['count'];
			
			//subkategorier
			if($r['bt_id'] == 1) {
				$sql = new SQL($subcat_qry, array('season'=>(int)$season,
										'fylkeID'=>(int)$this->fylkeID,
										'kommuner' => implode(',', $this->kommuner)));
				$subcat_result = $sql->run();
				while ($sr = mysql_fetch_assoc($result)) {
					if ($sr['subcat'] == "")
						$array['annet'] += $sr['count'];
					else
						$array[$sr['subcat']] = $sr['count'];
				}
			} 
		}






// select COUNT(*),`bt_id` from `ukm_statistics` where `season` = 2009 AND `f_id` = 2 AND `bt_id` > 0 GROUP BY `bt_id` ORDER BY `bt_id` asc;
// test-spørring: select * from `ukm_statistics` where `f_id` = 2 and `season` = 2009 and `bt_id` = 1;

		// 1377		
		
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
	
	public function getTargetGroup($season) {
		return rand(1000,3000);
	}
}
?>