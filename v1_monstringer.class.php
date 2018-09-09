<?php
class monstringer{
	public function monstringer($season=false){
		$this->season = $season;
	}
	public function alle_kommuner_med_lokalmonstringer($inkluder_testfylke=false) {
		$fylkelimit = $inkluder_testfylke ? 22 : 21;
		$query = "SELECT `pl`.`pl_id`,
						   `pl`.`pl_name`,
						   `kommune`.`id` AS `k_id`,
						   `kommune`.`name` AS `k_name`,
						   `fylke`.`id` AS `f_id`,
						   `fylke`.`name` AS `f_name`
						   
					FROM `smartukm_kommune` AS `kommune`
					JOIN `smartukm_fylke` AS `fylke` ON (`fylke`.`id` = `kommune`.`idfylke`)
					
					JOIN `smartukm_rel_pl_k` AS `rel_pl_k` ON (`rel_pl_k`.`k_id` = `kommune`.`id`)
					JOIN `smartukm_place` AS `pl` ON (`pl`.`pl_id` = `rel_pl_k`.`pl_id`)
					
					WHERE `rel_pl_k`.`season` = '#season' 
					AND `fylke`.`id` < #fylkelimit 
					AND `kommune`.`id` < '3002'
					AND `kommune`.`name` != 'Gjester'
					ORDER BY `fylke`.`name` ASC, `kommune`.`name` ASC";
		$query = new SQL( $query, array('season'=>$this->season, 'fylkelimit'=>$fylkelimit) );
		$res = $query->run();
		//echo $query->debug();
		$list = array();
		$monstringer = array();
		
		while( $row = SQL::fetch( $res ) ) {
			// Fylket er ikke lagt til i listen
			if( !isset( $list[ $row['f_id'] ] ) ) {
				$fylke = new stdClass();
				$fylke->id = $row['f_id'];
				$fylke->name = utf8_encode( $row['f_name'] );
				$fylke->monstringer = array();

				$list[ $row['f_id'] ] = $fylke;
			// Fylket er allerede lagt til i listen
			} else {
				$fylke = $list[ $row['f_id'] ];
			}
			
			// Mønstringen er ikke tidligere registrert i fylket
			if( !isset( $fylke->monstringer[ $row['pl_id'] ] ) ) {
				$monstring = new stdClass();
				if( in_array($row['pl_id'], $monstringer) ) {
					$monstring->fellesmonstring = true;
				} else { 
					$monstring->fellesmonstring = false;
				}
				$monstring->id = $row['pl_id'];
				$monstring->k_id = $row['k_id'];
				$monstring->name = utf8_encode($row['pl_name']);
				$monstring->kommuner = array();
				
				$fylke->monstringer[ $row['pl_id'] ] = $monstring;
			} else {
				$monstring = $fylke->monstringer[ $row['pl_id'] ];
				$monstring->fellesmonstring = true;
				$monstring->k_id = false;
			}
			
			$monstringer[] = $row['pl_id'];
			$monstring->kommuner[ $row['k_id'] ] = utf8_encode( $row['k_name']);
		}
		
		return $list;
	}

	
	public function etter_sesong($season=false){/* returnerer en liste over alle places i for et gitt år */
		if(!$season) {
			$season = $this->season;
		}
		$query ="SELECT `pl_id`, `pl_name`
				 FROM `smartukm_place`
				 WHERE `season` = '#season'
				 ORDER BY `pl_name` ASC";
		$qry = new SQL($query, array('season'=>$season));
		return $qry->run();
		#return $wpdb->get_col($query);	
	}
	
	public function selectArray($sesong=false) {
		if(!$sesong) {
			$sesong = $this->season;
		}
		$monstringer = $this->etter_sesong($sesong);
		while($r = SQL::fetch($monstringer))
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

		while($r = SQL::fetch($res))
			$liste[utf8_encode($r['kommune'])] = utf8_encode($r['pl_id']);
		
		return $liste;
	}
	
	
	public function etter_kommune(){/* returnerer en liste med alle kommune-mønstringer */
		$query ="SELECT `pl_id`, `pl_name`
				 FROM `smartukm_place`
				 WHERE `season` = '#season'
				 AND `pl_fylke` = '0'
				 ORDER BY `pl_name` ASC";
		$qry = new SQL($query, array('season'=>$this->season));
		return $qry->run();
		#return $wpdb->get_col($query);	
	}
	
	public function etter_kommune_array() {
		$res = $this->etter_kommune();
		while($r = SQL::fetch($res)) {
			$liste[$r['pl_id']] = utf8_encode($r['pl_name']);
		}
		return $liste;
	}
	
	public function etter_fylke(){/* returnerer en liste med alle fylkesmønstringer */
		$query ="SELECT `pl_id`, `pl_name`
				 FROM `smartukm_place`
				 WHERE `season` = '#season'
				 AND `pl_fylke` != '0'
				 ORDER BY `pl_name` ASC";
		$qry = new SQL($query, array('season'=>$this->season));
		return $qry->run();
		#return $wpdb->get_col($query);	
	}

	public function etter_fylke_array() {
		$res = $this->etter_fylke();
		while($r = SQL::fetch($res)) {
			$liste[$r['pl_id']] = utf8_encode($r['pl_name']);
		}
		return $liste;
	}
	
	public function antall_uregistrerte() {
		if( !$this->season ) {
			throw new Exception('Requires $season-parameter @ __construct');
		}
		$query ="SELECT COUNT(`pl_id`) AS `count`
				 FROM `smartukm_place`
				 WHERE `season` = '#season'
				 AND `pl_start` = '0'";
		$qry = new SQL($query, array('season'=>$this->season));

		return $qry->run('field', 'count');
	}
	public function antall_registrerte() {
		if( !$this->season ) {
			throw new Exception('Requires $season-parameter @ __construct');
		}
		$query ="SELECT COUNT(`pl_id`) AS `count`
				 FROM `smartukm_place`
				 WHERE `season` = '#season'
				 AND `pl_start` > '0'";
		$qry = new SQL($query, array('season'=>$this->season));

		return $qry->run('field', 'count');
	}
}
