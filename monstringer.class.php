<?php
require_once('UKM/sql.class.php');
require_once('UKM/monstring.class.php');
require_once('UKM/v1_monstringer.class.php');

/**
 * Fremtidig løsning (når alle er migrert bort fra v2
 * Fordi PHP ikke kan detektere static/instance context på en god måte
**/
//class stat_monstringer_v2 extends monstringer_v2
//{}
class stat_monstringer_v2 {
	/**
	 * utenGjester
	 * fjerner mønstringen for gjestekommunen
	 *
	 * @param array monstringer (fra f.eks. getAllByFylke)
	 * @return array monstringer
	**/
	public static function utenGjester( $monstringer ) {
		foreach( $monstringer as $array_pos => $monstring ) {
			if( 'kommune' != $monstring->getType() || $monstring->getAntallKommuner() == 0 ) {
				continue;
			}

			$gjestekommune = $monstring->getFylke()->getId() . '90';
			if( $monstring->harKommune( $gjestekommune ) ) {
				unset( $monstringer[ $array_pos ] );
			}
		}
		return $monstringer;
	}
	
	/**
	 * Henter ut alle lokalmønstringer fra gitt fylke, sortert alfabetisk
	 *
	 * @param object $fylke
	 * @param int $season
	 * @return array with monstring_v2-objects
	**/
	public static function getAllByFylke( $fylke, $season ) {
		$monstringer = [];
		
		if( is_numeric( $fylke ) ) {
			$fylkeId = $fylke;
		} else {
			$fylkeId = $fylke->getId();
		}
		
		$query =  monstring_v2::getLoadQry()
				."JOIN `smartukm_kommune` AS `k` ON (`k`.`id` = `kommuner`.`k_id`) 
				  WHERE `place`.`season` = '#season' 
					AND `k`.`idfylke` = '#fylke'
				  GROUP BY `place`.`pl_id` 
				  ORDER BY `place`.`pl_name` ASC";
		$qry = new SQL( $query , array('season'=>$season, 'fylke'=>$fylkeId));
		$res = $qry->run();
		if( $res ) {
			while( $row = mysql_fetch_assoc( $res ) ) {
				$monstringer[] = new monstring_v2( $row );
			}
		}
		return $monstringer;
	}

	/**
	 * getAllByFylkeSortByStart()
	 * Henter ut alle lokalmønstringer fra gitt fylke, sortert etter startdato
	 *
	 * @param object $fylke
	 * @param int $season
	 * @return array with monstring_v2-objects
	**/
	public static function getAllByFylkeSortByStart( $fylke, $season ) {
		$monstringer = [];
		
		if( is_numeric( $fylke ) ) {
			$fylkeId = $fylke;
		} else {
			$fylkeId = $fylke->getId();
		}
		
		$query =  monstring_v2::getLoadQry()
				."JOIN `smartukm_kommune` AS `k` ON (`k`.`id` = `kommuner`.`k_id`) 
				  WHERE `place`.`season` = '#season' 
					AND `k`.`idfylke` = '#fylke'
				  GROUP BY `place`.`pl_id` 
				  ORDER BY `place`.`pl_start` ASC";
		$qry = new SQL( $query , array('season'=>$season, 'fylke'=>$fylkeId));
		$res = $qry->run();
		if( $res ) {
			while( $row = mysql_fetch_assoc( $res ) ) {
				$monstringer[] = new monstring_v2( $row );
			}
		}
		return $monstringer;
	}

	/**
	 * getAllBySesong 
	 * Henter alle mønstringer fra en gitt sesong
	 *
	 * @param int $season
	 * @return array with monstring_v2-objects
	**/
	public static function getAllBySesong( $season ) {
		$monstringer = [];
		$query =  monstring_v2::getLoadQry()
				."WHERE `place`.`season` = '#season'
				  GROUP BY `place`.`pl_id` 
				  ORDER BY `place`.`pl_name` ASC";
		$qry = new SQL( $query , array('season'=>$season));
		$res = $qry->run();
		if( $res ) {
			while( $row = mysql_fetch_assoc( $res ) ) {
				$monstring = new monstring_v2( $row );
				if( $monstring->getType() == 'kommune' && $monstring->getAntallKommuner() == 0 ) {
					continue;
				}
				$monstringer[] = $monstring;
			}
		}
		return $monstringer;
	}
	
