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
           $missing = 0;
           $query_persons = "SELECT count(`stat_id`) as `persons` FROM `ukm_statistics`
                                WHERE `season`='#season'
                                AND `f_id` < 21
                                AND `time` NOT LIKE '#season-11-%'
                                AND `time` NOT LIKE '#season-10-%'";
            $query_bands = "SELECT COUNT(DISTINCT `b_id`) as `bands` FROM `ukm_statistics` 
                            WHERE `season`='#season'
                            AND `f_id` < 21
                            AND `time` NOT LIKE '#season-11-%'
                            AND `time` NOT LIKE '#season-10-%'";
            
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
                $query_pl_missing = "SELECT SUM(`pl_missing`) as `missing` FROM `smartukm_place`
                                        WHERE `season`=#season AND `pl_fylke` < 21";
            }
            
            // PL_missing
            $kommuner_array_2014 = isset( $this->kommuner[0] ) ? $this->kommuner[0] : false;
            $sql = new SQL($query_pl_missing, array('season'=>(int)$season,
                                                    'fylkeID'=>(int)$this->fylkeID,
                                                    'kommune' => $kommuner_array_2014,
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
	public function getStatArrayPerson($season) {
		if (!$this->type) return array();
		
		// Finner telling for hver bandtype (kategori)
		$qry = "SELECT `bt_id`, COUNT(*) as `count` FROM `ukm_statistics`".
				" WHERE `season` =#season AND `bt_id`>0";
				
		// finner telling for subkategorier i Scene
		$subcat_qry = "SELECT `subcat`,COUNT(*) AS `count` FROM `ukm_statistics`".
				" WHERE `season` =#season AND `bt_id` = 1";
		
		
		if ($this->type == 'kommune') {
			$qry .= " AND `k_id` IN (#kommuner)";
			$subcat_qry .= " AND `k_id` IN (#kommuner)";
			
		} else if ($this->type == 'fylke') {
			$qry .= " AND `f_id` =#fylkeID";
			$subcat_qry .=" AND `f_id` =#fylkeID";
		} else if ($this->type == 'land') {
			$qry .= " AND `f_id` < 21";
			$subcat_qry .=" AND `f_id` < 21";
		} 
		
		$qry .= " GROUP BY `bt_id` ORDER BY `bt_id` asc; "; // asc er ikke viktig.
		$subcat_qry .= " GROUP BY `subcat` ORDER BY `subcat` desc;"; // desc ER viktig!

		// stats
		$sql = new SQL($qry, array('season'=>(int)$season,
									'fylkeID'=>(int)$this->fylkeID,
									'kommuner' => implode(',', $this->kommuner)));
		$result = $sql->run();
		// var_dump($sql->debug());
		
		$array = array();
		for ($i = 1; $i <= 10; $i++) {
			$array['bt_'.$i] = 0;
		}
		
		//echo($sql->debug());
		
		while ($r = mysql_fetch_assoc($result)) {
			// var_dump($r);
			$array['bt_'.$r['bt_id']] = $r['count'];
			
			//subkategorier
			if($r['bt_id'] == 1) {
				$sql2 = new SQL($subcat_qry, array('season'=>(int)$season,
										'fylkeID'=>(int)$this->fylkeID,
										'kommuner' => implode(',', $this->kommuner)));
				$subcat_result = $sql2->run();
				//echo($sql2->debug());
				
				while ($sr = mysql_fetch_assoc($subcat_result)) {
					if ($sr['subcat'] == "")
						$array['annet'] += $sr['count'];
					else
						$array[$sr['subcat']] = $sr['count'];
				}
			} 
		}
		
		
		
		
		
		// var_dump($array);
		
		return $array;

//test-queries
// select COUNT(*),`bt_id` from `ukm_statistics` where `season` = 2009 AND `f_id` = 2 AND `bt_id` > 0 GROUP BY `bt_id` ORDER BY `bt_id` asc;
// test-spørring: select * from `ukm_statistics` where `f_id` = 2 and `season` = 2009 and `bt_id` = 1;

		// 1377		
		
	}
	
	public function getStatArrayBand($season) {
			if (!$this->type) return array();
			
			// Finner telling for hver person (kategori)
			$qry = "SELECT `bt_id`, COUNT(*) AS count FROM".
						" (SELECT `bt_id` FROM `ukm_statistics`".
						" WHERE `season` =#season AND `bt_id`>0";
					
			// finner telling for subkategorier i Scene
			$subcat_qry =	"SELECT `subcat`, COUNT(*) AS count FROM".
								" (select `subcat` from `ukm_statistics`".
								" WHERE `season` =#season AND `bt_id`=1";
			
			if ($this->type == 'kommune') {
				$qry .= " AND k_id IN #kommuner";
				$subcat_qry = " and k_id IN #kommuner";
				
			} else if ($this->type == 'fylke') {
				$qry .= " AND `f_id` =#fylkeID";
				$subcat_qry .=" AND `f_id` =#fylkeID";
			} else if ($this->type == 'land') {
				$qry .= " AND `f_id` < 21";
				$subcat_qry .=" AND `f_id` < 21";
			} 
			
			
			$qry .= 	" AND `bt_id` > 0 GROUP BY `p_id` ORDER BY `p_id` asc)".
					" AS `temp_table` GROUP BY `bt_id` ORDER BY `bt_id` asc;";
			
			$subcat_qry .= 		" GROUP BY `p_id` ORDER BY `p_id` asc)".
							" AS `temp_table` GROUP BY `subcat`".
							" ORDER BY `subcat` desc;"; // desc ER viktig!

			// stats
			$sql = new SQL($qry, array('season'=>(int)$season,
										'fylkeID'=>(int)$this->fylkeID,
										'kommuner' => implode(',', $this->kommuner)));
			$result = $sql->run();
			// var_dump($sql->debug());
			
			$array = array();
			for ($i = 1; $i <= 10; $i++) {
				$array['bt_'.$i] = 0;
			}
			
			// echo($sql->debug());
			
			while ($r = mysql_fetch_assoc($result)) {
				// var_dump($r);
				$array['bt_'.$r['bt_id']] = $r['count'];
				
				//subkategorier
				if($r['bt_id'] == 1) {
					$sql2 = new SQL($subcat_qry, array('season'=>(int)$season,
											'fylkeID'=>(int)$this->fylkeID,
											'kommuner' => implode(',', $this->kommuner)));
					$subcat_result = $sql2->run();
					// var_dump($sql->debug());
					
					while ($sr = mysql_fetch_assoc($subcat_result)) {
						if ($sr['subcat'] == "")
							$array['annet'] += $sr['count'];
						else
							$array[$sr['subcat']] = $sr['count'];
					}
				} 
			}
			
			return $array;
		}
	
	/**
	 * Oppdater statistikk for innslag
	 *
	 * @param (innslag|write_innslag) $innslag
	 * @return void
	**/
	public static function oppdater_innslag( $innslag ) {
		$sqldel = new SQLdel('ukm_statistics',
							 array('season' => $innslag->getSesong(),
							 	   'b_id' => $innslag->getId()));
		$sqldel->run();

		if( $innslag->getStatus() == 8 ) {
			foreach( $innslag->getPersoner()->getAll() as $person ) { // behandle hver person
				if( $innslag->getSubscriptionTime()->format('Y') == 1970) {
					$time = new DateTime( $innslag->getSesong() .'-01-01T00:00:01Z' );
				}
				$time = $innslag->getSubscriptionTime()->format('Y-m-d') .'T'. $innslag->getSubscriptionTime()->format('H:i:s') .'Z';
				
				$stats_info = array(
					"b_id" => $innslag->getId(), // innslag-id
					"p_id" => $person->getId(), // person-id
					"k_id" => $innslag->getKommune()->getId(), // kommune-id
					"f_id" => $innslag->getFylke()->getId(), // fylke-id
					"bt_id" => $innslag->getType()->getId(), // innslagstype-id
					"subcat" => $innslag->getKategori(), // underkategori
					"age" => $person->getAlder('') == '25+' ? 0 : $person->getAlder(''), // alder
					"sex" => $person->getKjonn(), // kjonn
					"time" =>  $time, // tid ved registrering
					"fylke" => false, // dratt pa fylkesmonstring?
					"land" => false, // dratt pa festivalen?
					"season" => $innslag->getSesong() // sesong
				);
				
				// faktisk lagre det 
				$qry = "SELECT * FROM `ukm_statistics`" .
						" WHERE `b_id` = '" . $stats_info["b_id"] . "'" .
						" AND `p_id` = '" . $stats_info["p_id"] . "'" .
						" AND `k_id` = '" . $stats_info["k_id"] . "'"  .
						" AND `season` = '" . $stats_info["season"] . "'";
				$sql = new SQL($qry);
				// Sjekke om ting skal settes inn eller oppdateres
				if (mysql_num_rows($sql->run()) > 0) {
					$sql_ins = new SQLins('ukm_statistics', array(
						"b_id" => $stats_info["b_id"], // innslag-id
						"p_id" => $stats_info["p_id"], // person-id
						"k_id" => $stats_info["k_id"], // kommune-id
						"season" => $stats_info["season"], // kommune-id
					) );
				} else {
					$sql_ins = new SQLins("ukm_statistics");
				}
				
				// Legge til info i insert-sporringen
				foreach ($stats_info as $key => $value) {
					$sql_ins->add($key, $value);
				}
				$sql_ins->run();
			}
		}
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
