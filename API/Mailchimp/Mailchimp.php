<?php
namespace UKMNorge\API;

require_once("MCList.php");
require_once("UKM/curl.class.php");

use stdClass;
use Exception;
use UKMCURL;
use UKMNorge\API\Mailchimp\MCList;

require_once("UKMconfig.inc.php");

/**
 *
 * Inneholder state-informasjon, f.eks "workflow running"?
 */
class Mailchimp {
	
	private $mailchimp_url;
	private $lists = null;
	private $pageSize = 50;
	private $result = null;
		
	public function __construct() {
		if( !defined("MAILCHIMP_API_BASE_URL") || !defined("MAILCHIMP_API_KEY")) {
			throw new Exception("Missing mailchimp defines - see Readme.md");
		}

		$this->mailchimp_url = MAILCHIMP_API_BASE_URL;

		if(substr($this->mailchimp_url, 0, 5) != "https") {
			throw new Exception("Can't use Mailchimp-API without https to ensure API Key secrecy.");
		}
	}

	/**
	 * Henter alle mail-lister fra Mailchimp, og returnerer de som et array.
	 * @return Array [List]
	 */
	public function getLists() {
		$this->lists = $this->sendGetRequest("lists", 0)->lists;
		return($this->lists);
	}

	/**
	 *
	 * @return MCList $list
	 * @throws Exception $list_not_found
	 */
	public function getList($id) {
		if($this->lists == null) {
			$this->getLists();
		}

		foreach($this->lists as $list) {
			if($list->id == $id) {
				return new MCList($list->id, $list->name, $list->permission_reminder, $list->stats);
			}
		}
		// Not found
		throw new Exception("List not found!");
	}

	/**
	 * @param MCList @list - the list object that has been modified.
	 * @return Boolean - true if all changes passed. False if some changes failed. Call getFailedUpdates to retrieve the failed ones to modify or try again.
	 */
	public function saveListChanges(MCList $list) {
		// You can add up to 500 members for each API call
		$limit = 500;
		$data['members'] = $list->getChangedSubscribers();
		if(count($data['members']) > $limit) {
			throw new Exception("Can only add 500 new members per API-call");
		}

		$data['update_existing'] = $list->willUpdateExistingSubscribers();

		$this->result = $this->sendPostRequest("lists/".$list->getId(), $data);

		if($this->result->error_count == 0) {
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Gets the raw result object
	 * @return stdClass
	 */
	public function getResult() {
		if($this->result == null) {
			throw new Exception("No result found");
		}

		return $this->result;
	}
	
	/**
	 * Returns an array of all failed updates
	 * @return Array
	 */
	public function getFailedUpdates() {
		if($this->result == null) {
			throw new Exception("No result found");
		}

		return $this->result->errors;
	}

	/**
	 * Returns the amount of fields that failed to update
	 * @return int
	 */
	public function getTotalFailed() {
		if($this->result == null) {
			throw new Exception("No result found");
		}
	}

	/**
	 * Returns the amount of newly created fields
	 * @return int
	 */
	public function getTotalCreated() {
		if($this->result == null) {
			throw new Exception("No result found");
		}
	}
	
	/**
	 * Returns the amount of updated fields
	 * @return int
	 */
	public function getTotalUpdated() {
		if($this->result == null) {
			throw new Exception("No result found");
		}
	}

	/**
	 * Sends a POST request to create new objects on the server
	 */
	private function sendPostRequest($resource, $data) {
		$url = $this->mailchimp_url."/".$resource;

		$curl = new UKMCURL();
		$curl->json($data);
		$curl->requestType("POST");
		$curl->user('userpwd:'.MAILCHIMP_API_KEY);
		$response = $curl->request($url);

		return $response;
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

		$response = $curl->request($url);

		if($response == false) {
			// TODO: Do some error checking etc here.
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