<?php

class UKMCURL {
	var $timeout = 6;
	var $headers = false;
	var $content = true;
	var $postdata = false;
	var $json = false;
	
	public function __construct() {

	}
	
	public function timeout($timeout) {
		$this->timeout = $timeout;
		return $this;
	}
	
	public function port( $port ) {
		$this->port = $port;
		return $this;
	}
	
	public function post($postdata) {
		$this->postdata = $postdata;
		return $this;
	}
	
	public function headersOnly() {
		$this->headers = true;
		$this->content = false;
		$this->timeout(2);
		return $this;
	}
	
	public function process($url) {
		return $this->request( $url );
	}

	public function json($object) {
		$this->json = true;	
		$this->json_data = json_encode( $object );
		return $this;
	}
	
	public function request($url) {
		$this->url = $url;
		
		$this->_init();
		
		// Is this a post-request?
		if( $this->postdata ) {
			curl_setopt($this->curl, CURLOPT_POST, true);
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->postdata);
		}

		// Get only headers
		if(!$this->content) {
			curl_setopt($this->curl, CURLOPT_HEADER, 1); 
			curl_setopt($this->curl, CURLOPT_NOBODY, 1); 
		}
		
		if( $this->json ) {
			curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->json_data);
			curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($this->curl, CURLOPT_HTTPHEADER,
				array(
					    'Content-Type: application/json',
					    'Content-Length: ' . strlen($this->json_data)
				    )
			);                                                                                                                   
		}
		
		// Use custom port
		if(isset($this->port)) {
			curl_setopt($this->curl, CURLOPT_PORT, $this->port);
		}
		
		// Execute
		$this->result = curl_exec($this->curl);
		
		// Default, return content of processed request
		if($this->content) {
			$this->_analyze();
		// Return only header infos
		} else {
			$info = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
			curl_close($this->curl);
			return $info;
		}
	
		// Close connection
		curl_close($this->curl);
		
		return $this->data;
	}
	
	private function _init() {
		$this->curl = curl_init();
		curl_setopt($this->curl, CURLOPT_URL, $this->url);
		curl_setopt($this->curl, CURLOPT_REFERER, $_SERVER['PHP_SELF']);
		curl_setopt($this->curl, CURLOPT_USERAGENT, "UKMNorge API");
		curl_setopt($this->curl, CURLOPT_HEADER, $this->headers);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_TIMEOUT, $this->timeout);
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
