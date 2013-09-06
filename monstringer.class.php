<?php
	class monstringer{
		public function monstringer($season=false){
			$this->season = $season;
		}
		
		public function etter_sesong($season){/* returnerer en liste over alle places i for et gitt år */
			$query ="SELECT `pl_id`, `pl_name`
					 FROM `smartukm_place`
					 WHERE `season` = '#season'
					 ORDER BY `pl_name` ASC";
			$qry = new SQL($query, array('season'=>$season));
			return $qry->run();
			#return $wpdb->get_col($query);	
		}
		
		public function selectArray($sesong) {
			$monstringer = $this->etter_sesong($sesong);
			while($r = mysql_fetch_assoc($monstringer))
				$places[$r['pl_id']] = utf8_encode($r['pl_name']);
			return $places;
		}
		
		public function kommuneliste($fylke, $season) {
			$qry = new SQL("SELECT `t_k`.`name` AS `kommune`,
								   `rel`.`pl_id`
							FROM `smartukm_kommune` AS `t_k`
							JOIN `smartukm_rel_pl_k` AS `rel` ON (`t_k`.`id` = `rel`.`k_id`)
							WHERE `t_k`.`idfylke` = '#fylke'
							AND `season` = '#season'
							GROUP BY `t_k`.`id`
							ORDER BY `t_k`.`name` ASC",
							array('fylke'=>$fylke, 'season'=>$season));
			$res = $qry->run();

			while($r = mysql_fetch_assoc($res))
				$liste[utf8_encode($r['kommune'])] = utf8_encode($r['pl_id']);
			
			return $liste;
		}
		
		
		public function etter_kommune(){/* returnerer en liste med alle kommune-mønstringer */
			$query ="SELECT `pl_id`, `pl_name`
					 FROM `smartukm_place`
					 WHERE `season` = '#season'
					 AND `pl_fylke` = 'kommune'
					 ORDER BY `pl_name` ASC";
			$qry = new SQL($query, array('season'=>$this->season));
			return $qry->run();
			#return $wpdb->get_col($query);	
		}
		
	}
?>