<?php

class UKMCURL {
	var $timeout = 6;
	var $headers = false;

	public function __construct() {

	}
	
	public function timeout($timeout) {
		$this->timeout = $timeout;
	}

	public function request($url) {
		$this->url = $url;
		
		$this->curl = curl_init();
		curl_setopt($this->curl, CURLOPT_URL, $this->url);
		curl_setopt($this->curl, CURLOPT_REFERER, $_SERVER['PHP_SELF']);
		curl_setopt($this->curl, CURLOPT_USERAGENT, "UKMNorge API");
		curl_setopt($this->curl, CURLOPT_HEADER, $this->headers);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_TIMEOUT, $this->timeout);

		
		if(isset($this->port))
			curl_setopt($this->curl, CURLOPT_PORT, $this->port);
			
		$this->result = curl_exec($this->curl);
		$this->_analyze();
		curl_close($this->curl);
		
	}
	
	private function _analyze() {
		$this->_isJson();
		$this->_isSerialized();
		if(!isset($this->data)) {
			if($this->result === 'false')
				$this->data = false;
			elseif($this->result === 'true')
				$this->data = true;
			else
				$this->data = $this->result;
		}
	}
	
	private function _isJson() {
		$decoded = @json_decode($this->result);
		$this->is_json = is_object($decoded);
		
		if($this->is_json)
			$this->data = $decoded;
	}
	
	private function _isSerialized() {
		$data = @unserialize($this->result);
		if ($this->result === 'b:0;' || $data !== false) {
			$this->data = $data;
		}
	}
}

$UKMCURL = new UKMCURL();
?>