	/**
	 * Hent alle kommuner uten mønstring
	 *
	 * @param int $season
	 * @return array kommuner
	**/
	public static function getAlleKommunerUtenMonstring( $season ) {
		$kommuner = array();
		// Hent alle kommuner
		$qry = new SQL("SELECT `kommune`.`name` AS `kommune`,
							`kommune`.`id` AS `kommune_id`,
							`fylke`.`name` AS `fylke`,
							`pl_k`.`pl_id`,
							`pl_k`.`season`
						FROM `smartukm_kommune` AS `kommune`
						JOIN `smartukm_fylke` AS `fylke` ON ( `fylke`.`id` = `kommune`.`idfylke` )
						LEFT JOIN `smartukm_rel_pl_k` AS `pl_k` ON (`pl_k`.`k_id` = `kommune`.`id` AND `season` = '#season')
						WHERE `pl_id` IS NULL
						ORDER BY `kommune`.`name` ASC",
					array('season' => $season ));
		$res = $qry->run();
		while($r = mysql_fetch_assoc($res)) {
			$kommuner[] = array('id' => $r['kommune_id'], 'navn' => utf8_encode( $r['kommune'] ), 'fylke' => utf8_encode($r['fylke']) );
		}
		return $kommuner;
	}
}

class monstringer_v2 {
	var $sesong = null;
	
	public function __construct( $sesong ) {
		trigger_error('monstringer_v2 bør kalles statisk via stat_monstringer_v2', E_USER_NOTICE);
		$this->sesong = $sesong;
	}
	
	public function utenGjester( $monstringer ) {
		return stat_monstringer_v2::utenGjester( $monstringer );
	}
	
	public function getAllByFylke( $fylke ) {
		return stat_monstringer_v2::getAllByFylke( $fylke, $this->sesong );
	}

	public function getAllByFylkeSortByStart( $fylke ) {
		return stat_monstringer_v2::getAllByFylkeSortByStart( $fylke, $this->sesong );
	}
	
	public function getAllBySesong() {
		return stat_monstringer_v2::getAllBySesong( $this->sesong );
	}
	
	public function setSesong( $sesong ) {
		$this->sesong = $sesong;
		return $this;
	}
	public function getSesong() {
		return $this->sesong;
	}
	
	public static function getAlleKommunerUtenMonstring( $season ) {
		return stat_monstringer_v2::getAlleKommunerUtenMonstring( $season );
	}
	
	public static function kommune( $kommune, $season ) {
		if( is_numeric( $kommune ) ) {
			$kommune_id = $kommune;
		} elseif( 'kommune' == get_class( $kommune ) ) {
			$kommune_id = $kommune->getId();
		} else {
			throw new Exception('Kan ikke finne kommune med ugyldig parameter. Må være integer ID eller kommune-objekt');
		}
		
		$qry = new SQL("SELECT `pl_id`
						FROM `smartukm_rel_pl_k`
						WHERE `k_id` = '#kommune'
						AND `season` = '#season'",
					array('kommune'=>$kommune_id,'season'=>$season));
		$pl_id = $qry->run('field','pl_id');
		
		if( is_numeric( $pl_id ) ) {
			$monstring = new monstring_v2( $pl_id );
			if( $monstring->eksisterer() ) {
				return $monstring;
			}
		}
		$kommune = new kommune( $kommune_id );
		throw new Exception('Fant ingen mønstring for '. $kommune->getNavn() .' i '. $season );
	}
}
?>
