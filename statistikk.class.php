<?php

use UKMNorge\Database\SQL\Delete;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Innslag\Innslag;
use UKMNorge\Arrangement\Arrangement;


require_once 'UKM/Autoloader.php';

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
           $query_persons = "SELECT count(`stat_id`) as `persons` FROM `ukm_statistics_from_2024`
                                WHERE `season`='#season'
                                AND `f_id` < 21
								AND `innslag_status` = 8
                                AND `time` NOT LIKE '#season-11-%'
                                AND `time` NOT LIKE '#season-10-%'";
            $query_bands = "SELECT COUNT(DISTINCT `b_id`) as `bands` FROM `ukm_statistics_from_2024` 
                            WHERE `season`='#season'
                            AND `f_id` < 21
							AND `innslag_status` = 8
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
                
                $sql = new Query("SELECT SUM(`pl_missing`) as `missing` FROM `smartukm_place`
                                        WHERE `season`= #season 
                                        AND `pl_owner_fylke` = #fylkeID
                                        ",
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
                                        WHERE `season`=#season AND `pl_owner_fylke` < 21";
            }
            
            // PL_missing
            $kommuner_array_2014 = isset( $this->kommuner[0] ) ? $this->kommuner[0] : false;
            $sql = new Query($query_pl_missing, array('season'=>(int)$season,
                                                    'fylkeID'=>(int)$this->fylkeID,
                                                    'kommune' => $kommuner_array_2014,
                                                    'kommuner' => implode(',', $this->kommuner)));

            $missing += (int)$sql->run('field', 'missing');
            
            // Persons
            $sql = new Query($query_persons, array('season'=>(int)$season,
                                                 'fylkeID'=>(int)$this->fylkeID,
                                                 'kommuner' => implode(',', $this->kommuner)));
            $persons = (int)$sql->run('field', 'persons');
            $persons += $missing;
            
            // Bands
            $sql = new Query($query_bands, array('season'=>(int)$season,
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
		$qry = "SELECT `bt_id`, COUNT(*) as `count` FROM `ukm_statistics_from_2024`".
				" WHERE `season` =#season AND `bt_id`>0 AND `innslag_status` = 8";
				
		// finner telling for subkategorier i Scene
		$subcat_qry = "SELECT `subcat`,COUNT(*) AS `count` FROM `ukm_statistics_from_2024`".
				" WHERE `season` =#season AND `bt_id` = 1 AND `innslag_status` = 8";
		
		
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
		$sql = new Query($qry, array('season'=>(int)$season,
									'fylkeID'=>(int)$this->fylkeID,
									'kommuner' => implode(',', $this->kommuner)));
		$result = $sql->run();
		// var_dump($sql->debug());
		
		$array = array();
		for ($i = 1; $i <= 10; $i++) {
			$array['bt_'.$i] = 0;
		}
		
		//echo($sql->debug());
		
		while ($r = Query::fetch($result)) {
			// var_dump($r);
			$array['bt_'.$r['bt_id']] = $r['count'];
			
			//subkategorier
			if($r['bt_id'] == 1) {
				$sql2 = new Query($subcat_qry, array('season'=>(int)$season,
										'fylkeID'=>(int)$this->fylkeID,
										'kommuner' => implode(',', $this->kommuner)));
				$subcat_result = $sql2->run();
				//echo($sql2->debug());
				
				while ($sr = Query::fetch($subcat_result)) {
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
// select COUNT(*),`bt_id` from `ukm_statistics_from_2024` where `season` = 2009 AND `f_id` = 2 AND `bt_id` > 0 GROUP BY `bt_id` ORDER BY `bt_id` asc;
// test-spørring: select * from `ukm_statistics_from_2024` where `f_id` = 2 and `season` = 2009 and `bt_id` = 1;

		// 1377		
		
	}
	
	public function getStatArrayBand($season) {
			if (!$this->type) return array();
			
			// Finner telling for hver person (kategori)
			$qry = "SELECT `bt_id`, COUNT(*) AS count FROM".
						" (SELECT `bt_id` FROM `ukm_statistics_from_2024`".
						" WHERE `season` =#season AND `bt_id`>0 AND `innslag_status` = 8";
					
			// finner telling for subkategorier i Scene
			$subcat_qry =	"SELECT `subcat`, COUNT(*) AS count FROM".
								" (select `subcat` from `ukm_statistics_from_2024`".
								" WHERE `season` =#season AND `bt_id`=1 AND `innslag_status` = 8";
			
			if ($this->type == 'kommune') {
				$qry .= " AND k_id IN (#kommuner)";
				$subcat_qry = " and k_id IN (#kommuner)";				
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
			$sql = new Query($qry, array('season'=>(int)$season,
										'fylkeID'=>(int)$this->fylkeID,
										'kommuner' => implode(',', $this->kommuner)));
			$result = $sql->run();
			// var_dump($sql->debug());
			
			$array = array();
			for ($i = 1; $i <= 10; $i++) {
				$array['bt_'.$i] = 0;
			}
			
			// echo($sql->debug());
			
			while ($r = Query::fetch($result)) {
				// var_dump($r);
				$array['bt_'.$r['bt_id']] = $r['count'];
				
				//subkategorier
				if($r['bt_id'] == 1) {
					$sql2 = new Query($subcat_qry, array('season'=>(int)$season,
											'fylkeID'=>(int)$this->fylkeID,
											'kommuner' => implode(',', $this->kommuner)));
					$subcat_result = $sql2->run();
					// var_dump($sql->debug());
					
					while ($sr = Query::fetch($subcat_result)) {
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
	 * @param (Innslag) $innslag
	 * @return void
	**/
	public static function oppdater_innslag(Innslag $innslag, ?Arrangement $tilArrangement = null) {
		/*
			Oppdater (sletting) innslag bare hvis arrangementet ikke er ferdig. 
			OBS: Innslag eller personer som blir lagt til etter at arrangementet er ferdig skal telles med i statistikk.
			Statistikk må ikke påvirkes av endringer som skjer etter at arrangementet ble utført. For eksempel, fjerning av deltakere senere skal ikke gjenspeilses i statistikk.
			OBS: Innslag og deltakere som legges til senere i et ferdig arrangement skal telles med i statistikk. Dette gjøres fordi noen av deltakere kan registreres etterpå pga videresending.
		*/
		$homeArrangement = $innslag->getHome();
		$currentArrangement = null;

		if($tilArrangement) {
			$currentArrangement = $tilArrangement;
		}
		else if($innslag->getContext() && $innslag->getContext()->getMonstring()) {
			try{
				$currentArrangement = new Arrangement($innslag->getContext()->getMonstring()->getId());
			}catch(Exception $e) {
				$currentArrangement = $homeArrangement;
			}
		}
		else {
			$currentArrangement = $homeArrangement;
		}
		
		// Innslag is being edited in another arrangement (videresending) or home arrangement
		$isHomeArrangement = null;
		if($currentArrangement == null) {
			$isHomeArrangement = true;
		} else {
			$isHomeArrangement = $homeArrangement->getId() == $currentArrangement->getId();
		}
		
		$nyDeltakerIFerdigArrangement = false;

		// Hvis arrangementet er ferdig og det redigeres fra home arrangement, ikke oppdater statistikk
		if($isHomeArrangement && $homeArrangement->erFerdig()) {
			// Sjekk hvis ny deltaker legges til i et ferdig arrangement
			$nyDeltakerIFerdigArrangement = static::nyInnslagEllerPersonOppdagelse($innslag, $homeArrangement, $currentArrangement);

			// Sjekk om det er et nytt innslag eller en ny person i et eksisterende innslag som er lagt til
			if(!$nyDeltakerIFerdigArrangement) {
				// Nytt innslag eller ny deltaker ikke funnet
				return;
			}
		}
		// Hvis arrangementet er ferdig og det redigeres fra et annet arrangement (videresending), ikke oppdater statistikk
		if(!$isHomeArrangement && $currentArrangement->erFerdig()) {
			// Sjekk hvis ny deltaker legges til i et ferdig arrangement
			$nyDeltakerIFerdigArrangement = static::nyInnslagEllerPersonOppdagelse($innslag, $homeArrangement, $currentArrangement);

			if(!$nyDeltakerIFerdigArrangement) {
				// Nytt innslag eller ny deltaker ikke funnet
				return;
			}
		}

		// Hvis ny deltaker, slett er ikke nødvendig.
		// Dette for å unngå å slette deltakere som har vært med i innslaget (og som potensielt har meldt seg av senere) og en ny deltaker legges til etter at arrangementet er ferdig.
		// Ny deltaker i et ferdig arrangement - registreres
		// Slettede deltakere i et ferdig arrangement - registreres IKKE
		if(!$nyDeltakerIFerdigArrangement) {
			$dbArray = array(
				'season' => $innslag->getSesong(),
				'b_id' => $innslag->getId(),
				'pl_id' => $isHomeArrangement ? $homeArrangement->getId() : $currentArrangement->getId(), 
				   'pl_id_home' => $homeArrangement->getId(),
			);
	
			$delete = new Delete('ukm_statistics_from_2024', $dbArray);
			$delete->run();
		}

		$videresending = $isHomeArrangement ? 0 : 1;
		if($currentArrangement->erSingelmonstring()) {
			$kommune = $currentArrangement->getKommune()->getId();
		}else {
			$kommune = $innslag->getKommune()->getId();
		}

		$fylke = $isHomeArrangement ? $homeArrangement->getFylke()->getId() : $currentArrangement->getFylke()->getId();
		
		// if( $innslag->getStatus() == 8) {
			foreach( $innslag->getPersoner()->getAll() as $person ) { 
				// behandle hver person og sjekk om de er videresendt
				// hvis videresendt, sjekk at de er påmeldt det til arrangementet de videresendes til. Det er mulig å videresende et innslag uten at alle personene er påmeldt.
				// Sjekker også at innslagtypen er ikke enkeltperson. Hvis det er enkeltperson, så skal personen alltid telles med.
				if($videresending && !$innslag->getType()->erEnkeltPerson() && !$person->erPameldt($currentArrangement->getId())) {
					continue;
				}
				
				if( $innslag->getSubscriptionTime()->format('Y') == 1970) {
					$time = new DateTime( $innslag->getSesong() .'-01-01T00:00:01Z' );
				}
				$time = $innslag->getSubscriptionTime()->format('Y-m-d') .'T'. $innslag->getSubscriptionTime()->format('H:i:s') .'Z';
				
				$stats_info = array(
					"b_id" => $innslag->getId(), // innslag-id
					"p_id" => $person->getId(), // person-id
					"k_id" => $kommune, // kommune-id
					"f_id" => $fylke, // fylke-id
					"bt_id" => $innslag->getType()->getId(), // innslagstype-id
					"subcat" => $innslag->getType()->getNavn(), // underkategori
					"b_kategori" => $innslag->getKategori(), // innslag kategori
					"age" => $person->getAlder('') == '25+' ? 0 : $person->getAlder(''), // alder
					"sex" => '', //$person->getKjonn(), // kjønn lagres ikke i statistikk pga personvern
					"time" =>  $time, // tid ved registrering
					"fylke" => $currentArrangement->getType() == 'fylke', // fylkesmonstring?
					"land" => $currentArrangement->getType() == 'land', // festivalen?
					"season" => $innslag->getSesong(), // sesong
					"pl_id_home" => $homeArrangement->getId(), // home pl_id
					"pl_id" => $currentArrangement->getId(), // current pl_id
					"videresending" => $videresending,
					"innslag_status" => $innslag->getStatus() == 6 ? 8 : $innslag->getStatus(), // Hvis status er "6 - Påmeldt" bytt til "8 - Fullført" for statistikken. Status 6 innebarer at innslaget har påmeldte deltakere. Se Mangler.php 
					"p_date_of_birth" => $person->getFodselsdato(),
					"p_firstname" => $person->getFornavn(),
				);
				
				// faktisk lagre det 
				$qry = "SELECT * FROM `ukm_statistics_from_2024`" .
						" WHERE `b_id` = '" . $stats_info["b_id"] . "'" .
						" AND `p_id` = '" . $stats_info["p_id"] . "'" .
						" AND `k_id` = '" . $stats_info["k_id"] . "'"  .
						" AND `pl_id_home` = '" . $stats_info["pl_id_home"] . "'"  .
						" AND `pl_id` = '" . $stats_info["pl_id"] . "'"  .
						" AND `videresending` = '" . $stats_info["videresending"] . "'"  .
						" AND `season` = '" . $stats_info["season"] . "'";
				$sql = new Query($qry);
				// Sjekke om ting skal settes inn eller oppdateres
				if (Query::numRows($sql->run()) > 0) {
					$sql_ins = new Update('ukm_statistics_from_2024', array(
						"b_id" => $stats_info["b_id"], // innslag-id
						"p_id" => $stats_info["p_id"], // person-id
						"k_id" => $stats_info["k_id"], // kommune-id
						"pl_id_home" => $stats_info["pl_id_home"], // home pl_id
						"pl_id" => $stats_info["pl_id"], // current pl_id
						"videresending" => $stats_info["videresending"], // er innslaget videresendt?
						"season" => $stats_info["season"], // kommune-id
						"innslag_status" => $stats_info["innslag_status"],
						"date_of_birth" => $stats_info["p_date_of_birth"],
					) );
				} else {
					$sql_ins = new Insert("ukm_statistics_from_2024");
				}
				
				// Legge til info i insert-sporringen
				foreach ($stats_info as $key => $value) {
					$sql_ins->add($key, $value);
				}
				$sql_ins->run();
			}
		// }
	}

	/**
	 * 
	 *
	 * @param Arrangement $arrangement
	 * @param Innslag $innslag
	 * @return void
	 */
	public static function avmeldVideresending(Innslag $innslag, Arrangement $tilArrangement) {
		foreach(Innslag::getById($innslag->getId())->getTitler()->getAll() as $tittel) {
			// Hvis det finnes en tittel som er videresend, da skal personene ikke fjernes fra statistikken
			if($tittel->erPameldt($tilArrangement->getId())) {
				return;
			}
		}
		
		$dbArray = array(
			'season' => $innslag->getSesong(),
			'b_id' => $innslag->getId(),
	   		'pl_id' => $tilArrangement->getId(),
			'videresending' => '1'
		);

		$delete = new Delete('ukm_statistics_from_2024', $dbArray);
		$delete->run();
	}
	
	
	private function _load($season) {
                $kommuneList = implode(', ', $this->kommuner);
		
		$sql = new Query("SELECT * FROM `ukm_statistics_from_2024`
						WHERE `k_id` IN (#kommuneList)
						AND `season` = '#season' AND `innslag_status` = 8",
						array('kommuneList' => $kommuneList, 'season' => $season));
		$res = $sql->run('array');
		               
//		var_dump($res);
	}
	
	public function getTargetGroup($season) {
		return rand(1000,3000);
	}

	// Sjekker hvis det er et nytt innslag eller en ny person i et eksisterende innslag som er lagt til
	private static function nyInnslagEllerPersonOppdagelse($innslag, $homeArrangement, $currentArrangement) {
		if($currentArrangement == null) {
			// redigering skjer i home arrangement
			$currentArrangement = $homeArrangement;
		}
		if($currentArrangement->erSingelmonstring()) {
			$kommune = $currentArrangement->getKommune()->getId();
		}else {
			$kommune = $innslag->getKommune()->getId();
		}		

		$videresending = $homeArrangement->getId() == $currentArrangement->getId() ? 0 : 1;

		$qry = "SELECT p_id FROM `ukm_statistics_from_2024`" .
				" WHERE `b_id` = '" . $innslag->getId() . "'" .
				" AND `k_id` = '" . $kommune . "'"  .
				" AND `pl_id_home` = '" . $homeArrangement->getId() . "'"  .
				" AND `pl_id` = '" . $currentArrangement->getId() . "'"  .
				" AND `videresending` = '" . $videresending . "'";
		$sql = new Query($qry);

		$result = $sql->run();

		// Sjekk om innslaget finnes i statistikken, hvis ikke, da er det et nytt innslag som kan legges til
		if (Query::numRows($result) < 1) {
			return true;
		}

		// Bygg sett med eksisterende p_id i statistikken
		$eksisterende = [];
		while ($r = Query::fetch($result)) {
			$eksisterende[$r['p_id']] = true;
		}
		

		// Finnes det en person i innslaget som ikke finnes i statistikken?
		foreach ($innslag->getPersoner()->getAll() as $person) {
			if (!isset($eksisterende[$person->getId()])) {
				// die("FOUND NEW PERSON\n");
				return true; // personen er ikke lagt til i statistikken enda
			}
		}

		return false; // innslag finnes og ingen nye personer
	}
}
?>
