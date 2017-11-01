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
				$monstringer[] = new monstring_v2( $row );
			}
		}
		return $monstringer;
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
}
?>
