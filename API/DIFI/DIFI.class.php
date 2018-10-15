<?php

require_once('UKM/curl.class.php');

class DIFI {
	const API_URL = 'http://hotell.difi.no/api/json/ssb/';
	private $resource;

	public function __construct() {
	}

	public function setResource($resource) {
		$this->resource = $resource;
		return $this;
	}

	public function getAllPages() {
		$url = self::API_URL . $this->resource;
		$curl = new UKMCURL();
		$res = $curl->process($url);

		if(!is_object($res)) {
			return false;
		}

		// Data is now an array of entries
		$data = $res->entries;
		$pages = $res->pages;

		for($i = 2; $i <= $pages; $i++) {
			$pagedURL = $url.'?page='.$i;
			$res = $curl->process($pagedURL);
			$data = array_merge($data, $res->entries);
		}

		return $data;
	}

	public static function parseKommuneData($kommuner) {
		$kommuneListe = array();
		foreach($kommuner as $kommune) {
			$kommuneListe[(int)$kommune->kode] = $kommune;
		}
		return $kommuneListe;
	}
}