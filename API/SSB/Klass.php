<?php

namespace UKMNorge\API\SSB;

use Exception, DateTime;
use UKMCURL;

require_once('UKM/curl.class.php');
require_once('UKM/Autoloader.php');

class Klass implements KlassInterface {
	private $classificationId = null;
	private $start = null;
	private $stop = null;
	private $includeFutureChanges = "false";

	public function setClassificationId($classificationId) {
		$this->classificationId = $classificationId;
	}

	public function getCodes($debug = false) {
		if(null == $this->classificationId) {
			throw new Exception("Kan ikke kjøre en Klass-spørring mot ukjent klassifisering.");
		}

		if(null == $this->start || null == $this->stop || $this->start > $this->stop) {
			throw new Exception("Klass-spørring for koder krever en gyldig dato-range. Kjør setRange()");
		}

		$url = self::API_URL . $this->classificationId . "/codes?";

		$url .= "from=".$this->start->format("Y-m-d");
		$url .= "&to=" .$this->stop->format("Y-m-d");
		$url .= "&includeFuture=" .$this->includeFutureChanges;

		$curl = new UKMCURL();
		$curl->addHeader("Accept: application/json; charset: UTF-8");
		return $curl->request($url);
	}

	public function includeFutureChanges(bool $include) {
		$this->includeFutureChanges = $include ? "true" : "false";
	}

	public function getChanges($debug = false) {
		if(null == $this->classificationId) {
			throw new Exception("Kan ikke kjøre en Klass-spørring mot ukjent klassifisering.");
		}

		if(null == $this->start || null == $this->stop || $this->start > $this->stop) {
			throw new Exception("Klass-spørring for koder krever en gyldig dato-range. Kjør setRange()");
		}

		$url = self::API_URL . $this->classificationId . "/changes?";

		$url .= "from=".$this->start->format("Y-m-d");
		$url .= "&to=" .$this->stop->format("Y-m-d");
		$url .= "&includeFuture=".$this->includeFutureChanges;

		$curl = new UKMCURL();
		$curl->addHeader("Accept: application/json; charset: UTF-8");
		return $curl->request($url);
	}

	public function setRange(DateTime $start, DateTime $stop) {
		$this->start = $start;
		$this->stop = $stop;
	}
	
	public function run() {
		if(null == $this->classificationId) {
			throw new Exception("Kan ikke kjøre en Klass-spørring mot ukjent klassifisering.");
		}

		# Build full API-url
		$url = self::API_URL . $this->classificationId;

		$curl = new UKMCURL();
		$curl->request($url);
		$result = $curl->process($url);
		return $result;
	}

	# SSB krever at kommune-ID er et firesifret tall med fylkes_id og et kommunetall, med 0-padding for hvert ènsifrede tall (i.e. 0104 = Moss). 
	# Vår kommune-ID paddes med 0 i front til ett firesifret tall, da det allerede er fylke_id.kommunetall med 0 der nødvendig internt.
	public function getSSBifiedKommuneID($k_id) {
		$k_id = (string)$k_id;
		if(strlen($k_id) < 4)
			$k_id = str_pad($k_id, 4, '0', STR_PAD_LEFT);	
		return $k_id;
	}

}