<?php

namespace UKMNorge\API\SSB;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Database\SQL\Write;

use stdClass;

require_once('UKM/Autoloader.php');

class KommuneAreal extends SSB {
	public $year;
	public $table = '09280';

	public function buildQuery() {
		$this->setResource('table/'.$this->table);

		$this->addQueryParameter("Region", "item", $this->_getAllKommuner());
		$this->addQueryParameter("Arealtype", "all", array());
		$this->addQueryParameter("ContentsCode", "item", array("Areal1"));
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

	public function getLatestYearUpdated() {
        $years = $this->getAllYears();
        if( !is_array( $years ) || sizeof( $years ) == 0) {
            return 'ukjent';
        }
		return max($years);
	}

	public function getAllYears() {
		$sql = new Query("DESCRIBE ssb_kommune_areal");

		$res = $sql->run();
		$years = array();
		while($row = Query::fetch($res)) {
			if(is_numeric($row['Field'])) {
				$years[] = $row['Field'];
			}
		}
		sort($years);
		return $years;
		# Test:
		#return array(2000, 2012);
	}

	# Returnerer et array med kommune-ID som nøkkel for hver verdi.
	public function getDataFromKommuneResult($results) {
		$kommunedata = array();
		// For hver kommune
		foreach($results->dataset->dimension->Region->category->index as $k_id => $position) {
			$kommunedata[$k_id] = $results->dataset->value[$position];
		}
		return $kommunedata;
	}

	# Adds a year-column for the selected year.
	public function addYearColumn($year) {
		require_once('UKM/sql.class.php');
		$sql = new Write(
			'ALTER TABLE ssb_kommune_areal ADD `#year` DOUBLE(8,3) NOT NULL',
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
		$log[] = "Oppdaterer databasen...";
		#var_dump($kommunedata);
		if(null == $kommunedata || null == $year) {
			return "Kunne ikke hente data for år ".$year.".";
		}
		foreach ($kommunedata as $k_id => $areal) {
			if(null == $k_id || null === $areal) {
				var_dump($k_id);
				var_dump($areal);
				return "Kan ikke oppdatere uten k_id eller areal!";
			}
			$qry = new Update('ssb_kommune_areal', array('kommune_id' => $k_id));	
			$qry->add($year, $areal);
			$res = $qry->run();
			
			$log_entry = new stdClass();
			$log_entry->id = $k_id;
			$log_entry->antall = $antall;
			if( !$res && $qry->error()) {	
				$log_entry->success = false;
				$log_entry->message = $qry->error() . ' med areal '.$areal . ' og year = '.$year;
				#$log_entry->message = $qry->debug();
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

	private function addMissingKommuner($kommunedata) {
		$log = array();
		$log[] = 'Finner kommuner som mangler fra tabellen.';

		$qry = new Query("SELECT * FROM ssb_kommune_areal");
		$res = $qry->run();
		$kommuner = [];
		while ($row = Query::fetch($res)) {
			$kommuner[$this->getSSBifiedKommuneID($row['kommune_id'])] = $row['kommune_navn'];
		}

		$missing = array_diff_key($kommunedata, $kommuner);
		
		if(empty($missing)) {
			$log[] = '<b>Ingen kommuner mangler fra ssb_kommune_areal-tabellen, fortsetter...</b>';
			return $log;
		}
		else {
			$log[] = '<b>Følgende kommuner mangler fra ssb_kommune_areal-tabellen:</b>';
			foreach($missing as $id => $val) 
				$log[] = 'ID: '.$id .' - importerer.';
			#$log[] = var_export($missing, true);
		}	

		## Finn navn og fylke på manglende kommuner
		$qry = new Query("SELECT * FROM smartukm_kommune");
		$res = $qry->run();
		$kommuneListe = [];
		$fylkeListe = [];
		while($row = Query::fetch($res)) {
			$id = $this->getSSBifiedKommuneID($row['id']);
			$kommuneListe[$id] = $row['name'];
			$fylkeListe[$id] = $row['idfylke'];
		}

		$missing2 = array_diff_key($kommunedata, $kommuneListe);
		if(!empty($missing2)) {
			$log[] = '<b>Kommuner fra SSB som ikke finnes i smartukm_kommune: </b>';
			foreach($missing2 as $id => $val) 
				$log[] = 'ID: '.$id;
		}

		## Legg til manglende i databasen!
		foreach($missing as $k_id => $value) {
			$sql = new Insert("ssb_kommune_areal");
			$sql->add("kommune_id", (int)$k_id);
			if(isset($fylkeListe[$k_id]))
				$sql->add("fylke_id", $fylkeListe[$k_id]);
			if(isset($kommuneListe[$k_id])) 
				$sql->add("kommune_navn", $kommuneListe[$k_id]);
			$sql->run();
		}

		return $log;
	}

	function getData($year) {
		$this->year = $year;

		$this->year = $year;
		$this->buildQuery();

		$result = $this->run();
		
		$kommunedata = $this->getDataFromKommuneResult($result);
		$log = $this->addMissingKommuner($kommunedata);

		if(!in_array($year, $this->getAllYears())) {
			// Dobbeltsjekk at vi har mottatt data for dette året før vi gjør dette.
			if(!empty($kommunedata))
				$this->addYearColumn($year);
		}

		$log[] = $this->updateDb($kommunedata, $year);
		return $log;
	}
}