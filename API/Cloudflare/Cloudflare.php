<?php

namespace UKMNorge\API\Cloudflare;
use UKMNorge\Http\Curl;
# Denne klassen er et interface mot CloudFlare, ferdig konfigurert for UKMs systemer.
class Cloudflare {
	private $URL = UKM_CLOUDFLARE_URL;
	private $ukmno_zone = UKM_CLOUDFLARE_UKMNO_ZONE;
	private $AuthKey = UKM_CLOUDFLARE_AUTH_KEY;
	private $email = UKM_CLOUDFLARE_EMAIL;
	var $result = null;

	// Denne funksjonen trigger en cloudflare-cache-rebuild
	// Input er et enkelt filnavn eller et array med filnavn med hele URLen for access utenfra.
	// Funksjonen returnerer true ved suksess, false ved feil.
	// TODO: Logging ved feil.
	public function purge($file = false) {
		if (!$file) {
			return false;
		}
		$purge_url = $this->URL . $this->ukmno_zone . '/purge_cache';
		
		$data = array();
		if (is_array($file)) {
			foreach ($file as $fil) {
				if (!empty($fil))
					$data['files'][] = $fil;
			}
		}
		else
			$data['files'][] = $file;

		// Konfigurer CURL
		$curl = new CURL();
		$curl->port(443);
		#$curl->
		$curl->requestType('DELETE');
		$curl->addHeader('X-Auth-Email: '.$this->email);
		$curl->addHeader('X-Auth-Key: '. $this->AuthKey);
		$curl->json($data);
		$this->result = $curl->request($purge_url);

		if (isset($this->result->success) && $this->result->success) {
	    	return true;
	    }
	    #var_dump($curl);
	    #var_dump($res);
	    #$curl->json
	    $error = var_export($this->result->errors, true);
	    if (is_array($file)) {
	    	$file = implode($file);
	    	error_log('Cloudflare cache-clear feilet på URLene: '.$file .' med feilmeldingene ' .$error);
	    }
	    else 
	    	error_log('Cloudflare cache-clear feilet på URL: '.$file.' med feilmeldingen ' .$errors);
	    return false;
	}

	public function purgeAll() {
		$purge_url = $this->URL . $this->ukmno_zone . '/purge_cache';

		$data['purge_everything'] = true;

		// Konfigurer CURL
		$curl = new CURL();
		$curl->port(443);
		#$curl->
		$curl->requestType('DELETE');
		$curl->addHeader('X-Auth-Email: '.$this->email);
		$curl->addHeader('X-Auth-Key: '. $this->AuthKey);
		$curl->json($data);
		$this->result = $curl->request($purge_url);

		if (isset($this->result->success) && $this->result->success) {
	    	return true;
	    }
	    #var_dump($curl);
	    #var_dump($res);
	    #$curl->json
	    $error = var_export($this->result->errors, true);
	    if (is_array($file)) {
	    	$file = implode($file);
	    	error_log('Cloudflare cache-clear feilet på URLene: '.$file .' med feilmeldingene ' .$error);
	    }
	    else 
	    	error_log('Cloudflare cache-clear feilet på URL: '.$file.' med feilmeldingen ' .$errors);
	    return false;
	}

	public function result() {
		return $this->result;
	}
}