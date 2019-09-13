<?php
namespace UKMNorge\API;

require_once("MCList.php");
require_once("UKM/curl.class.php");

use stdClass;
use Exception;
use UKMCURL;
use Mailchimp\MCList;

require_once("UKMconfig.inc.php");

/**
 *
 * Inneholder state-informasjon, f.eks "workflow running"?
 */
class Mailchimp {
	
	var $mailchimp_url = MAILCHIMP_API_BASE_URL;
	private $pageSize = 50;
		
	public function __construct() {
		if(substr($this->mailchimp_url, 0, 5) != "https") {
			throw new Exception("Can't use Mailchimp-API without https to ensure API Key secrecy.");
		}
	}

	/**
	 * Henter alle mail-lister fra Mailchimp, og returnerer de som et array.
	 * @return Array [List]
	 */
	public function getLists() {
		$this->sendGetRequest("lists", 0);
	}

	/**
	 *
	 * @return List $list
	 * @throws Exception $list_not_found
	 */
	public function getListId($id) {

	}

	public function updateList($id, $values) {

	}

	/**
	 * Sends the request to the correct mailchimp server and parses the response, including any errors.
	 * @param String $resource - lists, total_subscribers, ping etc
	 */
	private function sendGetRequest($resource, $page = null) {
		$url = $this->mailchimp_url."/".$resource;
		if($page != null) {
			$url .= "?offset".$page;
			$url .= "&count".$this->pageSize*($page+1);
		}

		$curl = new UKMCURL();

		$curl->requestType("GET");
		$curl->user('userpwd:'.MAILCHIMP_API_KEY);
		// Todo: Support data set size
		$response = $curl->request($url);

		if($response == false) {
			// TODO: Do some error checking etc here.
			var_dump($curl->error());
		}

		return $response;
	}

	public function ping() : bool {
		$response = $this->sendGetRequest("ping", null);

		if(is_object($response)) {
			return true;
		}
		return false;
	}
}