<?php

namespace UKMNorge\API\SSB;

use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Write;

use stdClass;

require_once('UKM/Autoloader.php');

class Levendefodte extends SSB {

	public $year;
	public $table = '04231';

	public function buildQuery() {
		$this->setResource('table/'.$this->table);

		$this->addQueryParameter("Region", "item", $this->_getAllKommuner());
		$this->addQueryParameter("Kjonn", "all", array()); # La SSB summere summene for kjønn for oss :D
		$this->addQueryParameter("ContentsCode", "item", array("Levendefodte"));
		$this->addQueryParameter("Tid", "item", array($this->year));
		$this->addResponseFormat("json-stat");

	}

	# Returnerer et array av kommuneIDer
	private function _getAllKommuner() {
		$qry = new Query("SELECT id FROM smartukm_kommune");
		$res = $qry->run();
		$kommuner = array();
		while ($row = Query::fetch($res)) {
			$kommuner[] = $this->getSSBifiedKommuneId($row['id']);
		}
		return $kommuner;
		# Test:
		#return array($this->getSSBifiedKommuneId(104),$this->getSSBifiedKommuneId(105));
	}

	public function getAllYears() {
		require_once('UKM/sql.class.php');
	
		$sql = new Query("DESCRIBE ukm_befolkning_ssb");
	
		$res = $sql->run();
		$years = array();
		while($row = Query::fetch($res)) {
			if(is_numeric($row['Field'])) {
				$years[] = $row['Field'];
			}
		}
		sort($years);
		return $years;
	}

	// Finner kun manglende år mellom siste og første.
	public function getMissingYears() {
		$years = $this->getAllYears();
		$max = max($years);
		$min = min($years);
		$missing = array();
		
		for ($year = $min; $year < $max; $year++) {
			if(!in_array($year, $years)) {
				$missing[] = $year;
			}
		}

		return $missing;
	}

	public function getLatestYearUpdated() {
		$years = $this->getAllYears();
		return max($years);
	}

	# Returnerer et array med kommune-ID som nøkkel og antall levendefødte som verdi.
	public function getDataFromKommuneResult($results) {
		$kommunedata = array();
        // For hver kommune
		foreach($results->dataset->dimension->Region->category->index as $k_id => $position) {
			$kommunedata[$k_id] = $results->dataset->value[$position];
		}
		/*# Numeriske nøkler
		foreach($results->dataset->value as $key, $value) {
			$k_id = 
			$kommunedata[] 
		}*/
		#var_dump($kommunedata);
		return $kommunedata;
	}

	# Adds a year-column for the selected year.
	public function addYearColumn($year) {
		$sql = new Write(
			'ALTER TABLE ukm_befolkning_ssb ADD `#year` INTEGER NOT NULL',
			[
				'year' => (int)$year
			]
		);
		
		$res = $sql->run();
		if( !$res ) {
			return $sql->getError();
		}
		return true;
	}

	# Returnerer true ved suksess, eller et array med objekter som beskriver elementene som mislyktes.
	public function updateDb($kommunedata, $year) {
		$log = array();
		if(null == $kommunedata || null == $year) {
			return "Kunne ikke hente data for år ".$year.".";
		}
		foreach ($kommunedata as $k_id => $antall) {
			if(null == $k_id || null === $antall) {
				var_dump($k_id);
				var_dump($antall);
				return "Kan ikke oppdatere uten k_id eller antall!";
			}
			$qry = new Insert('ukm_befolkning_ssb', array('kommune_id' => $k_id));	
			$qry->add($year, $antall);
			$res = $qry->run();
			
			$log_entry = new stdClass();
			$log_entry->id = $k_id;
			$log_entry->antall = $antall;
			if( !$res && $qry->error()) {	
				$log_entry->success = false;
				$log_entry->message = $qry->error();
			} elseif( !$res ) {
				$log_entry->success = true;
				$log_entry->message = 'Ingen endring.';
			} 
			else {
				$log_entry->success = true;
				$log_entry->message = 'Suksess!';
			}
			$log[] = $log_entry;
			// Resett SQL-objekt for minnesparing?
			$qry = null;
		}

		if(empty($log))
			return true;
		return $log;
	}

	public function getData($year) {
		$this->year = $year;
		$this->buildQuery();
		/*echo '<pre>';
		echo $levendefodte->buildQuery();
		echo '</pre>';*/
	
		$result = $this->run();
		
		/*echo '<pre>';
		echo var_dump($result);
		echo '</pre>';	*/
		$kommunedata = $this->getDataFromKommuneResult($result);
		#var_dump($kommunedata);
	
		if(!in_array($year, $this->getAllYears())) {
			// Dobbeltsjekk at vi har mottatt data for dette året før vi gjør dette.
			if(!empty($kommunedata))
				$this->addYearColumn($year);
		}
	
		$log = $this->updateDb($kommunedata, $year);
		return $log;
	}
}